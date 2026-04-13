<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\UniqueSlugGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:products,name',
            'category_id'  => 'required|exists:categories,id',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'unit'         => 'required|in:pcs,meter,roll,box',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'is_electronic' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'category_id'  => $validated['category_id'],
            'name'         => $validated['name'],
            'slug'         => UniqueSlugGenerator::make(Product::class, $validated['name'], 'slug'),
            'description'  => $validated['description'] ?? null,
            'image_path'   => $imagePath,
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'unit'         => $validated['unit'],
            'is_active'    => (bool) ($validated['is_active'] ?? true),
            'is_electronic' => (bool) ($validated['is_electronic'] ?? false),
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan!');
    }

    public function show(string $id)
    {
        $product = Product::with('category')->findOrFail($id);
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(string $id)
    {
        $product    = Product::findOrFail($id);
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'         => 'required|string|max:255|unique:products,name,' . $product->id,
            'category_id'  => 'required|exists:categories,id',
            'price'        => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'unit'         => 'required|in:pcs,meter,roll,box',
            'description'  => 'nullable|string',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'remove_image' => 'nullable|boolean',
            'is_electronic' => 'nullable|boolean',
            'is_active'    => 'nullable|boolean',
        ]);

        // Regenerate slug only if name changed
        $slug = $product->slug;
        if ($validated['name'] !== $product->name) {
            $slug = UniqueSlugGenerator::make(Product::class, $validated['name'], 'slug', $product->id);
        }

        $newImagePath = $product->image_path;

        if ((bool) ($validated['remove_image'] ?? false) && ! $request->hasFile('image')) {
            if (! empty($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $newImagePath = null;
        }

        if ($request->hasFile('image')) {
            if (! empty($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $newImagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'category_id'  => $validated['category_id'],
            'name'         => $validated['name'],
            'slug'         => $slug,
            'description'  => $validated['description'] ?? null,
            'image_path'   => $newImagePath,
            'price'        => $validated['price'],
            'stock'        => $validated['stock'],
            'unit'         => $validated['unit'],
            'is_active'    => (bool) ($validated['is_active'] ?? false),
            'is_electronic' => (bool) ($validated['is_electronic'] ?? false),
        ]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk "' . $product->name . '" berhasil diperbarui!');
    }

    /**
     * Adjust stock (increment / decrement) — inline quick-action from index table.
     */
    public function adjustStock(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'action' => 'required|in:add,subtract',
            'amount' => 'required|integer|min:1|max:9999',
        ]);

        if ($validated['action'] === 'add') {
            $product->increment('stock', $validated['amount']);
            $msg = "Stok {$product->name} ditambah {$validated['amount']}. Stok sekarang: {$product->fresh()->stock}";
        } else {
            $newStock = max(0, $product->stock - $validated['amount']);
            $product->update(['stock' => $newStock]);
            $msg = "Stok {$product->name} dikurangi. Stok sekarang: {$newStock}";
        }

        return redirect()->route('admin.products.index')->with('success', $msg);
    }

    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $name    = $product->name;

        // Guard: Jangan hapus produk yang sudah pernah dipesan.
        // Ini menjaga integritas data order history dan warranty claims.
        if ($product->orderItems()->exists()) {
            return redirect()->route('admin.products.index')
                ->with('error', "Produk \"{$name}\" tidak bisa dihapus karena sudah memiliki riwayat pesanan. Nonaktifkan saja produk ini.");
        }

        if (! empty($product->image_path)) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', "Produk \"{$name}\" berhasil dihapus.");
    }
}
