<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Atur Ulang Password</h1>
        <p class="mt-1 text-sm text-gray-500">Masukkan password baru untuk mengamankan akun Anda.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" data-ui-form>
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)"
                required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center" data-loading-text="Menyimpan password...">
                Simpan Password Baru
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
