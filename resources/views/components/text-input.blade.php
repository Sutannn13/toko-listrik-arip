@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'rounded-lg border-slate-300 text-sm shadow-sm transition duration-200 placeholder:text-slate-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 disabled:cursor-not-allowed disabled:bg-slate-100']) }}>
