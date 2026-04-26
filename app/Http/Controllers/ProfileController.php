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
            $this->deleteProfilePhoto($existingPhotoPath);
            $user->profile_photo_path = null;
            $existingPhotoPath = '';
        }

        if ($request->hasFile('profile_photo')) {
            if ($existingPhotoPath !== '') {
                $this->deleteProfilePhoto($existingPhotoPath);
            }

            $storedPath = $request->file('profile_photo')->store('profile-photos', 'local');
            $user->profile_photo_path = str_replace('\\', '/', (string) $storedPath);
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function photo(Request $request, User $user): BinaryFileResponse
    {
        $authUser = $request->user();

        $isAdmin = $authUser?->hasAnyRole(['super-admin', 'admin']) ?? false;
        $ownsPhoto = $authUser && (int) $authUser->id === (int) $user->id;

        if (! $ownsPhoto && ! $isAdmin) {
            abort(403);
        }

        $photoFile = $this->profilePhotoLocation($user->profile_photo_path);
        if ($photoFile === null) {
            abort(404);
        }

        $mimeType = mime_content_type($photoFile['absolute_path']) ?: 'application/octet-stream';

        return response()->file($photoFile['absolute_path'], [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
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
            $this->deleteProfilePhoto($photoPath);
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * @return array{disk: string, path: string, absolute_path: string}|null
     */
    private function profilePhotoLocation(?string $path): ?array
    {
        $path = $this->normalizeStoredFilePath($path);
        if ($path === '') {
            return null;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return [
                    'disk' => $disk,
                    'path' => $path,
                    'absolute_path' => Storage::disk($disk)->path($path),
                ];
            }
        }

        return null;
    }

    private function deleteProfilePhoto(?string $path): void
    {
        $path = $this->normalizeStoredFilePath($path);
        if ($path === '') {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function normalizeStoredFilePath(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));

        if ($path === '' || str_starts_with($path, '/') || str_contains($path, "\0")) {
            return '';
        }

        $segments = explode('/', $path);
        if (in_array('..', $segments, true)) {
            return '';
        }

        return ltrim($path, '/');
    }
}
