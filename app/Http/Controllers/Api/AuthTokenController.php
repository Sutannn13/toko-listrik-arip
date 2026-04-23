<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AuthTokenStoreRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthTokenController extends Controller
{
    public function store(AuthTokenStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check((string) $validated['password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Email atau password tidak valid.',
            ], 422);
        }

        if ($user->isSuspended()) {
            return response()->json([
                'message' => 'Akun Anda sedang disuspend. Hubungi admin untuk bantuan.',
            ], 403);
        }

        $tokenName = (string) ($validated['device_name'] ?? 'mobile-app');

        if (config('sanctum.revoke_on_login', true)) {
            $user->tokens()->delete();
        }

        $expirationMinutes = (int) config('sanctum.expiration', 0);
        $expiresAt = $expirationMinutes > 0 ? now()->addMinutes($expirationMinutes) : null;

        $newToken = $user->createToken($tokenName, ['*'], $expiresAt);
        $plainTextToken = $newToken->plainTextToken;

        return response()->json([
            'message' => 'Token API berhasil dibuat.',
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $plainTextToken,
                'expires_at' => $newToken->accessToken->expires_at?->toISOString(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        if ($request->boolean('all')) {
            $authenticatedUser->tokens()->delete();

            return response()->json([
                'message' => 'Semua token API berhasil dicabut.',
            ]);
        }

        $currentToken = $authenticatedUser->currentAccessToken();
        if ($currentToken instanceof PersonalAccessToken) {
            $currentToken->delete();
        }

        return response()->json([
            'message' => 'Token API berhasil dicabut.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $authenticatedUser = $request->user();
        if (! $authenticatedUser instanceof User) {
            return response()->json([
                'message' => 'Autentikasi tidak valid.',
            ], 401);
        }

        return response()->json([
            'message' => 'Profil user berhasil diambil.',
            'data' => [
                'id' => $authenticatedUser->id,
                'name' => $authenticatedUser->name,
                'email' => $authenticatedUser->email,
                'primary_role' => $authenticatedUser->primaryRole(),
                'roles' => $authenticatedUser->getRoleNames()->values(),
                'is_suspended' => (bool) $authenticatedUser->is_suspended,
                'email_verified_at' => $authenticatedUser->email_verified_at?->toISOString(),
            ],
        ]);
    }
}
