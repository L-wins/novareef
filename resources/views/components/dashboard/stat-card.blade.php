@props(['icon', 'color', 'value', 'label', 'href' => null, 'sub' => null])

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'stat-card stat-card--link']) }}>
        <div class="stat-icon-box ic-{{ $color }}"><i class="fa-solid {{ $icon }}"></i></div>
        <div class="stat-value val-{{ $color }}">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
        @if ($sub)<div class="stat-note">{{ $sub }}</div>@endif
    </a>
@else
    <div {{ $attributes->merge(['class' => 'stat-card']) }}>
        <div class="stat-icon-box ic-{{ $color }}"><i class="fa-solid {{ $icon }}"></i></div>
        <div class="stat-value val-{{ $color }}">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
        @if ($sub)<div class="stat-note">{{ $sub }}</div>@endif
    </div>
@endif
