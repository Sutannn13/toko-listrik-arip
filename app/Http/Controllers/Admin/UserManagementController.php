<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('roles')->latest();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        if ($status = $request->get('status')) {
            if ($status === 'suspended') {
                $query->where('is_suspended', true);
            } elseif ($status === 'active') {
                $query->where('is_suspended', false);
            }
        }

        $users = $query->paginate(20)->withQueryString();
        $roles  = Role::orderBy('name')->get();
        $counts = [
            'total'     => User::count(),
            'active'    => User::where('is_suspended', false)->count(),
            'suspended' => User::where('is_suspended', true)->count(),
        ];

        return view('admin.users.index', compact('users', 'roles', 'counts'));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        // Prevent demoting yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kamu tidak bisa mengubah role milikmu sendiri.');
        }

        $validated = $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user->syncRoles([$validated['role']]);

        return back()->with('success', "Role {$user->name} diubah menjadi {$validated['role']}.");
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Kamu tidak bisa mensuspend akun sendiri.');
        }

        if ($user->hasRole('super-admin')) {
            return back()->with('error', 'Akun Super Admin tidak bisa disuspend.');
        }

        $validated = $request->validate([
            'suspended_reason' => 'nullable|string|max:500',
        ]);

        $user->update([
            'is_suspended'     => true,
            'suspended_at'     => now(),
            'suspended_reason' => $validated['suspended_reason'] ?? null,
        ]);

        return back()->with('success', "Akun \"{$user->name}\" berhasil disuspend.");
    }

    public function unsuspend(User $user): RedirectResponse
    {
        $user->update([
            'is_suspended'     => false,
            'suspended_at'     => null,
            'suspended_reason' => null,
        ]);

        return back()->with('success', "Akun \"{$user->name}\" berhasil diaktifkan kembali.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Gunakan halaman Profile untuk mengubah password sendiri.');
        }

        $validated = $request->validate([
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
        ]);

        $user->update(['password' => Hash::make($validated['new_password'])]);

        return back()->with('success', "Password \"{$user->name}\" berhasil direset.");
    }
}
