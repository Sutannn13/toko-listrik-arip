<form method="GET" action="{{ route('home.warranty-claims.index') }}" class="ui-panel mb-6">
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="ui-label">Cari Klaim / Order / Produk</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="ui-input"
                placeholder="WRN..., ORD..., nama produk">
        </div>
        <div>
            <label class="ui-label">Status</label>
            <select name="status" class="ui-select">
                <option value="">Semua</option>
                @foreach (['submitted', 'reviewing', 'approved', 'rejected', 'resolved'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="ui-btn ui-btn-primary">
                Terapkan
            </button>
            <a href="{{ route('home.warranty-claims.index') }}" class="ui-btn ui-btn-secondary">
                Reset
            </a>
        </div>
    </div>
</form>
