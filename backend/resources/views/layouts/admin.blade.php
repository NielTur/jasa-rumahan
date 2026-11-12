<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') | Jasa Rumahan</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
</head>

<body class="bg-light">
    @php
        // Item menu muncul otomatis begitu rutenya ada (T3b-T3d menambah rute bertahap)
        $menu = [
            'admin.dashboard' => 'Dashboard',
            'admin.orders.index' => 'Pesanan',
            'admin.payments.index' => 'Pembayaran',
            'admin.mitra.index' => 'Mitra',
            'admin.users.index' => 'Akun',
            'admin.service-categories.index' => 'Kategori Jasa',
            'admin.services.index' => 'Jasa',
            'admin.property-types.index' => 'Jenis Properti',
            'admin.property-sizes.index' => 'Ukuran Properti',
            'admin.service-prices.index' => 'Harga Jasa',
            'admin.service-addons.index' => 'Add-on',
        ];
    @endphp

    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('admin.dashboard') }}">Jasa Rumahan &middot; Admin</a>
            <div class="d-flex align-items-center gap-3 text-white">
                <span class="small">{{ auth()->user()->full_name }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <aside class="col-md-3 col-lg-2 py-3">
                <div class="list-group">
                    @foreach ($menu as $namaRute => $label)
                        @if (Route::has($namaRute))
                            @php
                                $pola = $namaRute === 'admin.dashboard'
                                    ? $namaRute
                                    : \Illuminate\Support\Str::beforeLast($namaRute, '.') . '.*';
                            @endphp
                            <a href="{{ route($namaRute) }}"
                                class="list-group-item list-group-item-action {{ request()->routeIs($pola) ? 'active' : '' }}">
                                {{ $label }}
                            </a>
                        @endif
                    @endforeach
                </div>
            </aside>

            <main class="col-md-9 col-lg-10 py-3">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $pesan)
                                <li>{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
    @stack('scripts')
</body>

</html>