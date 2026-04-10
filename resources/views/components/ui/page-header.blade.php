@props(['title', 'subtitle' => null])

<div {{ $attributes->class(['ui-page-header']) }}>
    <div>
        <h1 class="ui-page-title">{{ $title }}</h1>
        @if (filled($subtitle))
            <p class="ui-page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
