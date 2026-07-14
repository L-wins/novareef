@props(['label'])

<section class="dash-section">
    <div class="section-head">
        <span class="section-label">{{ $label }}</span>
        <div class="section-rule"></div>
    </div>

    {{ $slot }}
</section>
