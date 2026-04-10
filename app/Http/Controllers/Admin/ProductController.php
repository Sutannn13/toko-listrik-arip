<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\UniqueSlugGenerator;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        // Ambil semua produk beserta relasi kategorinya (Eager Loading biar cepat)
        $products = Product::with('category')->latest()->get();
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        // Ambil semua data kategori buat dimasukin ke dropdown pilihan (Select)
        $categories = Category::all();
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // 1. Validasi super ketat buat inputan angka dan pilihan satuan
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:products,name',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|in:pcs,meter,roll,box',
            'description' => 'nullable|string',
            'is_electronic' => 'nullable|boolean',
        ]);

        // 2. Simpan ke database
        Product::create([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => UniqueSlugGenerator::make(Product::class, $validated['name']),
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'unit' => $validated['unit'],
            'is_active' => true,
            'is_electronic' => (bool) ($validated['is_electronic'] ?? false),
            // (Spesifikasi kita skip dulu untuk form V1 biar lo paham flow dasarnya)
        ]);

        // 3. Tendang balik ke halaman tabel
        return redirect()->route('admin.products.index')->with('success', 'Barang listrik berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
