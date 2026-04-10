<form method="GET" action="{{ route('home.transactions') }}" class="ui-panel mb-6">
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="ui-label">Cari Order / Customer</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="ui-input"
                placeholder="ORD..., nama, email">
        </div>
        <div>
            <label class="ui-label">Status Pesanan</label>
            <select name="status" class="ui-select">
                @foreach (['completed' => 'Completed', 'all' => 'Semua', 'pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'cancelled' => 'Cancelled'] as $statusValue => $statusLabel)
                    <option value="{{ $statusValue }}" @selected(($filters['status'] ?? 'completed') === $statusValue)>
                        {{ $statusLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="ui-btn ui-btn-primary">
                Terapkan
            </button>
            <a href="{{ route('home.transactions') }}" class="ui-btn ui-btn-secondary">
                Reset
            </a>
        </div>
    </div>
</form>
