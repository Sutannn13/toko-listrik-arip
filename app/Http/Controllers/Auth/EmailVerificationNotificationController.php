<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $defaultRoute = $request->user()->hasAnyRole(['super-admin', 'admin'])
            ? 'admin.dashboard'
            : 'home';

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($defaultRoute, absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
