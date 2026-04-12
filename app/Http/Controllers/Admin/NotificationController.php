<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

    public function open(Request $request, string $notification): RedirectResponse
    {
        $admin = $request->user();
        abort_unless($admin, 403);

        if (!Schema::hasTable('notifications')) {
            return redirect()->route('admin.notifications.index')
                ->with('error', 'Tabel notifikasi belum tersedia. Jalankan migrasi database terlebih dahulu.');
        }

        $notificationModel = $admin->notifications()
            ->whereKey($notification)
            ->first();

        abort_unless($notificationModel, 404);

        if ($notificationModel->read_at === null) {
            $notificationModel->markAsRead();
        }

        $payload = is_array($notificationModel->data) ? $notificationModel->data : [];
        $targetUrl = trim((string) ($payload['route'] ?? ''));

        if ($targetUrl === '') {
            return redirect()->route('admin.notifications.index');
        }

        if (Str::startsWith($targetUrl, '/')) {
            return redirect()->to($targetUrl);
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl !== '' && Str::startsWith($targetUrl, $appUrl)) {
            return redirect()->to($targetUrl);
        }

        $targetHost = parse_url($targetUrl, PHP_URL_HOST);
        $currentHost = parse_url((string) url('/'), PHP_URL_HOST);
        if (
            is_string($targetHost) &&
            $targetHost !== '' &&
            is_string($currentHost) &&
            $currentHost !== '' &&
            strcasecmp($targetHost, $currentHost) === 0
        ) {
            return redirect()->to($targetUrl);
        }

        return redirect()->route('admin.notifications.index');
    }
}
