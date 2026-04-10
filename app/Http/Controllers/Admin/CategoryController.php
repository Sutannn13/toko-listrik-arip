<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\UniqueSlugGenerator;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::latest()->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        // Tampilkan halaman form
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        // 1. Validasi: Nama wajib diisi dan nggak boleh kembar
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        // 2. Simpan ke database
        Category::create([
            'name' => $validated['name'],
            'slug' => UniqueSlugGenerator::make(Category::class, $validated['name']),
        ]);

        // 3. Tendang balik ke halaman tabel dengan pesan sukses
        return redirect()->route('admin.categories.index')->with('success', 'Kategori berhasil ditambahkan!');
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
