<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\WarrantyClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function landing(Request $request): View
    {
        $featuredCategories = Category::query()
            ->withCount([
                'products as active_products_count' => fn($query) => $query->where('is_active', true),
            ])
            ->orderByDesc('active_products_count')
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('home.landing', [
            'featuredCategories' => $featuredCategories,
            'totalProducts' => Product::where('is_active', true)->count(),
            'totalCategories' => Category::count(),
            ...$this->cartSummary($request),
        ]);
    }

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
            ->paginate(8)
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
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn($query) => $query->where('category_id', $product->category_id))
            ->latest()
            ->limit(4)
            ->get();

        return view('home.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            ...$this->cartSummary($request),
        ]);
    }

    public function buy(Request $request, string $slug): RedirectResponse
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        if ($product->stock < 1) {
            return redirect()->route('home.products.show', $product->slug)
                ->with('error', 'Stok produk habis. Silakan pilih produk lain.');
        }

        $validated = $request->validate([
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $qty = (int) ($validated['qty'] ?? 1);
        $simpleCart = $request->session()->get('simple_cart', []);
        $existingQty = isset($simpleCart[$product->id]) ? (int) $simpleCart[$product->id]['qty'] : 0;

        if ($existingQty >= (int) $product->stock) {
            return redirect()->route('home.products.show', $product->slug)
                ->with('error', 'Jumlah produk di keranjang sudah mencapai stok tersedia.');
        }

        $newQty = min($existingQty + $qty, (int) $product->stock);

        $simpleCart[$product->id] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'price' => (int) $product->price,
            'unit' => $product->unit,
            'qty' => $newQty,
        ];

        $request->session()->put('simple_cart', $simpleCart);

        return redirect()->route('home.products.show', $product->slug)
            ->with('success', $product->name . ' berhasil ditambahkan ke keranjang sederhana.');
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

        $userAddresses = collect();
        $defaultAddressId = null;

        if ($request->user()) {
            $userAddresses = $request->user()
                ->addresses()
                ->orderByDesc('is_default')
                ->latest()
                ->get();

            $defaultAddressId = $userAddresses->firstWhere('is_default', true)?->id
                ?? $userAddresses->first()?->id;
        }

        return view('home.cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'totalQuantity' => $totalQuantity,
            'userAddresses' => $userAddresses,
            'defaultAddressId' => $defaultAddressId,
            'recentOrders' => $this->recentOrdersForCart($request),
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

        $shippingCost = 0;
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
                return redirect()->route('home.cart')
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
                return redirect()->route('home.cart')
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
            return redirect()->route('home.cart')
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
        ];

        if ($addressSnapshot !== '') {
            $orderNotesParts[] = 'Alamat: ' . $addressSnapshot;
        }

        if (!empty($validated['address_notes'])) {
            $orderNotesParts[] = 'Catatan alamat: ' . $validated['address_notes'];
        }

        $orderNotes = implode(' | ', $orderNotesParts);

        $orderCode = $this->generateOrderCode();
        $paymentCode = $this->generatePaymentCode();

        try {
            $order = DB::transaction(function () use (
                $user,
                $address,
                $customerName,
                $customerEmail,
                $customerPhone,
                $orderNotes,
                $orderCode,
                $paymentCode,
                $subtotal,
                $shippingCost,
                $discountAmount,
                $totalAmount,
                $orderItemsPayload,
            ) {
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

                    $order->items()->create([
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_slug' => $product->slug,
                        'unit' => $product->unit,
                        'price' => $payload['price'],
                        'quantity' => $payload['qty'],
                        'subtotal' => $payload['subtotal'],
                        'warranty_days' => 7,
                        'warranty_expires_at' => now()->addDays(7),
                    ]);

                    $freshProduct->decrement('stock', $payload['qty']);
                }

                $order->payments()->create([
                    'payment_code' => $paymentCode,
                    'method' => 'dummy',
                    'amount' => $totalAmount,
                    'status' => 'pending',
                    'notes' => 'Payment placeholder untuk flow awal sebelum gateway real.',
                ]);

                return $order;
            });
        } catch (\Exception $e) {
            return redirect()->route('home.cart')->with('error', 'Checkout gagal: ' . $e->getMessage());
        }

        $request->session()->forget('simple_cart');

        return redirect()->route('home.cart')->with(
            'success',
            'Checkout berhasil. Kode pesanan: ' . $order->order_code . '. Garansi Toko Arip 7 hari aktif untuk item pesanan.',
        );
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

        if (!$orderItem->warranty_expires_at || $orderItem->warranty_expires_at->isPast()) {
            return redirect()->route('home.cart')
                ->with('error', 'Masa garansi item ini sudah berakhir.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $claim = null;

        try {
            $claim = DB::transaction(function () use ($order, $orderItem, $user, $validated) {
                $lockedItem = OrderItem::query()
                    ->where('id', $orderItem->id)
                    ->where('order_id', $order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedItem) {
                    throw new \RuntimeException('ORDER_ITEM_NOT_FOUND');
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

                $totalClaims = WarrantyClaim::query()
                    ->where('order_item_id', $lockedItem->id)
                    ->lockForUpdate()
                    ->count();

                if ($totalClaims >= 2) {
                    throw new \RuntimeException('CLAIM_LIMIT_REACHED');
                }

                $claim = WarrantyClaim::create([
                    'claim_code' => $this->generateWarrantyClaimCode(),
                    'order_id' => $order->id,
                    'order_item_id' => $lockedItem->id,
                    'user_id' => $user?->id,
                    'reason' => $validated['reason'],
                    'status' => 'submitted',
                    'requested_at' => now(),
                ]);

                $claim->activities()->create([
                    'actor_id' => $user?->id,
                    'actor_name' => $user?->name,
                    'action' => 'submitted',
                    'from_status' => null,
                    'to_status' => 'submitted',
                    'note' => $validated['reason'],
                ]);

                return $claim;
            }, 3);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'ACTIVE_CLAIM_EXISTS') {
                return redirect()->route('home.cart')
                    ->with('error', 'Item ini sudah memiliki klaim garansi aktif.');
            }

            if ($e->getMessage() === 'WARRANTY_EXPIRED') {
                return redirect()->route('home.cart')
                    ->with('error', 'Masa garansi item ini sudah berakhir.');
            }

            if ($e->getMessage() === 'CLAIM_LIMIT_REACHED') {
                return redirect()->route('home.cart')
                    ->with('error', 'Item ini sudah mencapai batas maksimal 2 kali klaim garansi.');
            }

            return redirect()->route('home.cart')
                ->with('error', 'Item pesanan tidak ditemukan atau tidak valid untuk klaim.');
        }

        if (!$claim) {
            return redirect()->route('home.cart')
                ->with('error', 'Klaim garansi gagal diproses. Silakan coba lagi.');
        }

        return redirect()->route('home.cart')->with('success', 'Klaim garansi berhasil diajukan. Tim admin akan meninjau klaim Anda.');
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

        if ($payment->status === 'pending' && !empty($payment->proof_url)) {
            return back()->with('error', 'Bukti pembayaran sudah diunggah. Silakan tunggu verifikasi admin.');
        }

        $path = $request->file('payment_proof')->store('payments/' . $order->order_code, 'public');

        $payment->update([
            'proof_url' => $path,
            'status' => 'pending',
            'paid_at' => null,
            'notes' => 'Bukti pembayaran diunggah ulang oleh pelanggan, menunggu verifikasi admin.',
        ]);

        if ($order->payment_status === 'failed') {
            $order->payment_status = 'pending';
            $order->save();
        }

        return back()->with('success', 'Bukti pembayaran berhasil diunggah. Silakan tunggu konfirmasi admin.');
    }

    private function recentOrdersForCart(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return collect();
        }

        return Order::query()
            ->with([
                'items.warrantyClaims' => fn($claimQuery) => $claimQuery->latest(),
                'payments' => fn($paymentQuery) => $paymentQuery->latest(),
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();
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

    public function tracking(Request $request): View
    {
        return view('home.tracking', [
            ...$this->cartSummary($request),
        ]);
    }

    public function checkTracking(Request $request)
    {
        $validated = $request->validate([
            'order_code' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        if (!$user) {
            abort(403);
        }

        $normalizedOrderCode = strtoupper(trim($validated['order_code']));

        $order = Order::with(['items', 'payments', 'address'])
            ->where('order_code', $normalizedOrderCode)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return redirect()->route('home.tracking')
                ->withInput()
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
