@php
    $warnaBayar = [
        'pending' => 'secondary',
        'paid' => 'success',
        'failed' => 'danger',
        'expired' => 'secondary',
        'refund_pending' => 'warning',
        'refunded' => 'info',
        'refund_failed' => 'danger',
    ];
@endphp
<span class="badge text-bg-{{ $warnaBayar[$status] ?? 'secondary' }}">{{ \App\Support\PaymentStatus::label($status) }}</span>