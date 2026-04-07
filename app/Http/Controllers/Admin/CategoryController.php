<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str; // <-- WAJIB ADA BIAR BISA NGAMBIL DATA KE DATABASE

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
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        // 2. Simpan ke database
        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // Otomatis ubah "Kabel Listrik" jadi "kabel-listrik"
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
