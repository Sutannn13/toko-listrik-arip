<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-primary-600 px-4 py-2.5 text-xs font-semibold uppercase tracking-widest text-white shadow-sm transition duration-200 hover:bg-primary-700 focus:outline-none focus:ring-4 focus:ring-primary-500/30 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70']) }}>
    {{ $slot }}
</button>
