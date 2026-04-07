<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarrantyClaimController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:submitted,reviewing,approved,rejected,resolved'],
            'payment_status' => ['nullable', 'in:pending,paid,failed,refunded'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $claims = WarrantyClaim::query()
            ->with([
                'order:id,order_code,status,payment_status',
                'orderItem:id,order_id,product_name,quantity,warranty_expires_at',
                'user:id,name,email',
            ])
            ->when($filters['q'] ?? null, function ($query, $keyword) {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('claim_code', 'like', '%' . $keyword . '%')
                        ->orWhere('reason', 'like', '%' . $keyword . '%')
                        ->orWhereHas('order', fn($orderQuery) => $orderQuery->where('order_code', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('orderItem', fn($itemQuery) => $itemQuery->where('product_name', 'like', '%' . $keyword . '%'))
                        ->orWhereHas('user', fn($userQuery) => $userQuery
                            ->where('name', 'like', '%' . $keyword . '%')
                            ->orWhere('email', 'like', '%' . $keyword . '%'));
                });
            })
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, function ($query, $paymentStatus) {
                $query->whereHas('order', fn($orderQuery) => $orderQuery->where('payment_status', $paymentStatus));
            })
            ->when($filters['date_from'] ?? null, fn($query, $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn($query, $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.warranty_claims.index', [
            'claims' => $claims,
            'filters' => $filters,
        ]);
    }

    public function show(WarrantyClaim $warrantyClaim): View
    {
        $warrantyClaim->load([
            'user:id,name,email',
            'order.user:id,name,email',
            'order.address',
            'orderItem',
            'activities.actor:id,name,email',
        ]);

        return view('admin.warranty_claims.show', compact('warrantyClaim'));
    }

    public function updateStatus(Request $request, WarrantyClaim $warrantyClaim): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:submitted,reviewing,approved,rejected,resolved'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $beforeStatus = $warrantyClaim->status;

        $warrantyClaim->status = $validated['status'];
        $warrantyClaim->admin_notes = $validated['admin_notes'] ?? null;

        if ($warrantyClaim->status === 'resolved') {
            $warrantyClaim->resolved_at = now();
        }

        if (in_array($warrantyClaim->status, ['submitted', 'reviewing', 'approved'], true)) {
            $warrantyClaim->resolved_at = null;
        }

        $warrantyClaim->save();

        $actor = $request->user();
        $activityAction = $beforeStatus === $warrantyClaim->status ? 'note_updated' : 'status_updated';
        $activityNote = trim((string) ($validated['admin_notes'] ?? ''));

        $warrantyClaim->activities()->create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'action' => $activityAction,
            'from_status' => $beforeStatus,
            'to_status' => $warrantyClaim->status,
            'note' => $activityNote !== '' ? $activityNote : null,
        ]);

        return redirect()->route('admin.warranty-claims.index')
            ->with('success', 'Status klaim garansi berhasil diperbarui.');
    }
}
