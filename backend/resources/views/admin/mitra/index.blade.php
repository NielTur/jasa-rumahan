@extends('layouts.admin')

@section('title', 'Mitra')

@section('content')
    @php
        $labelVerif = ['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
        $warnaVerif = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
        $labelAkun = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Suspended'];
    @endphp

    <h1 class="h4 mb-3">Mitra</h1>

    <form method="GET" action="{{ route('admin.mitra.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua verifikasi</option>
                @foreach ($labelVerif as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(($filters['status'] ?? '') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                placeholder="Cari nama atau telepon">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.mitra.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Nama</th>
                        <th>Telepon</th>
                        <th>Wilayah</th>
                        <th class="text-center">Jasa aktif</th>
                        <th class="text-center">Pesanan</th>
                        <th>Tersedia</th>
                        <th>Verifikasi</th>
                        <th>Akun</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mitras as $m)
                        <tr>
                            <td>{{ $m->id }}</td>
                            <td>{{ $m->user->full_name }}</td>
                            <td>{{ $m->user->phone }}</td>
                            <td>{{ $m->service_area ?? '-' }}</td>
                            <td class="text-center">{{ $m->active_services_count }}</td>
                            <td class="text-center">{{ $m->orders_count }}</td>
                            <td>{{ $m->is_available ? 'Ya' : 'Tidak' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $warnaVerif[$m->verification_status] ?? 'secondary' }}">
                                    {{ $labelVerif[$m->verification_status] ?? $m->verification_status }}
                                </span>
                            </td>
                            <td>{{ $labelAkun[$m->user->account_status] ?? $m->user->account_status }}</td>
                            <td class="text-end">
                                @if ($m->verification_status !== 'approved')
                                    <form method="POST" action="{{ route('admin.mitra.verification', ['id' => $m->id]) }}"
                                        class="d-inline" onsubmit="return confirm('Setujui mitra ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="verification_status" value="approved">
                                        <button type="submit" class="btn btn-outline-success btn-sm">Setujui</button>
                                    </form>
                                @endif
                                @if ($m->verification_status !== 'rejected')
                                    <form method="POST" action="{{ route('admin.mitra.verification', ['id' => $m->id]) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('{{ $m->verification_status === 'approved' ? 'Cabut persetujuan mitra ini? Ia tidak akan ditawari pesanan baru.' : 'Tolak mitra ini?' }}');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="verification_status" value="rejected">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            {{ $m->verification_status === 'approved' ? 'Cabut' : 'Tolak' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">Tidak ada mitra yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $mitras->links() }}
    </div>
@endsection