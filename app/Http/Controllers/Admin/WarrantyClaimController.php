<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyClaim;
use App\Notifications\WarrantyClaimStatusUpdatedNotification;
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
            'electronic' => ['nullable', 'in:all,electronic,non_electronic'],
            'age_bucket' => ['nullable', 'in:all,0_2d,3_7d,gt_7d,sla_overdue'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $now = now();

        $claims = WarrantyClaim::query()
            ->with([
                'order:id,order_code,status,payment_status',
                'orderItem:id,order_id,product_id,product_name,quantity,warranty_days,warranty_expires_at',
                'orderItem.product:id,name,is_electronic',
                'user:id,name,email',
                'activities' => fn($query) => $query->latest(),
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
            ->when($filters['electronic'] ?? null, function ($query, $electronic) {
                if ($electronic === 'electronic') {
                    $query->whereHas('orderItem.product', fn($productQuery) => $productQuery->where('is_electronic', true));
                }

                if ($electronic === 'non_electronic') {
                    $query->whereHas('orderItem.product', fn($productQuery) => $productQuery->where('is_electronic', false));
                }
            })
            ->when($filters['age_bucket'] ?? null, function ($query, $ageBucket) use ($now) {
                if ($ageBucket === '0_2d') {
                    $query->whereRaw('COALESCE(requested_at, created_at) >= ?', [$now->copy()->subHours(48)]);
                }

                if ($ageBucket === '3_7d') {
                    $query->whereRaw('COALESCE(requested_at, created_at) < ?', [$now->copy()->subHours(48)])
                        ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$now->copy()->subDays(7)]);
                }

                if ($ageBucket === 'gt_7d') {
                    $query->whereRaw('COALESCE(requested_at, created_at) < ?', [$now->copy()->subDays(7)]);
                }

                if ($ageBucket === 'sla_overdue') {
                    $query->whereIn('status', ['submitted', 'reviewing'])
                        ->whereRaw('COALESCE(requested_at, created_at) < ?', [$now->copy()->subHours(48)]);
                }
            })
            ->when($filters['date_from'] ?? null, fn($query, $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn($query, $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $claims->getCollection()->transform(function (WarrantyClaim $claim) use ($now) {
            $submittedAt = $claim->requested_at ?? $claim->created_at;
            $slaDeadline = $submittedAt?->copy()->addHours(48);
            $isSlaOpenStatus = in_array($claim->status, ['submitted', 'reviewing'], true);
            $isSlaOverdue = $isSlaOpenStatus && $slaDeadline && $now->greaterThan($slaDeadline);

            $claim->setAttribute('sla_deadline', $slaDeadline);
            $claim->setAttribute('is_sla_overdue', $isSlaOverdue);
            $claim->setAttribute('claim_age_hours', $submittedAt ? (int) $submittedAt->diffInHours($now) : 0);

            return $claim;
        });

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
            'orderItem.product:id,name,is_electronic',
            'activities.actor:id,name,email',
        ]);

        $submittedAt = $warrantyClaim->requested_at ?? $warrantyClaim->created_at;
        $slaDeadline = $submittedAt?->copy()->addHours(48);
        $isSlaOpenStatus = in_array($warrantyClaim->status, ['submitted', 'reviewing'], true);

        $warrantyClaim->setAttribute('sla_deadline', $slaDeadline);
        $warrantyClaim->setAttribute('is_sla_overdue', $isSlaOpenStatus && $slaDeadline && now()->greaterThan($slaDeadline));
        $warrantyClaim->setAttribute('claim_age_hours', $submittedAt ? (int) $submittedAt->diffInHours(now()) : 0);

        return view('admin.warranty_claims.show', compact('warrantyClaim'));
    }

    public function updateStatus(Request $request, WarrantyClaim $warrantyClaim): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:submitted,reviewing,approved,rejected,resolved'],
            'admin_notes' => ['nullable', 'string', 'max:1000', 'required_if:status,rejected'],
        ], [
            'admin_notes.required_if' => 'Alasan admin wajib diisi saat klaim ditolak (rejected).',
        ]);

        $beforeStatus = $warrantyClaim->status;
        $activityNote = trim((string) ($validated['admin_notes'] ?? ''));

        $warrantyClaim->status = $validated['status'];
        $warrantyClaim->admin_notes = $activityNote !== '' ? $activityNote : null;

        if ($warrantyClaim->status === 'resolved') {
            $warrantyClaim->resolved_at = now();
        }

        if (in_array($warrantyClaim->status, ['submitted', 'reviewing', 'approved'], true)) {
            $warrantyClaim->resolved_at = null;
        }

        $warrantyClaim->save();

        $actor = $request->user();
        $activityAction = $beforeStatus === $warrantyClaim->status
            ? 'note_updated'
            : 'status_' . $warrantyClaim->status;

        $warrantyClaim->activities()->create([
            'actor_id' => $actor?->id,
            'actor_name' => $actor?->name,
            'action' => $activityAction,
            'from_status' => $beforeStatus,
            'to_status' => $warrantyClaim->status,
            'note' => $activityNote !== '' ? $activityNote : 'Status klaim diperbarui oleh admin.',
        ]);

        if ($warrantyClaim->user) {
            $warrantyClaim->user->notify(new WarrantyClaimStatusUpdatedNotification($warrantyClaim));
        }

        return redirect()->route('admin.warranty-claims.index')
            ->with('success', 'Status klaim garansi berhasil diperbarui.');
    }
}
