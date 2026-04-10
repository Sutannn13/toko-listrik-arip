<x-guest-layout>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Konfirmasi Password</h1>
        <p class="mt-1 text-sm text-gray-500">
            Ini area aman. Masukkan password Anda untuk melanjutkan.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" data-ui-form>
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required
                autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-primary-button class="w-full justify-center" data-loading-text="Memverifikasi...">
                Konfirmasi
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
