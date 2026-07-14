@props(['titulo', 'icono', 'color' => 'emerald', 'href' => null, 'cta' => null])

<div {{ $attributes->merge(['class' => 'widget-card']) }}>
    <div class="widget-card__head">
        <div class="widget-card__title">
            <div class="widget-card__icon ic-{{ $color }}">
                <i class="fa-solid {{ $icono }}"></i>
            </div>
            <div class="widget-card__heading">
                <span>{{ $titulo }}</span>
                @isset($meta)
                    <div class="widget-card__meta">{{ $meta }}</div>
                @endisset
            </div>
        </div>
        @if ($href && $cta)
            <a href="{{ $href }}" class="widget-card__cta">
                {{ $cta }}
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        @endif
    </div>
    <div class="widget-card__body">
        {{ $slot }}
    </div>
</div>
