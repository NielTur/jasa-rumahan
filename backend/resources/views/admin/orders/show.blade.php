@extends('layouts.admin')

@section('title', 'Pesanan ' . $order->order_code)

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="small">&larr; Daftar pesanan</a>
            <h1 class="h4 mb-0 mt-1">
                {{ $order->order_code }}
                @include('admin.partials.order-badge', ['status' => $order->order_status])
            </h1>
        </div>

        @if ($order->order_status === \App\Support\OrderStatus::MITRA_COMPLETED)
            <form method="POST" action="{{ route('admin.orders.force-complete', ['id' => $order->id]) }}"
                  onsubmit="return confirm('Tandai pesanan ini selesai tanpa menunggu konfirmasi customer?');">
                @csrf
                <button type="submit" class="btn btn-success">Paksa selesai</button>
            </form>
        @endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Pesanan</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Jasa</dt>
                        <dd class="col-sm-8">{{ $order->service->service_name }}</dd>
                        <dt class="col-sm-4">Ukuran</dt>
                        <dd class="col-sm-8">{{ $order->propertySize->size_name }}</dd>
                        <dt class="col-sm-4">Jadwal</dt>
                        <dd class="col-sm-8">
                            {{ $order->scheduled_at->format('d/m/Y H:i') }}
                            @if ($order->duration_minutes) ({{ $order->duration_minutes }} menit) @endif
                        </dd>
                        <dt class="col-sm-4">Dibuat</dt>
                        <dd class="col-sm-8">{{ $order->created_at->format('d/m/Y H:i') }}</dd>
                        @if ($order->completed_at)
                            <dt class="col-sm-4">Selesai</dt>
                            <dd class="col-sm-8">{{ $order->completed_at->format('d/m/Y H:i') }}</dd>
                        @endif
                        @if ($order->cancelled_at)
                            <dt class="col-sm-4">Dibatalkan</dt>
                            <dd class="col-sm-8">
                                {{ $order->cancelled_at->format('d/m/Y H:i') }}
                                oleh {{ $order->cancelled_by ?? '-' }}
                                @if ($order->cancel_reason)
                                    <br><span class="text-muted">{{ $order->cancel_reason }}</span>
                                @endif
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Customer</div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Nama</dt>
                        <dd class="col-sm-8">{{ $order->customer_name }}</dd>
                        <dt class="col-sm-4">Telepon</dt>
                        <dd class="col-sm-8">{{ $order->customer_phone }}</dd>
                        <dt class="col-sm-4">Akun</dt>
                        <dd class="col-sm-8">{{ $order->customer_id ? 'Terdaftar (ID ' . $order->customer_id . ')' : 'Guest' }}</dd>
                        <dt class="col-sm-4">Alamat</dt>
                        <dd class="col-sm-8">{{ $order->service_address }}</dd>
                        @if ($order->address_notes)
                            <dt class="col-sm-4">Catatan alamat</dt>
                            <dd class="col-sm-8">{{ $order->address_notes }}</dd>
                        @endif
                        @if ($order->customer_note)
                            <dt class="col-sm-4">Catatan</dt>
                            <dd class="col-sm-8">{{ $order->customer_note }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Rincian harga</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>{{ $order->service->service_name }} ({{ $order->propertySize->size_name }})</td>
                            <td class="text-end">Rp {{ number_format($order->service_price, 0, ',', '.') }}</td>
                        </tr>
                        @foreach ($order->addons as $a)
                            <tr>
                                <td>{{ $a->addon_name }} &times; {{ $a->quantity }}</td>
                                <td class="text-end">Rp {{ number_format($a->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-semibold">
                            <td>Total</td>
                            <td class="text-end">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">Mitra</div>
                <div class="card-body">
                    @if ($order->mitra)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Nama</dt>
                            <dd class="col-sm-8">{{ $order->mitra->user->full_name }}</dd>
                            <dt class="col-sm-4">Telepon</dt>
                            <dd class="col-sm-8">{{ $order->mitra->user->phone }}</dd>
                        </dl>
                    @else
                        <span class="text-muted">Belum ada mitra yang menerima.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">Pembayaran</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID gateway</th>
                        <th>Metode</th>
                        <th class="text-end">Nominal</th>
                        <th>Status</th>
                        <th>Dibayar</th>
                        <th>Kedaluwarsa</th>
                        <th class="text-end">Refund</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($order->payments->sortBy('id') as $p)
                        <tr>
                            <td>{{ $p->gateway_order_id }}</td>
                            <td>{{ $p->payment_method ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                            <td>@include('admin.partials.payment-badge', ['status' => $p->payment_status])</td>
                            <td>{{ $p->paid_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td>{{ $p->expired_at?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="text-end">{{ $p->refund_amount !== null ? 'Rp ' . number_format($p->refund_amount, 0, ',', '.') : '-' }}</td>
                            <td class="text-end">@include('admin.partials.refund-buttons', ['payment' => $p])</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Belum ada transaksi pembayaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Riwayat status</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Dari</th>
                        <th>Ke</th>
                        <th>Oleh</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->statusHistories->sortBy('id') as $h)
                        <tr>
                            <td>{{ $h->created_at->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $h->old_status ? \App\Support\OrderStatus::label($h->old_status) : '-' }}</td>
                            <td>{{ \App\Support\OrderStatus::label($h->new_status) }}</td>
                            <td>{{ $h->changedBy?->full_name ?? 'Sistem / guest' }}</td>
                            <td>{{ $h->note ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection