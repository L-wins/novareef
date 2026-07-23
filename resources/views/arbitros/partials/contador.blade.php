<span class="counter-chip counter-chip--strong">
    {{ $arbitros->total() }} árbitro{{ $arbitros->total() === 1 ? '' : 's' }}
    {{ $arbitros->total() === 1 ? 'encontrado' : 'encontrados' }}
</span>
<span class="counter-chip">
    {{ $limiteUsados }} {{ $limite === null ? '(ilimitado)' : "de {$limite}" }} en tu plan
</span>
