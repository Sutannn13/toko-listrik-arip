<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CancelUnpaidOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cancel-unpaid';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Batalkan otomatis order yang belum dibayar selama lebih dari 1 jam';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $expiredThreshold = now()->subHour();

        $expiredOrderQuery = Order::query()
            ->where('status', 'pending')
            ->where('payment_status', 'pending')
            ->where(function ($query) use ($expiredThreshold) {
                $query->where('placed_at', '<=', $expiredThreshold)
                    ->orWhere(function ($fallbackQuery) use ($expiredThreshold) {
                        $fallbackQuery->whereNull('placed_at')
                            ->where('created_at', '<=', $expiredThreshold);
                    });
            });

        if (!(clone $expiredOrderQuery)->exists()) {
            $this->info('Tidak ada order pending yang melewati batas 1 jam.');
            return Command::SUCCESS;
        }

        $cancelledCount = 0;

        $expiredOrderQuery
            ->orderBy('id')
            ->chunkById(
                50,
                function ($orders) use (&$cancelledCount) {
                    foreach ($orders as $order) {
                        DB::transaction(function () use ($order, &$cancelledCount) {
                            $lockedOrder = Order::query()
                                ->whereKey($order->id)
                                ->lockForUpdate()
                                ->first();

                            if (!$lockedOrder || $lockedOrder->status !== 'pending' || $lockedOrder->payment_status !== 'pending') {
                                return;
                            }

                            $lockedOrder->load(['items', 'payments']);

                            foreach ($lockedOrder->items as $item) {
                                if (!$item->product_id) {
                                    continue;
                                }

                                $lockedProduct = Product::query()
                                    ->whereKey($item->product_id)
                                    ->lockForUpdate()
                                    ->first();

                                if ($lockedProduct) {
                                    $lockedProduct->increment('stock', $item->quantity);
                                }
                            }

                            $autoCancelNote = 'Auto-cancel sistem: dibatalkan otomatis karena belum dibayar selama lebih dari 1 jam.';
                            $lockedOrder->status = 'cancelled';
                            $lockedOrder->payment_status = 'failed';
                            $lockedOrder->warranty_status = 'void';
                            $lockedOrder->notes = implode(' | ', array_filter([
                                $lockedOrder->notes,
                                $autoCancelNote,
                            ]));
                            $lockedOrder->save();

                            $latestPayment = $lockedOrder->payments()
                                ->latest('id')
                                ->first();

                            if ($latestPayment && $latestPayment->status === 'pending') {
                                $latestPayment->update([
                                    'status' => 'failed',
                                    'paid_at' => null,
                                    'notes' => 'Auto-cancel sistem: batas waktu pembayaran 1 jam telah terlewati.',
                                ]);
                            }

                            $cancelledCount++;
                        }, 3);
                    }
                }
            );

        $this->info("Berhasil auto-cancel {$cancelledCount} order pending yang melewati batas 1 jam.");

        return Command::SUCCESS;
    }
}
