@php
    $warnaOrder = [
        'pending' => 'secondary',
        'searching_mitra' => 'info',
        'waiting_payment' => 'warning',
        'confirmed' => 'primary',
        'on_the_way' => 'primary',
        'arrived' => 'primary',
        'in_progress' => 'primary',
        'mitra_completed' => 'warning',
        'completed' => 'success',
        'cancelled' => 'danger',
    ];
@endphp
<span
    class="badge text-bg-{{ $warnaOrder[$status] ?? 'secondary' }}">{{ \App\Support\OrderStatus::label($status) }}</span>