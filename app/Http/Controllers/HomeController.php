<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use App\Models\WarrantyClaim;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\AdminPaymentProofUploadedNotification;
use App\Notifications\AdminRefundRequestedNotification;
use App\Notifications\WarrantyClaimSubmittedNotification;
use App\Services\BayarGgGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification as NotificationMessage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly BayarGgGatewayService $bayarGgGatewayService,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $keyword = trim((string) $request->query('q', ''));
        $selectedCategoryId = $request->integer('category');

        if ($selectedCategoryId > 0 && !Category::whereKey($selectedCategoryId)->exists()) {
            return redirect()->route('home', array_filter([
                'q' => $keyword,
            ]));
        }

        $categories = Category::query()
            ->withCount([
                'products as active_products_count' => fn($query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->with('category:id,name')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->when($selectedCategoryId > 0, fn($query) => $query->where('category_id', $selectedCategoryId))
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('description', 'like', '%' . $keyword . '%')
                        ->orWhereHas('category', fn($categoryQuery) => $categoryQuery->where('name', 'like', '%' . $keyword . '%'));
                });
            })
            ->latest()
            ->paginate(18)
            ->withQueryString();

        return view('home.index', [
            'categories' => $categories,
            'products' => $products,
            'activeCategory' => $categories->firstWhere('id', $selectedCategoryId),
            'totalProducts' => Product::where('is_active', true)->count(),
            'totalCategories' => $categories->count(),
            'keyword' => $keyword,
            ...$this->cartSummary($request),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $product = Product::query()
            ->with('category:id,name')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->limit(4)
            ->get();

        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get();

        $user = $request->user();
        $userReview = null;
        $canReview = false;

        if ($user) {
            $userReview = Review::query()
                ->where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            $hasPurchased = OrderItem::query()
                ->where('product_id', $product->id)
                ->whereHas('order', fn($query) => $query
                    ->where('user_id', $user->id)
                    ->where('status', 'completed'))
                ->exists();

            $canReview = $hasPurchased;
        }

        return view('home.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'reviews' => $reviews,
            'canReview' => $canReview,
            'userReview' => $userReview,
            ...$this->cartSummary($request),
        ]);
    }

    public function submitReview(Request $request, string $slug): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $product = Product::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $hasPurchased = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn($query) => $query
                ->where('user_id', $user->id)
                ->where('status', 'completed'))
            ->exists();

        if (! $hasPurchased) {
            return back()->with('error', 'Anda hanya bisa memberi ulasan setelah membeli produk ini.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'image' => ['prohibited'],
        ], [
            'image.prohibited' => 'Ulasan saat ini hanya mendukung teks tanpa gambar.',
        ]);

        $existingReview = Review::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if (! empty($existingReview?->image)) {
            Storage::disk('public')->delete($existingReview->image);
        }

        Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'rating' => (int) $validated['rating'],
                'comment' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
                'image' => null,
            ],
        );

        return back()->with('success', 'Ulasan produk berhasil disimpan. Terima kasih atas feedback Anda.');
    }

    public function buy(Request $request, string $slug): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $qty = (int) ($validated['qty'] ?? 1);

        // ATOMIC STOCK CHECK: Gunakan DB transaction + lockForUpdate
        // untuk mencegah race condition dimana 2 user bisa add-to-cart
        // stok terakhir secara bersamaan.
        try {
            $result = DB::transaction(function () use ($slug, $qty, $request) {
                $product = Product::query()
                    ->where('is_active', true)
                    ->where('slug', $slug)
                    ->lockForUpdate()
                    ->first();

                if (!$product) {
                    throw new \RuntimeException('PRODUCT_NOT_FOUND');
                }

                if ((int) $product->stock < 1) {
                    throw new \RuntimeException('OUT_OF_STOCK');
                }

                $simpleCart = $request->session()->get('simple_cart', []);

                // Bersihin array session biar ID-nya jadi Key yang mutlak.
                // Ini buat nyegah bug item masuk ke keranjang jadi 2 baris (dobel).
                $cleanCart = [];
                foreach ($simpleCart as $item) {
                    if (isset($item['product_id'])) {
                        $cleanCart[$item['product_id']] = $item;
                    }
                }
                $simpleCart = $cleanCart;

                $existingQty = isset($simpleCart[$product->id]) ? (int) $simpleCart[$product->id]['qty'] : 0;

                if ($existingQty >= (int) $product->stock) {
                    throw new \RuntimeException('STOCK_LIMIT_REACHED');
                }

                $newQty = min($existingQty + $qty, (int) $product->stock);

                // Timpa data pakai ID sebagai key, jadi kuantitasnya aja yang nambah, bukan barisnya.
                $simpleCart[$product->id] = [
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (int) $product->price,
                    'unit' => $product->unit,
                    'qty' => $newQty,
                ];

                $request->session()->put('simple_cart', $simpleCart);

                return $product->name;
            });

            return back()->with('success', $result . ' berhasil ditambahkan ke keranjang.');
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'PRODUCT_NOT_FOUND' => back()->with('error', 'Produk tidak ditemukan atau sudah tidak aktif.'),
                'OUT_OF_STOCK' => back()->with('error', 'Stok produk habis. Silakan pilih produk lain.'),
                'STOCK_LIMIT_REACHED' => back()->with('error', 'Jumlah produk di keranjang sudah mencapai stok tersedia.'),
                default => back()->with('error', 'Gagal menambahkan ke keranjang.'),
            };
        }
    }

    public function cart(Request $request): View
    {
        $simpleCart = $request->session()->get('simple_cart', []);
        $productIds = array_map('intval', array_keys($simpleCart));

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $cartItems = collect($simpleCart)
            ->map(function ($item, $productId) use ($products) {
                $product = $products->get((int) $productId);
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $price = (int) ($item['price'] ?? ($product?->price ?? 0));
                $stock = max(0, (int) ($product?->stock ?? 0));
                $isAvailable = (bool) ($product?->is_active) && $stock > 0;

                return [
                    'product_id' => (int) ($item['product_id'] ?? $productId),
                    'name' => (string) ($item['name'] ?? ($product?->name ?? 'Produk tidak ditemukan')),
                    'slug' => (string) ($item['slug'] ?? ($product?->slug ?? '')),
                    'image_url' => (string) ($product?->image_url ?? asset('img/hero-bg.jpg')),
                    'unit' => (string) ($item['unit'] ?? ($product?->unit ?? 'pcs')),
                    'price' => $price,
                    'qty' => $qty,
                    'stock' => $stock,
                    'is_available' => $isAvailable,
                    'subtotal' => $price * $qty,
                ];
            })
            ->values();

        $subtotal = (int) $cartItems->sum('subtotal');
        $totalQuantity = (int) $cartItems->sum('qty');
        $shippingCostPerItem = $this->shippingCostPerItem();
        $shippingCost = $this->calculateShippingCost($totalQuantity);
        $totalAmount = $subtotal + $shippingCost;

        return view('home.cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'totalQuantity' => $totalQuantity,
            'shippingCostPerItem' => $shippingCostPerItem,
            'shippingCost' => $shippingCost,
            'totalAmount' => $totalAmount,
            ...$this->cartSummary($request),
        ]);
    }

    public function checkoutPage(Request $request): View|RedirectResponse
    {
        $simpleCart = $request->session()->get('simple_cart', []);

        if (count($simpleCart) === 0) {
            return redirect()->route('home.cart')->with('error', 'Keranjang masih kosong, belum bisa checkout.');
        }

        $productIds = array_map('intval', array_keys($simpleCart));

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $cartItems = collect($simpleCart)
            ->map(function ($item, $productId) use ($products) {
                $product = $products->get((int) $productId);
                $qty = max(1, (int) ($item['qty'] ?? 1));
                $price = (int) ($item['price'] ?? ($product?->price ?? 0));
                $stock = max(0, (int) ($product?->stock ?? 0));
                $isAvailable = (bool) ($product?->is_active) && $stock > 0;

                return [
                    'product_id' => (int) ($item['product_id'] ?? $productId),
                    'name' => (string) ($item['name'] ?? ($product?->name ?? 'Produk tidak ditemukan')),
                    'slug' => (string) ($item['slug'] ?? ($product?->slug ?? '')),
                    'image_url' => (string) ($product?->image_url ?? asset('img/hero-bg.jpg')),
                    'unit' => (string) ($item['unit'] ?? ($product?->unit ?? 'pcs')),
                    'price' => $price,
                    'qty' => $qty,
                    'stock' => $stock,
                    'is_available' => $isAvailable,
                    'subtotal' => $price * $qty,
                ];
            })
            ->values();

        $subtotal = (int) $cartItems->sum('subtotal');
        $totalQuantity = (int) $cartItems->sum('qty');
        $shippingCostPerItem = $this->shippingCostPerItem();
        $shippingCost = $this->calculateShippingCost($totalQuantity);
        $totalAmount = $subtotal + $shippingCost;

        $userAddresses = collect();
        $defaultAddressId = null;
        $selectedAddress = null;

        if ($request->user()) {
            $userAddresses = $request->user()
                ->addresses()
                ->orderByDesc('is_default')
                ->latest()
                ->get();

            $defaultAddressId = $userAddresses->firstWhere('is_default', true)?->id
                ?? $userAddresses->first()?->id;

            $selectedAddress = $userAddresses->firstWhere('id', $defaultAddressId);
        }

        return view('home.checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'totalQuantity' => $totalQuantity,
            'shippingCostPerItem' => $shippingCostPerItem,
            'shippingCost' => $shippingCost,
            'totalAmount' => $totalAmount,
            'userAddresses' => $userAddresses,
            'defaultAddressId' => $defaultAddressId,
            'selectedAddress' => $selectedAddress,
            ...$this->cartSummary($request),
        ]);
    }

    public function updateCart(Request $request, int $productId): RedirectResponse
    {
        $validated = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $simpleCart = $request->session()->get('simple_cart', []);

        if (!isset($simpleCart[$productId])) {
            return redirect()->route('home.cart')->with('error', 'Item tidak ditemukan di keranjang.');
        }

        $product = Product::query()->where('id', $productId)->first();

        if (!$product || !$product->is_active || (int) $product->stock < 1) {
            unset($simpleCart[$productId]);
            $request->session()->put('simple_cart', $simpleCart);

            return redirect()->route('home.cart')->with('error', 'Produk tidak tersedia dan dihapus dari keranjang.');
        }

        $newQty = min((int) $validated['qty'], (int) $product->stock);
        $simpleCart[$productId]['qty'] = $newQty;
        $request->session()->put('simple_cart', $simpleCart);

        return redirect()->route('home.cart')->with('success', 'Jumlah produk di keranjang berhasil diperbarui.');
    }

    public function removeFromCart(Request $request, int $productId): RedirectResponse
    {
        $simpleCart = $request->session()->get('simple_cart', []);

        if (isset($simpleCart[$productId])) {
            unset($simpleCart[$productId]);
            $request->session()->put('simple_cart', $simpleCart);

            return redirect()->route('home.cart')->with('success', 'Produk berhasil dihapus dari keranjang.');
        }

        return redirect()->route('home.cart')->with('error', 'Produk tidak ditemukan di keranjang.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $simpleCart = $request->session()->get('simple_cart', []);

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'in:cod,bank_transfer,ewallet,dummy,bayargg'],
            'address_id' => ['nullable', 'integer'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'address_label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'address_phone' => ['nullable', 'string', 'max:30'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address_notes' => ['nullable', 'string', 'max:1000'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);

        if (count($simpleCart) === 0) {
            return redirect()->route('home.cart')->with('error', 'Keranjang masih kosong, belum bisa checkout.');
        }

        $productIds = array_map('intval', array_keys($simpleCart));
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        if ($products->count() !== count($productIds)) {
            return redirect()->route('home.cart')->with('error', 'Ada produk yang sudah tidak tersedia di keranjang.');
        }

        $orderItemsPayload = [];
        $subtotal = 0;
        $totalQuantity = 0;

        foreach ($simpleCart as $productId => $cartItem) {
            $product = $products->get((int) $productId);
            $qty = max(1, (int) ($cartItem['qty'] ?? 1));

            if (!$product || !$product->is_active) {
                return redirect()->route('home.cart')
                    ->with('error', 'Produk ' . ($cartItem['name'] ?? '#' . $productId) . ' sudah tidak aktif.');
            }

            if ((int) $product->stock < $qty) {
                return redirect()->route('home.cart')
                    ->with('error', 'Stok untuk produk ' . $product->name . ' tidak mencukupi.');
            }

            $lineSubtotal = ((int) $product->price) * $qty;
            $subtotal += $lineSubtotal;
            $totalQuantity += $qty;

            $orderItemsPayload[] = [
                'product' => $product,
                'price' => (int) $product->price,
                'qty' => $qty,
                'subtotal' => $lineSubtotal,
            ];
        }

        $shippingCostPerItem = $this->shippingCostPerItem();
        $shippingCost = $this->calculateShippingCost($totalQuantity);
        $discountAmount = 0;
        $totalAmount = $subtotal + $shippingCost - $discountAmount;

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $address = null;

        if (!empty($validated['address_id'])) {
            $address = $user->addresses()->whereKey((int) $validated['address_id'])->first();

            if (!$address) {
                return redirect()->route('home.checkout')
                    ->withInput()
                    ->with('error', 'Alamat yang dipilih tidak valid.');
            }
        }

        if (!$address && $this->hasAddressFormInput($validated)) {
            $requiredAddressFields = [
                'recipient_name',
                'address_phone',
                'address_line',
                'city',
                'province',
                'postal_code',
            ];

            $missingAddressFields = collect($requiredAddressFields)
                ->filter(fn($field) => blank($validated[$field] ?? null));

            if ($missingAddressFields->isNotEmpty()) {
                return redirect()->route('home.checkout')
                    ->withInput()
                    ->with('error', 'Lengkapi data alamat baru sebelum checkout.');
            }

            $shouldDefault = (bool) ($validated['set_as_default'] ?? false) || !$user->addresses()->exists();

            if ($shouldDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = Address::create([
                'user_id' => $user->id,
                'label' => $validated['address_label'] ?? 'Alamat Baru',
                'recipient_name' => $validated['recipient_name'],
                'phone' => $validated['address_phone'],
                'address_line' => $validated['address_line'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'postal_code' => $validated['postal_code'],
                'notes' => $validated['address_notes'] ?? null,
                'is_default' => $shouldDefault,
            ]);
        }

        if (!$address) {
            $address = $user->addresses()->where('is_default', true)->first()
                ?? $user->addresses()->latest()->first();
        }

        if (!$address) {
            return redirect()->route('home.checkout')
                ->withInput()
                ->with('error', 'Pilih alamat default atau isi alamat baru sebelum checkout.');
        }

        if ((bool) ($validated['set_as_default'] ?? false) && !$address->is_default) {
            $user->addresses()->update(['is_default' => false]);
            $address->is_default = true;
            $address->save();
        }

        $customerName = (string) ($validated['customer_name'] ?? $user->name ?? $address->recipient_name);
        $customerEmail = (string) ($validated['customer_email'] ?? $user->email);
        $customerPhone = (string) ($validated['customer_phone'] ?? $address->phone ?? '');

        $addressSnapshot = $address
            ? implode(', ', array_filter([
                $address->address_line,
                $address->city,
                $address->province,
                $address->postal_code,
            ]))
            : implode(', ', array_filter([
                $validated['address_line'] ?? null,
                $validated['city'] ?? null,
                $validated['province'] ?? null,
                $validated['postal_code'] ?? null,
            ]));

        $orderNotesParts = [
            'Order dibuat dari checkout storefront. Total item: ' . $totalQuantity,
            'Ongkir per item: Rp ' . number_format($shippingCostPerItem, 0, ',', '.'),
        ];

        if ($addressSnapshot !== '') {
            $orderNotesParts[] = 'Alamat: ' . $addressSnapshot;
        }

        if (!empty($validated['address_notes'])) {
            $orderNotesParts[] = 'Catatan alamat: ' . $validated['address_notes'];
        }

        $orderNotes = implode(' | ', $orderNotesParts);

        $paymentMethod = (string) ($validated['payment_method'] ?? 'dummy');

        $orderCode = $this->generateOrderCode();
        $paymentCode = $this->generatePaymentCode();
        try {
            $order = DB::transaction(function () use ($user, $address, $customerName, $customerEmail, $customerPhone, $orderNotes, $orderCode, $paymentCode, $subtotal, $shippingCost, $discountAmount, $totalAmount, $orderItemsPayload, $paymentMethod) {
                $order = Order::create([
                    'order_code' => $orderCode,
                    'user_id' => $user?->id,
                    'address_id' => $address?->id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone !== '' ? $customerPhone : null,
                    'notes' => $orderNotes,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'warranty_status' => 'active',
                    'subtotal' => $subtotal,
                    'shipping_cost' => $shippingCost,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'placed_at' => now(),
                ]);

                foreach ($orderItemsPayload as $payload) {
                    /** @var \App\Models\Product $product */
                    $product = $payload['product'];

                    $freshProduct = Product::where('id', $product->id)->lockForUpdate()->first();
                    if (!$freshProduct || $freshProduct->stock < $payload['qty']) {
                        throw new \Exception('Stok untuk produk ' . $product->name . ' tidak mencukupi (tersisa: ' . ($freshProduct->stock ?? 0) . ').');
                    }

                    $warrantyDays = (int) $freshProduct->warranty_days_for_claim;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_slug' => $product->slug,
                        'unit' => $product->unit,
                        'price' => $payload['price'],
                        'quantity' => $payload['qty'],
                        'subtotal' => $payload['subtotal'],
                        'warranty_days' => $warrantyDays,
                        'warranty_expires_at' => null,
                    ]);

                    $freshProduct->decrement('stock', $payload['qty']);
                }

                $order->payments()->create([
                    'payment_code' => $paymentCode,
                    'method' => $paymentMethod,
                    'gateway_provider' => $paymentMethod === 'bayargg' ? 'bayargg' : null,
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'notes' => match ($paymentMethod) {
                        'cod' => 'Bayar di tempat saat barang sampai (COD).',
                        'bank_transfer' => 'Menunggu transfer bank dan upload bukti pembayaran. Setelah upload, status menunggu ACC admin.',
                        'ewallet' => 'Menunggu pembayaran e-wallet dan upload bukti pembayaran. Setelah upload, status menunggu ACC admin.',
                        'bayargg' => 'Link pembayaran Bayar.gg sedang disiapkan.',
                        default => 'Menunggu pembayaran.',
                    },
                ]);

                return $order;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('home.cart')
                ->with('error', 'Checkout gagal diproses. Silakan coba lagi atau hubungi admin.');
        }

        $latestPayment = $order->payments()->latest('id')->first();
        $hasBayarGgPaymentLink = false;

        if ($paymentMethod === 'bayargg' && $latestPayment) {
            $hasBayarGgPaymentLink = $this->syncBayarGgPaymentLink($order, $latestPayment);
        }

        $this->notifyAdminsWhenEnabled('notif_order_new', new AdminNewOrderNotification($order));

        $request->session()->forget('simple_cart');

        $successMsg = 'Checkout berhasil. Kode: ' . $order->order_code . '. ';
        if ($paymentMethod === 'cod') {
            $successMsg .= 'Silakan siapkan uang pas saat kurir tiba.';
        } elseif ($paymentMethod === 'bayargg') {
            $successMsg .= $hasBayarGgPaymentLink
                ? 'Lanjutkan pembayaran otomatis via Bayar.gg dari halaman detail pesanan.'
                : 'Order tersimpan, tetapi link Bayar.gg belum berhasil dibuat. Coba tombol "Buat Link Bayar.gg Lagi" di halaman detail pesanan.';
        } else {
            $successMsg .= 'Segera lakukan pembayaran, upload bukti melalui Tracking Pesanan, lalu tunggu ACC admin.';
        }

        if ($paymentMethod === 'bayargg') {
            return redirect()->route('home.tracking.show', $order->order_code)
                ->with('success', $successMsg);
        }

        return redirect()->route('home.cart')
            ->with('success', $successMsg)
            ->with('checkout_order_code', $order->order_code);
    }

    public function submitWarrantyClaim(Request $request, Order $order, OrderItem $orderItem): RedirectResponse
    {
        if ($orderItem->order_id !== $order->id) {
            abort(404);
        }

        $user = $request->user();
        $isAdmin = $user?->hasAnyRole(['super-admin', 'admin']) ?? false;

        if (!$isAdmin) {
            if (!$user || (int) $order->user_id !== (int) $user->id) {
                abort(403, 'Anda tidak punya akses ke pesanan ini.');
            }

            if (!$order->user_id) {
                return redirect()->route('home.cart')
                    ->with('error', 'Pesanan guest tidak mendukung klaim garansi online.');
            }
        }

        if ((int) $orderItem->warranty_days < 1) {
            return redirect()->route('home.cart')
                ->with('error', 'Garansi klaim hanya berlaku untuk produk elektronik.');
        }

        if (!$orderItem->warranty_expires_at || $orderItem->warranty_expires_at->isPast()) {
            return redirect()->route('home.cart')
                ->with('error', 'Masa garansi item ini sudah berakhir.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
            'damage_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,mp4,mov,webm', 'max:20480'],
        ]);

        $claim = null;
        $storedProofPath = null;

        try {
            $claim = DB::transaction(function () use ($order, $orderItem, $user, $validated, &$storedProofPath) {
                $lockedItem = OrderItem::query()
                    ->where('id', $orderItem->id)
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedItem) {
                    throw new \RuntimeException('ORDER_ITEM_NOT_FOUND');
                }

                if ((int) $lockedItem->warranty_days < 1) {
                    throw new \RuntimeException('NON_ELECTRONIC_ITEM');
                }

                if (!$lockedItem->warranty_expires_at || $lockedItem->warranty_expires_at->isPast()) {
                    throw new \RuntimeException('WARRANTY_EXPIRED');
                }

                $hasOpenClaim = WarrantyClaim::query()
                    ->where('order_item_id', $lockedItem->id)
                    ->whereIn('status', ['submitted', 'reviewing', 'approved'])
                    ->lockForUpdate()
                    ->exists();

                if ($hasOpenClaim) {
                    throw new \RuntimeException('ACTIVE_CLAIM_EXISTS');
                }

                $claimCode = $this->generateWarrantyClaimCode();
                $storedProofPath = $validated['damage_proof']->store('warranty-claims/' . $claimCode, 'local');

                $claim = WarrantyClaim::create([
                    'claim_code' => $claimCode,
                    'order_id' => $order->id,
                    'order_item_id' => $lockedItem->id,
                    'user_id' => $user?->id,
                    'reason' => $validated['reason'],
                    'status' => 'submitted',
                    'damage_proof_url' => $storedProofPath,
                    'damage_proof_mime' => $validated['damage_proof']->getMimeType(),
                    'requested_at' => now(),
                ]);

                $claim->activities()->create([
                    'actor_id' => $user?->id,
                    'actor_name' => $user?->name,
                    'action' => 'submitted',
                    'from_status' => null,
                    'to_status' => 'submitted',
                    'note' => 'Klaim diajukan user dengan bukti kerusakan terlampir.',
                ]);

                return $claim;
            }, 3);
        } catch (\RuntimeException $e) {
            if ($storedProofPath) {
                $this->deleteSensitiveFile($storedProofPath);
            }

            if ($e->getMessage() === 'ACTIVE_CLAIM_EXISTS') {
                return redirect()->route('home.cart')
                    ->with('error', 'Item ini sudah memiliki klaim garansi aktif.');
            }

            if ($e->getMessage() === 'WARRANTY_EXPIRED') {
                return redirect()->route('home.cart')
                    ->with('error', 'Masa garansi item ini sudah berakhir.');
            }

            if ($e->getMessage() === 'NON_ELECTRONIC_ITEM') {
                return redirect()->route('home.cart')
                    ->with('error', 'Garansi klaim hanya berlaku untuk produk elektronik.');
            }

            return redirect()->route('home.cart')
                ->with('error', 'Item pesanan tidak ditemukan atau tidak valid untuk klaim.');
        } catch (\Throwable $e) {
            if ($storedProofPath) {
                $this->deleteSensitiveFile($storedProofPath);
            }

            return redirect()->route('home.cart')
                ->with('error', 'Upload bukti kerusakan gagal diproses. Coba lagi dengan file lain.');
        }

        if (!$claim) {
            return redirect()->route('home.cart')
                ->with('error', 'Klaim garansi gagal diproses. Silakan coba lagi.');
        }

        $this->notifyAdminsWhenEnabled('notif_claim_new', new WarrantyClaimSubmittedNotification($claim));

        return redirect()->route('home.cart')->with('success', 'Klaim garansi berhasil diajukan. Tim admin akan meninjau klaim Anda.');
    }

    public function warrantyCenter(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,active,expired'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $status = $filters['status'] ?? 'all';

        $warrantyItems = OrderItem::query()
            ->with([
                'order:id,order_code,user_id,status,payment_status,placed_at,created_at',
                'warrantyClaims' => fn($query) => $query->latest(),
            ])
            ->where('warranty_days', '>', 0)
            ->whereHas('order', fn($query) => $query->where('user_id', $user->id))
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('product_name', 'like', '%' . $keyword . '%')
                        ->orWhereHas('order', fn($orderQuery) => $orderQuery->where('order_code', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($status === 'active', fn($query) => $query->whereNotNull('warranty_expires_at')->where('warranty_expires_at', '>', now()))
            ->when($status === 'expired', fn($query) => $query->where(function ($subQuery) {
                $subQuery
                    ->whereNull('warranty_expires_at')
                    ->orWhere('warranty_expires_at', '<=', now());
            }))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('home.warranty', [
            'warrantyItems' => $warrantyItems,
            'filters' => $filters,
            ...$this->cartSummary($request),
        ]);
    }

    public function warrantyClaims(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:submitted,reviewing,approved,rejected,resolved'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $claims = WarrantyClaim::query()
            ->with([
                'order:id,order_code,payment_status,status',
                'orderItem:id,order_id,product_name,quantity,warranty_days,warranty_expires_at',
                'activities' => fn($query) => $query->latest(),
            ])
            ->where('user_id', $user->id)
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('claim_code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('order', fn($orderQuery) => $orderQuery->where('order_code', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('orderItem', fn($itemQuery) => $itemQuery->where('product_name', 'like', '%' . $keyword . '%'));
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('home.warranty_claims', [
            'claims' => $claims,
            'filters' => $filters,
            ...$this->cartSummary($request),
        ]);
    }

    public function transactionHistory(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:all,completed,cancelled,processing,shipped,pending'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $status = $filters['status'] ?? 'completed';

        $orders = Order::query()
            ->with([
                'items.product:id,image_path',
                'payments' => fn($query) => $query->latest(),
                'address',
            ])
            ->where('user_id', $user->id)
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('order_code', 'like', '%' . $keyword . '%')
                        ->orWhere('customer_name', 'like', '%' . $keyword . '%')
                        ->orWhere('customer_email', 'like', '%' . $keyword . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('home.transactions', [
            'orders' => $orders,
            'filters' => $filters,
            ...$this->cartSummary($request),
        ]);
    }

    public function notifications(Request $request): View
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if (!Schema::hasTable('notifications')) {
            $notifications = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('home.notifications', [
                'notifications' => $notifications,
                ...$this->cartSummary($request),
            ]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('home.notifications', [
            'notifications' => $notifications,
            ...$this->cartSummary($request),
        ]);
    }

    public function openNotification(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if (!Schema::hasTable('notifications')) {
            return redirect()->route('home.notifications.index')
                ->with('error', 'Tabel notifikasi belum tersedia. Jalankan migrasi database terlebih dahulu.');
        }

        $notificationModel = $user->notifications()
            ->whereKey($notification)
            ->first();

        abort_unless($notificationModel, 404);

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        $payload = is_array($notificationModel->data) ? $notificationModel->data : [];
        $targetUrl = trim((string) ($payload['route'] ?? ''));

        if ($targetUrl === '') {
            return redirect()->route('home.notifications.index');
        }

        if ($this->isAllowedNotificationRedirectUrl($targetUrl)) {
            return redirect()->to($targetUrl);
        }

        return redirect()->route('home.notifications.index');
    }

    public function markAllNotificationsRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        if (!Schema::hasTable('notifications')) {
            return redirect()->route('home.notifications.index')
                ->with('error', 'Tabel notifikasi belum tersedia. Jalankan migrasi database terlebih dahulu.');
        }

        $user->unreadNotifications->markAsRead();

        return redirect()->route('home.notifications.index')
            ->with('success', 'Semua notifikasi sudah ditandai dibaca.');
    }

    public function uploadPaymentProof(Request $request, string $orderCode): RedirectResponse
    {
        $request->validate([
            'payment_proof' => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($orderCode));

        $order = Order::with('payments')
            ->where('order_code', $normalizedOrderCode)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Pesanan sudah dibatalkan, bukti pembayaran tidak dapat diunggah.');
        }

        if ($order->payment_status === 'paid') {
            return back()->with('error', 'Pesanan ini sudah lunas. Tidak perlu unggah ulang bukti pembayaran.');
        }

        $payment = $order->payments()->latest()->first();

        if (!$payment) {
            return back()->with('error', 'Data pembayaran tidak ditemukan.');
        }

        if (in_array($payment->status, ['paid', 'refunded'], true)) {
            return back()->with('error', 'Status pembayaran saat ini tidak mengizinkan unggah bukti baru.');
        }

        if (! in_array($payment->method, ['bank_transfer', 'ewallet', 'dummy'], true)) {
            if ($payment->method === 'cod') {
                return back()->with('error', 'Metode COD tidak memerlukan upload bukti pembayaran.');
            }

            if ($payment->method === 'bayargg') {
                return back()->with('error', 'Pesanan Bayar.gg tidak memerlukan upload bukti. Gunakan tombol Bayar Sekarang dari link Bayar.gg.');
            }

            return back()->with('error', 'Metode pembayaran pada pesanan ini tidak mendukung upload bukti.');
        }

        $isReplacingExistingProof = false;
        $oldProofPath = null;

        if (! empty($payment->proof_url)) {
            if (! $request->boolean('replace_proof')) {
                return back()->with('error', 'Bukti pembayaran sudah diunggah. Gunakan aksi ganti bukti jika ingin mengunggah ulang.');
            }

            $oldProofPath = $payment->proof_url;
            $isReplacingExistingProof = true;
        }

        $path = $request->file('payment_proof')->store('payments/' . $order->order_code, 'local');

        $payment->update([
            'proof_url' => $path,
            'status' => 'pending',
            'paid_at' => null,
            'notes' => $isReplacingExistingProof
                ? 'Bukti pembayaran diganti oleh pelanggan, status menunggu ACC admin.'
                : 'Bukti pembayaran diunggah oleh pelanggan, status menunggu ACC admin.',
        ]);

        $order->update([
            'payment_status' => 'pending',
            'paid_at' => null,
        ]);

        if ($oldProofPath) {
            $this->deleteSensitiveFile($oldProofPath);
        }

        $this->notifyAdminsWhenEnabled(
            'notif_order_paid',
            new AdminPaymentProofUploadedNotification($order, $payment, $isReplacingExistingProof),
        );

        return back()->with('success', 'Bukti pembayaran berhasil diunggah dan diteruskan ke admin. Silakan tunggu ACC admin.');
    }

    public function viewPaymentProof(Request $request, string $orderCode, Payment $payment): BinaryFileResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($orderCode));

        $order = Order::query()
            ->where('order_code', $normalizedOrderCode)
            ->firstOrFail();

        if ((int) $payment->order_id !== (int) $order->id) {
            abort(404);
        }

        $isAdmin = $user->hasAnyRole(['super-admin', 'admin']);
        $ownsOrder = (int) $order->user_id === (int) $user->id;

        if (! $isAdmin && ! $ownsOrder) {
            abort(403);
        }

        $proofFile = $this->sensitiveFileLocation($payment->proof_url);
        if ($proofFile === null) {
            abort(404, 'File bukti pembayaran tidak ditemukan.');
        }

        return $this->sensitiveFileResponse($proofFile['absolute_path']);
    }

    public function viewWarrantyClaimProof(Request $request, WarrantyClaim $warrantyClaim): BinaryFileResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $isAdmin = $user->hasAnyRole(['super-admin', 'admin']);
        $ownsClaim = (int) $warrantyClaim->user_id === (int) $user->id;

        if (! $isAdmin && ! $ownsClaim) {
            abort(403);
        }

        $proofFile = $this->sensitiveFileLocation($warrantyClaim->damage_proof_url);
        if ($proofFile === null) {
            abort(404, 'File bukti kerusakan tidak ditemukan.');
        }

        return $this->sensitiveFileResponse(
            $proofFile['absolute_path'],
            $warrantyClaim->damage_proof_mime,
        );
    }

    /**
     * @return array{disk: string, path: string, absolute_path: string}|null
     */
    private function sensitiveFileLocation(?string $path): ?array
    {
        $path = $this->normalizeStoredFilePath($path);
        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return [
                    'disk' => $disk,
                    'path' => $path,
                    'absolute_path' => Storage::disk($disk)->path($path),
                ];
            }
        }

        return null;
    }

    private function deleteSensitiveFile(?string $path): void
    {
        $path = $this->normalizeStoredFilePath($path);
        if ($path === '') {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function normalizeStoredFilePath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0")) {
            return '';
        }

        $segments = explode('/', $path);
        if (in_array('..', $segments, true)) {
            return '';
        }

        return ltrim($path, '/');
    }

    private function sensitiveFileResponse(string $absolutePath, ?string $fallbackMimeType = null): BinaryFileResponse
    {
        $mimeType = mime_content_type($absolutePath) ?: $fallbackMimeType ?: 'application/octet-stream';

        return response()->file($absolutePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=60',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function requestRefund(Request $request, string $orderCode): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'in:wrong_item,damaged_item,late_delivery,duplicate_payment,other'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($orderCode));

        $order = Order::with('payments')
            ->where('order_code', $normalizedOrderCode)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $payment = $order->payments()->latest('id')->first();
        if (! $payment) {
            return back()->with('error', 'Data pembayaran tidak ditemukan untuk pengajuan refund.');
        }

        if ($order->payment_status === 'refunded' || $payment->status === 'refunded') {
            return back()->with('error', 'Pesanan ini sudah berstatus refunded.');
        }

        if ($order->payment_status !== 'paid' || $payment->status !== 'paid') {
            return back()->with('error', 'Refund hanya dapat diajukan setelah pembayaran lunas.');
        }

        $existingNotes = (string) ($payment->notes ?? '');
        if (Str::contains($existingNotes, '[REFUND_REQUEST_PENDING]')) {
            return back()->with('error', 'Pengajuan refund untuk pesanan ini sudah terkirim dan sedang diproses admin.');
        }

        $reasonLabel = match ($validated['reason']) {
            'wrong_item' => 'Barang tidak sesuai pesanan',
            'damaged_item' => 'Barang rusak/cacat',
            'late_delivery' => 'Pengiriman terlalu lama',
            'duplicate_payment' => 'Pembayaran ganda',
            default => 'Alasan lainnya',
        };

        $details = trim((string) ($validated['details'] ?? ''));
        $refundNoteParts = [
            '[REFUND_REQUEST_PENDING] Permintaan refund diajukan pelanggan.',
            'Alasan: ' . $reasonLabel . '.',
        ];

        if ($details !== '') {
            $refundNoteParts[] = 'Detail pelanggan: ' . $details;
        }

        $newNotes = trim($existingNotes);
        if ($newNotes !== '') {
            $newNotes .= ' | ';
        }
        $newNotes .= implode(' ', $refundNoteParts);

        $payment->update([
            'notes' => $newNotes,
        ]);

        $this->notifyAdminsWhenEnabled(
            'notif_order_paid',
            new AdminRefundRequestedNotification($order, $payment, $reasonLabel, $details),
        );

        return back()->with('success', 'Pengajuan refund berhasil dikirim. Admin akan meninjau permintaan Anda.');
    }

    public function regenerateBayarGgPaymentLink(Request $request, string $orderCode): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($orderCode));

        $order = Order::with('payments')
            ->where('order_code', $normalizedOrderCode)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($order->status === 'cancelled') {
            return back()->with('error', 'Pesanan dibatalkan, link Bayar.gg tidak dapat dibuat ulang.');
        }

        $payment = $order->payments()->latest('id')->first();
        if (! $payment || $payment->method !== 'bayargg') {
            return back()->with('error', 'Pesanan ini tidak menggunakan metode Bayar.gg.');
        }

        if ($payment->status === 'paid' || $order->payment_status === 'paid') {
            return back()->with('success', 'Pembayaran sudah lunas. Tidak perlu membuat ulang link Bayar.gg.');
        }

        $replacementPayment = $order->payments()->create([
            'payment_code' => $this->generatePaymentCode(),
            'method' => 'bayargg',
            'gateway_provider' => 'bayargg',
            'amount' => (int) $order->total_amount,
            'status' => 'pending',
            'paid_at' => null,
            'notes' => 'Link pembayaran Bayar.gg dibuat ulang atas permintaan pelanggan.',
        ]);

        $isCreated = $this->syncBayarGgPaymentLink($order, $replacementPayment);
        if (! $isCreated) {
            return back()->with('error', 'Link Bayar.gg belum berhasil dibuat. Silakan cek konfigurasi API key/webhook secret dan coba lagi.');
        }

        return back()->with('success', 'Link Bayar.gg berhasil diperbarui. Lanjutkan pembayaran sekarang.');
    }

    private function syncBayarGgPaymentLink(Order $order, Payment $payment): bool
    {
        if ($payment->method !== 'bayargg') {
            return false;
        }

        if (! $this->bayarGgGatewayService->isConfigured()) {
            $payment->update([
                'notes' => 'Link Bayar.gg gagal dibuat: konfigurasi API belum lengkap.',
            ]);

            return false;
        }

        try {
            $gatewayPayment = $this->bayarGgGatewayService->createPayment($order, $payment);
        } catch (\Throwable $exception) {
            report($exception);

            $payment->update([
                'notes' => 'Link Bayar.gg gagal dibuat otomatis. Gunakan tombol buat link ulang dari halaman tracking.',
            ]);

            return false;
        }

        $gatewayStatus = $gatewayPayment['gateway_status'];
        $isPaid = $gatewayStatus === 'paid';

        $payment->update([
            'gateway_provider' => 'bayargg',
            'gateway_invoice_id' => $gatewayPayment['invoice_id'],
            'gateway_payment_url' => $gatewayPayment['payment_url'],
            'gateway_status' => $gatewayStatus,
            'gateway_expires_at' => $gatewayPayment['expires_at'],
            'gateway_payload' => $gatewayPayment['payload'],
            'status' => $isPaid ? 'paid' : 'pending',
            'paid_at' => $isPaid ? now() : null,
            'notes' => $isPaid
                ? 'Pembayaran Bayar.gg sudah lunas.'
                : 'Pembayaran via Bayar.gg. Buka link pembayaran untuk melanjutkan transaksi.',
        ]);

        $order->update([
            'payment_status' => $isPaid ? 'paid' : 'pending',
            'paid_at' => $isPaid ? now() : null,
        ]);

        return true;
    }

    private function generateOrderCode(): string
    {
        do {
            $code = 'ORD-ARIP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Order::query()->where('order_code', $code)->exists());

        return $code;
    }

    private function generatePaymentCode(): string
    {
        do {
            $code = 'PAY-ARIP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Payment::query()->where('payment_code', $code)->exists());

        return $code;
    }

    private function generateWarrantyClaimCode(): string
    {
        do {
            $code = 'WRN-ARIP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (WarrantyClaim::query()->where('claim_code', $code)->exists());

        return $code;
    }

    private function cartSummary(Request $request): array
    {
        $simpleCart = $request->session()->get('simple_cart', []);

        return [
            'cartItemCount' => count($simpleCart),
            'cartQuantity' => array_sum(array_column($simpleCart, 'qty')),
        ];
    }

    private function shippingCostPerItem(): int
    {
        if (!Schema::hasTable('system_settings')) {
            return 5000;
        }

        $rawValue = (string) Setting::get('shipping_cost_per_item', '5000');
        $normalized = preg_replace('/[^0-9]/', '', $rawValue);

        if ($normalized === null || $normalized === '') {
            return 5000;
        }

        return max(0, (int) $normalized);
    }

    private function calculateShippingCost(int $totalQuantity): int
    {
        return max(0, $totalQuantity) * $this->shippingCostPerItem();
    }

    private function notifyAdminsWhenEnabled(string $toggleSettingKey, NotificationMessage $notification): void
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        if (!Setting::get($toggleSettingKey, true)) {
            return;
        }

        if (!Schema::hasTable('notifications')) {
            return;
        }

        $adminRecipients = User::query()
            ->whereHas('roles', fn($query) => $query
                ->where('guard_name', 'web')
                ->whereIn('name', ['super-admin', 'admin']))
            ->get();

        if ($adminRecipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($adminRecipients, $notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function isAllowedNotificationRedirectUrl(string $targetUrl): bool
    {
        if (Str::startsWith($targetUrl, '//')) {
            return false;
        }

        if (Str::startsWith($targetUrl, '/')) {
            return true;
        }

        $parsedUrl = parse_url($targetUrl);
        if (!is_array($parsedUrl)) {
            return false;
        }

        $scheme = strtolower((string) ($parsedUrl['scheme'] ?? ''));
        $host = strtolower((string) ($parsedUrl['host'] ?? ''));

        if ($scheme === '' || $host === '') {
            return false;
        }

        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $allowedHosts = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower((string) parse_url((string) url('/'), PHP_URL_HOST)),
        ]);

        return in_array($host, $allowedHosts, true);
    }

    private function hasAddressFormInput(array $validated): bool
    {
        $fields = [
            'address_label',
            'recipient_name',
            'address_phone',
            'address_line',
            'city',
            'province',
            'postal_code',
            'address_notes',
        ];

        foreach ($fields as $field) {
            if (filled($validated[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    public function tracking(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,pending,processing,shipped,completed,cancelled'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $prefilledOrderCode = strtoupper(trim((string) $request->query('order_code', '')));
        if ($prefilledOrderCode !== '') {
            return redirect()->route('home.tracking.show', $prefilledOrderCode);
        }

        $status = $filters['status'] ?? 'all';

        $orders = Order::query()
            ->with([
                'items',
                'payments' => fn($query) => $query->latest(),
                'address',
            ])
            ->where('user_id', $user->id)
            ->when($status !== 'all', fn($query) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('order_code', 'like', '%' . $keyword . '%')
                        ->orWhereHas('items', fn($itemQuery) => $itemQuery->where('product_name', 'like', '%' . $keyword . '%'));
                });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('home.tracking', [
            'orders' => $orders,
            'filters' => $filters,
            ...$this->cartSummary($request),
        ]);
    }

    public function checkTracking(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:255'],
        ]);

        $normalizedOrderCode = strtoupper(trim($validated['order_code']));

        return redirect()->route('home.tracking.show', $normalizedOrderCode);
    }

    public function showTracking(Request $request, string $orderCode): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($orderCode));

        $order = Order::with(['items', 'payments', 'address'])
            ->where('order_code', $normalizedOrderCode)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return redirect()->route('home.tracking')
                ->with('error', 'Pesanan tidak ditemukan di akun Anda. Pastikan kode pesanan benar.');
        }

        $shippingAddress = $order->address
            ? implode(', ', array_filter([
                $order->address->address_line,
                $order->address->city,
                $order->address->province,
                $order->address->postal_code,
            ]))
            : '-';

        return view('home.tracking_result', [
            'order' => $order,
            'shippingAddress' => $shippingAddress,
            ...$this->cartSummary($request),
        ]);
    }
}
