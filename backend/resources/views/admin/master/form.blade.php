@extends('layouts.admin')

@section('title', ($row ? 'Ubah ' : 'Tambah ') . $title)

@section('content')
    <h1 class="h4 mb-3">{{ $row ? 'Ubah' : 'Tambah' }} {{ $title }}</h1>

    <div class="card" style="max-width: 640px;">
        <div class="card-body">
            <form method="POST"
                  action="{{ $row ? route($routeBase . '.update', ['id' => $row->id]) : route($routeBase . '.store') }}">
                @csrf
                @if ($row)
                    @method('PUT')
                @endif

                @foreach ($fields as $f)
                    @php
                        $nama = $f['name'];
                        $nilai = old($nama, $row ? $row->{$nama} : null);
                        $merah = $errors->has($nama) ? ' is-invalid' : '';
                    @endphp

                    <div class="mb-3">
                        <label for="{{ $nama }}" class="form-label">
                            {{ $f['label'] }}@if (!empty($f['required'])) <span class="text-danger">*</span>@endif
                        </label>

                        @if ($f['type'] === 'textarea')
                            <textarea id="{{ $nama }}" name="{{ $nama }}" rows="3"
                                      class="form-control{{ $merah }}">{{ $nilai }}</textarea>
                        @elseif ($f['type'] === 'select')
                            <select id="{{ $nama }}" name="{{ $nama }}" class="form-select{{ $merah }}">
                                <option value="">-- Pilih --</option>
                                @foreach ($f['options'] as $nilaiOpsi => $labelOpsi)
                                    <option value="{{ $nilaiOpsi }}" @selected((string) $nilai === (string) $nilaiOpsi)>{{ $labelOpsi }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="{{ $f['type'] }}" id="{{ $nama }}" name="{{ $nama }}" value="{{ $nilai }}"
                                   @if ($f['type'] === 'number') step="{{ $f['step'] ?? '1' }}" @endif
                                   class="form-control{{ $merah }}">
                        @endif

                        @if (!empty($f['help']))
                            <div class="form-text">{{ $f['help'] }}</div>
                        @endif
                    </div>
                @endforeach

                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route($routeBase . '.index') }}" class="btn btn-link">Batal</a>
            </form>
        </div>
    </div>
@endsection