<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\UniqueSlugGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')->latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => UniqueSlugGenerator::make(Category::class, $validated['name'], 'slug'),
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil ditambahkan!');
    }

    public function show(string $id)
    {
        return redirect()->route('admin.categories.edit', $id);
    }

    public function edit(string $id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        // Regenerate slug only if name changed
        $slug = $category->slug;
        if ($validated['name'] !== $category->name) {
            $slug = UniqueSlugGenerator::make(Category::class, $validated['name'], 'slug', $category->id);
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', 'Kategori "' . $validated['name'] . '" berhasil diperbarui!');
    }

    public function destroy(string $id)
    {
        $category = Category::with(['products:id,category_id,image_path'])->findOrFail($id);
        $name = $category->name;
        $deletedProductCount = $category->products->count();

        try {
            foreach ($category->products as $product) {
                if (! empty($product->image_path)) {
                    Storage::disk('public')->delete($product->image_path);
                }
            }

            $category->delete();
        } catch (QueryException $exception) {
            report($exception);

            return redirect()->route('admin.categories.index')
                ->with('error', 'Kategori "' . $name . '" gagal dihapus karena masih dipakai data lain.');
        }

        $successMessage = 'Kategori "' . $name . '" berhasil dihapus.';
        if ($deletedProductCount > 0) {
            $successMessage = 'Kategori "' . $name . '" berhasil dihapus beserta ' . $deletedProductCount . ' produk di dalamnya.';
        }

        return redirect()->route('admin.categories.index')
            ->with('success', $successMessage);
    }
}
