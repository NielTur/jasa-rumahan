@extends('layouts.admin')

@section('title', 'Akun')

@section('content')
    @php
        $labelRole = ['customer' => 'Customer', 'mitra' => 'Mitra', 'admin' => 'Admin'];
        $labelStatus = ['active' => 'Aktif', 'inactive' => 'Nonaktif', 'suspended' => 'Suspended'];
        $warnaStatus = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger'];
    @endphp

    <h1 class="h4 mb-3">Akun</h1>

    <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-3">
        <div class="col-md-3">
            <select name="role" class="form-select">
                <option value="">Semua role</option>
                @foreach ($labelRole as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(($filters['role'] ?? '') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">Semua status</option>
                @foreach ($labelStatus as $nilai => $label)
                    <option value="{{ $nilai }}" @selected(($filters['status'] ?? '') === $nilai)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control"
                placeholder="Cari nama atau telepon">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th>Role</th>
                        <th>Status akun</th>
                        <th>Terdaftar</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $u)
                        <tr class="{{ $u->account_status === 'active' ? '' : 'table-secondary' }}">
                            <td>{{ $u->id }}</td>
                            <td>{{ $u->full_name }}</td>
                            <td>{{ $u->phone }}</td>
                            <td>{{ $labelRole[$u->role] ?? $u->role }}</td>
                            <td>
                                <span class="badge text-bg-{{ $warnaStatus[$u->account_status] ?? 'secondary' }}">
                                    {{ $labelStatus[$u->account_status] ?? $u->account_status }}
                                </span>
                            </td>
                            <td>{{ $u->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                @if ($u->role === 'admin')
                                    <span class="text-muted small">-</span>
                                @elseif ($u->account_status === 'active')
                                    <form method="POST" action="{{ route('admin.users.status', ['id' => $u->id]) }}"
                                        class="d-inline"
                                        onsubmit="return confirm('Suspend akun ini? Pengguna akan dikeluarkan dan tidak bisa masuk lagi.');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="suspended">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Suspend</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.users.status', ['id' => $u->id]) }}"
                                        class="d-inline" onsubmit="return confirm('Aktifkan akun ini?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="status" value="active">
                                        <button type="submit" class="btn btn-outline-success btn-sm">Aktifkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Tidak ada akun yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $users->links() }}
    </div>
@endsection