{{ $arbitros->total() }} árbitro{{ $arbitros->total() === 1 ? '' : 's' }}
{{ $arbitros->total() === 1 ? 'encontrado' : 'encontrados' }}
<span class="plan-limite-contador">
    · {{ $limiteUsados }} {{ $limite === null ? '(ilimitado)' : "de {$limite}" }} en tu plan
</span>
