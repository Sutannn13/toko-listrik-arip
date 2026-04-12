<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Toko HS ELECTRIC') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-slate-50 font-sans text-gray-900 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden">
        <div class="absolute -top-24 -left-24 h-96 w-96 rounded-full bg-primary-100/60 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 h-80 w-80 rounded-full bg-primary-50/80 blur-3xl"></div>
    </div>

    <div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-4 py-6 sm:px-6">
        <div class="mb-6">
            <a href="{{ route('home') }}" class="inline-flex items-center">
                <img src="{{ asset('img/gemini_generated_image.png') }}" alt="Toko HS ELECTRIC"
                    class="h-12 w-auto object-contain sm:h-14">
            </a>
        </div>

        <div
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white px-6 py-6 shadow-lg shadow-gray-200/50 sm:px-8 sm:py-8">
            {{ $slot }}
        </div>
    </div>

    @include('auth.partials.form-micro-interactions')
</body>

</html>
