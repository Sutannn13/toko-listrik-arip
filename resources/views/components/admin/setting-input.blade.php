@props(['setting', 'multiline' => false])

<div>
    <label class="mb-1.5 block text-sm font-semibold text-gray-700 dark:text-gray-300">
        {{ $setting['label'] ?? $setting->label ?? $setting->key }}
    </label>

    @if ($multiline)
        <textarea
            name="{{ $setting['key'] ?? $setting->key }}"
            rows="3"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">{{ $setting['value'] ?? $setting->value }}</textarea>
    @else
        <input
            type="text"
            name="{{ $setting['key'] ?? $setting->key }}"
            value="{{ $setting['value'] ?? $setting->value }}"
            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-dark-border dark:bg-dark-input dark:text-white">
    @endif
</div>
