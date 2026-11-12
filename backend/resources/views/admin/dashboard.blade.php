@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h4 mb-3">Dashboard</h1>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Total pesanan</div>
                    <div class="fs-3 fw-semibold">{{ $summary['total_orders'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Sedang berjalan</div>
                    <div class="fs-3 fw-semibold">{{ $summary['active_orders'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Dibuat hari ini</div>
                    <div class="fs-3 fw-semibold">{{ $summary['created_today'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Dijadwalkan hari ini</div>
                    <div class="fs-3 fw-semibold">{{ $summary['scheduled_today'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100 {{ $summary['refund_pending_count'] > 0 ? 'border-warning' : '' }}">
                <div class="card-body">
                    <div class="text-muted small">Refund menunggu diproses</div>
                    <div class="fs-3 fw-semibold">{{ $summary['refund_pending_count'] }}</div>
                    <div class="text-muted">Rp {{ number_format($summary['refund_pending_amount'], 0, ',', '.') }}</div>
                    @if (Route::has('admin.payments.index'))
                        <a href="{{ route('admin.payments.index', ['status' => 'refund_pending']) }}" class="small">Lihat
                            pembayaran</a>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100 {{ $summary['mitra_pending'] > 0 ? 'border-warning' : '' }}">
                <div class="card-body">
                    <div class="text-muted small">Mitra menunggu persetujuan</div>
                    <div class="fs-3 fw-semibold">{{ $summary['mitra_pending'] }}</div>
                    @if (Route::has('admin.mitra.index'))
                        <a href="{{ route('admin.mitra.index', ['status' => 'pending']) }}" class="small">Lihat mitra</a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">Pesanan per status</div>
        <table class="table table-sm mb-0">
            <tbody>
                @foreach ($summary['status_counts'] as $baris)
                    <tr>
                        <td>{{ $baris['label'] }}</td>
                        <td class="text-end">{{ $baris['total'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection