@extends('layouts.admin')

@section('title', $title)

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">{{ $title }}</h1>
        <a href="{{ route($routeBase . '.create') }}" class="btn btn-primary btn-sm">+ Tambah</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        @foreach ($headers as $judul)
                            <th>{{ $judul }}</th>
                        @endforeach
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr class="{{ $row['active'] ? '' : 'table-secondary' }}">
                            <td>{{ $row['id'] }}</td>
                            @foreach ($row['cells'] as $isi)
                                <td>{{ $isi }}</td>
                            @endforeach
                            <td>
                                @if ($row['active'])
                                    <span class="badge text-bg-success">Aktif</span>
                                @else
                                    <span class="badge text-bg-secondary">Nonaktif</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route($routeBase . '.edit', ['id' => $row['id']]) }}"
                                    class="btn btn-outline-primary btn-sm">Edit</a>

                                <form method="POST" action="{{ route($routeBase . '.toggle', ['id' => $row['id']]) }}"
                                    class="d-inline"
                                    onsubmit="return confirm('{{ $row['active'] ? 'Nonaktifkan' : 'Aktifkan' }} data ini?');">
                                    @csrf
                                    @method('PATCH')
                                    @if ($row['active'])
                                        <button type="submit" class="btn btn-outline-warning btn-sm">Nonaktifkan</button>
                                    @else
                                        <button type="submit" class="btn btn-outline-success btn-sm">Aktifkan</button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($headers) + 3 }}" class="text-center text-muted py-4">Belum ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $items->links() }}
    </div>
@endsection