@extends('layouts.admin')

@section('title', 'Pesanan')

@section('content')
    <h1 class="h4 mb-3">Pesanan</h1>

    <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach (array_keys(\App\Support\OrderStatus::TRANSITIONS) as $s)
                    <option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ \App\Support\OrderStatus::label($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                placeholder="Cari kode, nama, atau telepon customer">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Kode</th>
                        <th>Customer</th>
                        <th>Jasa</th>
                        <th>Jadwal</th>
                        <th>Mitra</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $o)
                        <tr>
                            <td>
                                <a href="{{ route('admin.orders.show', ['id' => $o->id]) }}">{{ $o->order_code }}</a>
                            </td>
                            <td>
                                {{ $o->customer_name }}<br>
                                <span class="text-muted small">{{ $o->customer_phone }}</span>
                            </td>
                            <td>{{ $o->service->service_name }}</td>
                            <td>{{ $o->scheduled_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $o->mitra?->user->full_name ?? '-' }}</td>
                            <td class="text-end">Rp {{ number_format($o->total_price, 0, ',', '.') }}</td>
                            <td>@include('admin.partials.order-badge', ['status' => $o->order_status])</td>
                            <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Tidak ada pesanan yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $orders->links() }}
    </div>
@endsection