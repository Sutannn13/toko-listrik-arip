<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return view('profile.admin-edit', [
                'user' => $user,
            ]);
        }

        $addresses = $user->addresses()
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'addresses' => $addresses,
            'defaultAddress' => $addresses->firstWhere('is_default', true) ?? $addresses->first(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $existingPhotoPath = str_replace('\\', '/', (string) $user->profile_photo_path);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->boolean('remove_profile_photo') && $existingPhotoPath !== '') {
            Storage::disk('public')->delete($existingPhotoPath);
            $user->profile_photo_path = null;
            $existingPhotoPath = '';
        }

        if ($request->hasFile('profile_photo')) {
            if ($existingPhotoPath !== '') {
                Storage::disk('public')->delete($existingPhotoPath);
            }

            $storedPath = $request->file('profile_photo')->store('profile-photos', 'public');
            $user->profile_photo_path = str_replace('\\', '/', (string) $storedPath);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function photo(Request $request, User $user): BinaryFileResponse
    {
        $authUser = $request->user();

        if (! $authUser || (int) $authUser->id !== (int) $user->id) {
            abort(403);
        }

        $photoPath = str_replace('\\', '/', (string) $user->profile_photo_path);
        if ($photoPath === '' || ! Storage::disk('public')->exists($photoPath)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($photoPath), [
            'Cache-Control' => 'private, max-age=604800',
        ]);
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $photoPath = str_replace('\\', '/', (string) $user->profile_photo_path);

        if ($photoPath !== '') {
            Storage::disk('public')->delete($photoPath);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
