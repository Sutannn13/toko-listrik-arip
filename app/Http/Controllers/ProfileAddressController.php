<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('profile.addresses', [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $shouldDefault = (bool) ($validated['set_as_default'] ?? false) || !$user->addresses()->exists();

        if ($shouldDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        Address::create([
            'user_id' => $user->id,
            'label' => $validated['label'] ?? null,
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'postal_code' => $validated['postal_code'],
            'notes' => $validated['notes'] ?? null,
            'is_default' => $shouldDefault,
        ]);

        return redirect()->route('profile.addresses.index')
            ->with('success', 'Alamat baru berhasil ditambahkan.');
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddressOwner($request, $address);

        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $address->update([
            'label' => $validated['label'] ?? null,
            'recipient_name' => $validated['recipient_name'],
            'phone' => $validated['phone'],
            'address_line' => $validated['address_line'],
            'city' => $validated['city'],
            'province' => $validated['province'],
            'postal_code' => $validated['postal_code'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('profile.addresses.index')
            ->with('success', 'Alamat berhasil diperbarui.');
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddressOwner($request, $address);

        $user = $request->user();
        $isDefault = $address->is_default;

        $address->delete();

        if ($isDefault) {
            $replacement = $user->addresses()->latest()->first();

            if ($replacement) {
                $replacement->update(['is_default' => true]);
            }
        }

        return redirect()->route('profile.addresses.index')
            ->with('success', 'Alamat berhasil dihapus.');
    }

    public function setDefault(Request $request, Address $address): RedirectResponse
    {
        $this->authorizeAddressOwner($request, $address);

        $user = $request->user();
        $user->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return redirect()->route('profile.addresses.index')
            ->with('success', 'Alamat default berhasil diubah.');
    }

    private function authorizeAddressOwner(Request $request, Address $address): void
    {
        if ((int) $address->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
