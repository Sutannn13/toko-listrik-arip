<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $admin = $request->user();
        abort_unless($admin, 403);

        if (!Schema::hasTable('notifications')) {
            $notifications = new LengthAwarePaginator([], 0, 20, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return view('admin.notifications.index', [
                'notifications' => $notifications,
            ]);
        }

        $notifications = $admin->notifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.notifications.index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin, 403);

        if (!Schema::hasTable('notifications')) {
            return redirect()->route('admin.notifications.index')
                ->with('error', 'Tabel notifikasi belum tersedia. Jalankan migrasi database terlebih dahulu.');
        }

        $admin->unreadNotifications->markAsRead();

        return redirect()->route('admin.notifications.index')
            ->with('success', 'Semua notifikasi admin ditandai sudah dibaca.');
    }
}
