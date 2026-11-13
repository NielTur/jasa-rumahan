@extends('layouts.admin')

@section('title', 'Pembayaran')

@section('content')
    <h1 class="h4 mb-3">Pembayaran</h1>

    <form method="GET" action="{{ route('admin.payments.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (\App\Support\PaymentStatus::ALL as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ \App\Support\PaymentStatus::label($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                   placeholder="Cari kode pesanan atau ID gateway">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Pesanan</th>
                        <th>ID gateway</th>
                        <th>Metode</th>
                        <th class="text-end">Nominal</th>
                        <th>Status</th>
                        <th>Dibayar</th>
                        <th class="text-end">Refund</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $p)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', ['id' => $p->order_id]) }}">{{ $p->order->order_code }}</a>
                            </td>
                            <td>{{ $p->gateway_order_id }}</td>
                            <td>{{ $p->payment_method ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                            <td>@include('admin.partials.payment-badge', ['status' => $p->payment_status])</td>
                            <td>{{ $p->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-end">{{ $p->refund_amount !== null ? 'Rp ' . number_format($p->refund_amount, 0, ',', '.') : '-' }}</td>
                            <td class="text-end">@include('admin.partials.refund-buttons', ['payment' => $p])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada pembayaran yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $payments->links() }}
    </div>
@endsection