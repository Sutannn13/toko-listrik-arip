<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PasswordResetLinkController extends Controller
{
    private const GENERIC_RESET_LINK_MESSAGE = 'Jika email terdaftar, tautan reset password akan dikirim ke email tersebut.';

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            Password::sendResetLink($request->only('email'));
        } catch (Throwable $exception) {
            report($exception);
        }

        // Always return a generic response to avoid revealing whether the email exists.
        return back()->with('status', self::GENERIC_RESET_LINK_MESSAGE);
    }
}
