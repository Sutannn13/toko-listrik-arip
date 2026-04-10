<button
    {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-slate-700 shadow-sm transition duration-200 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-primary-500/20 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70']) }}>
    {{ $slot }}
</button>
