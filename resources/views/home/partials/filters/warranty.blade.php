<form method="GET" action="{{ route('home.warranty') }}" class="ui-panel mb-6">
    <div class="grid gap-3 sm:grid-cols-3">
        <div>
            <label class="ui-label">Cari Produk / Order</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="ui-input"
                placeholder="Nama produk atau ORD...">
        </div>
        <div>
            <label class="ui-label">Status Garansi</label>
            <select name="status" class="ui-select">
                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Masih Aktif</option>
                <option value="expired" @selected(($filters['status'] ?? '') === 'expired')>Sudah Berakhir</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="ui-btn ui-btn-primary">
                Terapkan
            </button>
            <a href="{{ route('home.warranty') }}" class="ui-btn ui-btn-secondary">
                Reset
            </a>
        </div>
    </div>
</form>
