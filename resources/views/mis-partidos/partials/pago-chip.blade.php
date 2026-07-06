{{-- Chip de compensación según modalidad de pago y tarifa.
     Espera $pago = ['valor' => ?float, 'modalidad' => ?string] (atributo calculado en el controlador). --}}
@php $pago = $pago ?? ['valor' => null, 'modalidad' => null]; @endphp

@if(($pago['modalidad'] ?? null) === 'nomina')
    <span class="pago-chip pago-chip--nomina"><i class="fa-solid fa-file-invoice-dollar"></i> Nómina</span>
@elseif($pago['valor'] !== null)
    <span class="pago-chip pago-chip--campo"><i class="fa-solid fa-coins"></i> ${{ number_format($pago['valor'], 0, ',', '.') }} COP</span>
@else
    <span class="pago-chip pago-chip--consultar"><i class="fa-regular fa-circle-question"></i> Consultar con designador</span>
@endif
