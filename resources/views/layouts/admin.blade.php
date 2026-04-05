<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - Toko Listrik Arip</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="font-sans antialiased bg-gray-100">
    <div class="flex h-screen overflow-hidden">

        <aside class="w-64 bg-gray-900 text-white flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-800">
                <span class="text-xl font-bold uppercase tracking-wider">Toko Arip</span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('admin.dashboard') }}"
                            class="block px-6 py-3 hover:bg-gray-800 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-800 border-l-4 border-blue-500' : '' }}">
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="#"
                            class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Kategori Produk</a>
                    </li>
                    <li>
                        <a href="#"
                            class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Barang Listrik</a>
                    </li>
                    <li>
                        <a href="#"
                            class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Pesanan</a>
                    </li>
                </ul>
            </nav>
            <div class="p-4 border-t border-gray-800">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left px-4 py-2 bg-red-600 hover:bg-red-700 rounded text-sm transition-colors">
                        Logout System
                    </button>
                </form>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center justify-between px-6">
                <h2 class="text-xl font-semibold text-gray-800">
                    @yield('header', 'Ruang Kendali')
                </h2>
                <div class="text-sm text-gray-600">
                    Login sebagai: <span class="font-bold">{{ Auth::user()->name }}</span>
                </div>
            </header>

            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
                @yield('content')
            </main>
        </div>

    </div>
</body>

</html>
