<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Hasil Lacak Pesanan {{ $order->order_code }} - Toko Listrik Arip</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 font-sans text-gray-800 antialiased selection:bg-primary-500 selection:text-white flex flex-col">
    <!-- Overlay Design Elements -->
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-32 left-0 h-96 w-96 rounded-full bg-primary-100/40 blur-3xl"></div>
    </div>

    <div class="relative z-10 flex-1 flex flex-col">
        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white shadow-sm">
            <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('landing') }}" class="flex items-center gap-3 transition-transform hover:scale-105">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 text-sm font-extrabold text-white shadow-md shadow-primary-500/30">TA</span>
                    <div>
                        <p class="text-sm font-bold tracking-widest text-primary-600 uppercase">Toko Listrik Arip</p>
                        <p class="text-[10px] font-medium text-gray-400">Hasil Pelacakan</p>
                    </div>
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('home.tracking') }}" class="hidden sm:inline-flex rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:border-primary-500 hover:text-primary-600 hover:bg-gray-50">
                        Cek Nomor Resi Lain
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-4 py-8 sm:px-6 lg:px-8 flex-1">
            
            <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-extrabold text-gray-900 flex items-center gap-2">
                        Status Pesanan 
                        <span class="inline-block rounded-md bg-gray-100 px-2 py-1 text-sm font-mono text-gray-700 border border-gray-200">{{ $order->order_code }}</span>
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Dipesan pada {{ optional($order->placed_at)->format('d F Y H:i') ?? $order->created_at->format('d F Y H:i') }}
                    </p>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-6 rounded-xl border border-green-200 bg-green-50 p-4 flex gap-3 text-sm text-green-700 items-start">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 flex gap-3 text-sm text-red-700 items-start">
                    <p>{{ session('error') }}</p>
                </div>
            @endif

            <!-- Resi Display -->
            @if($order->tracking_number)
                <div class="mb-4 rounded-xl border border-primary-200 bg-primary-50 p-4 flex items-center justify-between shadow-sm">
                    <div>
                        <p class="text-xs font-bold text-primary-700 uppercase tracking-wider mb-1">Nomor Resi Pengiriman</p>
                        <p class="text-lg font-mono font-bold text-gray-900">{{ $order->tracking_number }}</p>
                    </div>
                </div>
            @endif

            <!-- Payment Proof Form -->
            @if($order->status !== 'cancelled' && $order->payment_status !== 'paid')
                @php
                    $latestPayment = $order->payments->sortByDesc('created_at')->first();
                @endphp
                @if($latestPayment && !$latestPayment->proof_url)
                    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-blue-900 mb-2">Segera Upload Bukti Pembayaran</h3>
                        <p class="text-xs text-blue-700 mb-4">Transfer sebesar <strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong> ke <b>BCA 12345678 a/n Arip</b>, lalu unggah bukti di sini agar pesanan diproses.</p>
                        
                        <form action="{{ route('home.tracking.proof', $order->order_code) }}" method="POST" enctype="multipart/form-data" class="flex flex-col sm:flex-row gap-3">
                            @csrf
                            <input type="file" name="payment_proof" accept="image/*" required class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 transition bg-white">
                            <button type="submit" class="whitespace-nowrap rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-blue-700 shadow-md">
                                Unggah Bukti
                            </button>
                        </form>
                        @error('payment_proof')
                           <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @elseif($latestPayment && $latestPayment->proof_url)
                    <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 shadow-sm flex items-center gap-4">
                        <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="text-sm font-bold text-blue-900">Bukti Transfer Berhasil Diunggah</p>
                            <p class="text-xs text-blue-700 mt-0.5">Admin sedang memverifikasi pembayaran Anda.</p>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Status Banner -->
            <div class="mb-8 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-4 w-full sm:w-auto">
                    @if($order->status === 'completed')
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-green-600">Pesanan Selesai</p>
                            <p class="text-sm font-medium text-gray-900">Barang telah diterima oleh pelanggan.</p>
                        </div>
                    @elseif($order->status === 'cancelled')
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-red-600">Dibatalkan</p>
                            <p class="text-sm font-medium text-gray-900">Pesanan ini telah dibatalkan.</p>
                        </div>
                    @else
                        <!-- Processing Status -->
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Sedang Diproses</p>
                            <p class="text-sm font-medium text-gray-900">Pesanan Anda dalam tahap ({{ $order->status }}).</p>
                        </div>
                    @endif
                </div>

                <div class="w-full sm:w-auto p-4 rounded-xl {{ $order->payment_status === 'paid' ? 'bg-green-50 border border-green-200' : 'bg-orange-50 border border-orange-200' }}">
                     <p class="text-[10px] uppercase font-bold text-gray-500 mb-1">Status Pembayaran</p>
                     @if($order->payment_status === 'paid')
                        <p class="text-sm font-bold text-green-700 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Lunas (Paid)</p>
                     @else
                        <p class="text-sm font-bold text-orange-700 flex items-center gap-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Belum Lunas ({{ $order->payment_status }})</p>
                     @endif
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Customer & Address Info -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 uppercase tracking-wider">Informasi Pengiriman</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Nama Tujuan</p>
                                <p class="text-sm font-semibold text-gray-900">{{ $order->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Kontak</p>
                                <p class="text-sm font-medium text-gray-900">{{ $order->customer_phone }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $order->customer_email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-0.5">Alamat Lengkap</p>
                                <p class="text-sm font-medium text-gray-900 leading-relaxed">{{ $shippingAddress }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="lg:col-span-2">
                    <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm filter flex flex-col h-full">
                        <h3 class="text-sm font-bold text-gray-900 border-b border-gray-100 pb-3 mb-4 uppercase tracking-wider">Daftar Produk</h3>
                        
                        <div class="space-y-4 flex-1">
                            @foreach($order->items as $item)
                                <div class="flex justify-between items-start border-b border-gray-50 pb-4 last:border-0 last:pb-0">
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $item->product_name }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ number_format($item->quantity) }} x Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-extrabold text-gray-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="mt-6 border-t border-gray-200 pt-4 space-y-2">
                             <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Total Item</span>
                                <span class="font-bold text-gray-900">{{ number_format($order->items->sum('quantity')) }}</span>
                             </div>
                             <div class="flex justify-between text-base items-center pt-2">
                                <span class="font-bold text-gray-900">Total Pembayaran</span>
                                <span class="text-xl font-black text-primary-600">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 text-center">
                 <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-xl bg-gray-900 px-6 py-3 text-sm font-bold text-white transition hover:bg-gray-800 shadow-md">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                      Kembali ke Beranda
                 </a>
            </div>

        </main>
        
        <footer class="mt-auto bg-gray-900 py-6 text-center text-gray-400">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p class="text-sm">&copy; {{ date('Y') }} Toko Listrik Arip. Hak Cipta Dilindungi.</p>
            </div>
        </footer>
    </div>
</body>
</html>
