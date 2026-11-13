{{-- Tombol tandai refund. Dipakai di daftar pembayaran dan detail pesanan. Butuh variabel $payment. --}}
@if ($payment->payment_status === 'refund_pending' || $payment->payment_status === 'refund_failed')
    <form method="POST" action="{{ route('admin.payments.refund', ['id' => $payment->id]) }}" class="d-inline"
        onsubmit="return confirm('Tandai refund ini sudah dikembalikan ke customer?');">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="refunded">
        <button type="submit" class="btn btn-outline-success btn-sm">Tandai refunded</button>
    </form>
@endif
@if ($payment->payment_status === 'refund_pending')
    <form method="POST" action="{{ route('admin.payments.refund', ['id' => $payment->id]) }}" class="d-inline"
        onsubmit="return confirm('Tandai refund ini gagal diproses?');">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="refund_failed">
        <button type="submit" class="btn btn-outline-danger btn-sm">Refund gagal</button>
    </form>
@endif