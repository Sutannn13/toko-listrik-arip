<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();
        $period30Days = $now->copy()->subDays(30);
        $period7Days = $now->copy()->subDays(6)->startOfDay();
        $trendDays = collect(range(6, 0))->map(fn($offset) => $now->copy()->subDays($offset)->startOfDay());
        $unreadNotificationsCount = 0;

        if (Schema::hasTable('notifications') && $request->user()) {
            $unreadNotificationsCount = $request->user()->unreadNotifications()->count();
        }

        $overview = [
            'total_products' => Product::query()->where('is_active', true)->count(),
            'active_orders' => Order::query()->whereIn('status', ['pending', 'processing', 'shipped'])->count(),
            'total_customers' => User::query()
                ->whereHas('roles', fn($query) => $query
                    ->where('guard_name', 'web')
                    ->where('name', 'user'))
                ->count(),
            'unread_notifications' => $unreadNotificationsCount,
        ];

        $triage = [
            'payments_to_verify' => Order::query()
                ->where('payment_status', 'pending')
                ->whereHas('latestPayment', fn($query) => $query
                    ->whereNotNull('proof_url')
                    ->where('proof_url', '!=', ''))
                ->count(),
            'orders_ready_to_ship' => Order::query()
                ->where('status', 'processing')
                ->where('payment_status', 'paid')
                ->count(),
            'claims_submitted' => WarrantyClaim::query()
                ->where('status', 'submitted')
                ->count(),
            'claims_sla_overdue' => WarrantyClaim::query()
                ->whereIn('status', ['submitted', 'reviewing'])
                ->whereRaw('COALESCE(requested_at, created_at) < ?', [$now->copy()->subHours(48)])
                ->count(),
        ];

        $ordersLast30Days = Order::query()->where('created_at', '>=', $period30Days);

        $totalOrders30Days = (clone $ordersLast30Days)->count();
        $completedOrders30Days = (clone $ordersLast30Days)
            ->where('status', 'completed')
            ->count();

        $paidOrders30Days = (clone $ordersLast30Days)
            ->where('payment_status', 'paid');

        $fulfillmentRate30Days = $totalOrders30Days > 0
            ? round(($completedOrders30Days / $totalOrders30Days) * 100, 1)
            : 0.0;

        $metrics = [
            'paid_revenue_30d' => (int) (clone $paidOrders30Days)->sum('total_amount'),
            'avg_paid_order_value_30d' => (int) round((float) ((clone $paidOrders30Days)->avg('total_amount') ?: 0)),
            'fulfillment_rate_30d' => $fulfillmentRate30Days,
            'open_claims_48h' => WarrantyClaim::query()
                ->whereIn('status', ['submitted', 'reviewing'])
                ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$now->copy()->subHours(48)])
                ->count(),
            'pending_payment_orders' => Order::query()->where('payment_status', 'pending')->count(),
            'failed_payment_orders' => Order::query()->where('payment_status', 'failed')->count(),
        ];

        $revenue7dByDay = Order::query()
            ->selectRaw('DATE(COALESCE(paid_at, created_at)) as day_key, COALESCE(SUM(total_amount), 0) as total')
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(paid_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(paid_at, created_at))')
            ->pluck('total', 'day_key');

        $claims7dByDay = WarrantyClaim::query()
            ->selectRaw('DATE(COALESCE(requested_at, created_at)) as day_key, COUNT(*) as total')
            ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(requested_at, created_at))')
            ->pluck('total', 'day_key');

        $orderBacklog7dByDay = Order::query()
            ->selectRaw('DATE(created_at) as day_key, COUNT(*) as total')
            ->whereIn('status', ['pending', 'processing', 'shipped'])
            ->where('created_at', '>=', $period7Days)
            ->groupByRaw('DATE(created_at)')
            ->pluck('total', 'day_key');

        $claimBacklog7dByDay = WarrantyClaim::query()
            ->selectRaw('DATE(COALESCE(requested_at, created_at)) as day_key, COUNT(*) as total')
            ->whereIn('status', ['submitted', 'reviewing'])
            ->whereRaw('COALESCE(requested_at, created_at) >= ?', [$period7Days])
            ->groupByRaw('DATE(COALESCE(requested_at, created_at))')
            ->pluck('total', 'day_key');

        $backlog7dByDay = $trendDays
            ->mapWithKeys(function ($day) use ($orderBacklog7dByDay, $claimBacklog7dByDay) {
                $dayKey = $day->toDateString();

                return [
                    $dayKey => (int) ($orderBacklog7dByDay->get($dayKey, 0)) + (int) ($claimBacklog7dByDay->get($dayKey, 0)),
                ];
            });

        $trend7d = [
            'revenue' => $this->buildTrendSeries($trendDays, $revenue7dByDay),
            'claims' => $this->buildTrendSeries($trendDays, $claims7dByDay),
            'backlog' => $this->buildTrendSeries($trendDays, $backlog7dByDay),
        ];

        return view('admin.dashboard', [
            'overview' => $overview,
            'triage' => $triage,
            'metrics' => $metrics,
            'trend7d' => $trend7d,
        ]);
    }

    private function buildTrendSeries(Collection $trendDays, Collection $valuesByDay): array
    {
        $values = $trendDays
            ->map(fn($day) => (int) $valuesByDay->get($day->toDateString(), 0));

        $maxValue = max(1, ...$values->all());

        return $trendDays
            ->values()
            ->map(function ($day, $index) use ($values, $maxValue) {
                $value = (int) $values[$index];
                $scaledHeight = (int) round(($value / $maxValue) * 100);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->format('d M'),
                    'short_label' => $day->format('D'),
                    'value' => $value,
                    'height' => $value > 0 ? max(14, $scaledHeight) : 6,
                ];
            })
            ->all();
    }
}
