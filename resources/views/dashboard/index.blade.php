<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Rekap Pelatihan</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="calendar-surface min-h-screen text-slate-800 antialiased">
    @php
        $summary = $summary ?? [
            'total_master_kalender' => 0,
            'total_master_waktu' => 0,
            'total_detail_aktivitas' => 0,
            'total_peserta' => 0,
        ];
        $rekapTahun = $rekapTahun ?? collect();
        $rekapMetode = $rekapMetode ?? collect();
        $tahunMetodeOptions = $tahunMetodeOptions ?? collect();
        $selectedMetodeTahun = $selectedMetodeTahun ?? '';
        $tahunTrendOptions = $tahunTrendOptions ?? collect();
        $selectedTrendTahun = $selectedTrendTahun ?? now()->format('Y');
        $labelsTren = $labelsTren ?? [];
        $valuesTren = $valuesTren ?? [];
        $isAdmin = auth()->check() && auth()->user()?->isAdmin();
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="calendar-card rounded-3xl border border-white/70 bg-white/90 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Dashboard</p>
                    <h1 class="mt-1 font-['Space_Grotesk'] text-3xl font-bold text-slate-900 sm:text-4xl">Rekap Data Pelatihan</h1>
                    <p class="mt-2 text-sm text-slate-600">Ringkasan cepat data master agenda, waktu pelatihan, dan detail aktivitas.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <nav class="inline-flex flex-wrap items-center gap-1 rounded-full bg-slate-100 p-1 ring-1 ring-slate-200">
                        <a href="{{ route('katalog.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Katalog</a>
                        <a href="{{ route('kalender.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Kalender</a>
                        <a href="{{ route('dashboard.index') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">Dashboard</a>
                    </nav>
                    @if ($isAdmin)
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Login Admin</a>
                    @endif
                </div>
            </div>
        </header>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article class="calendar-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-lg shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Master Kalender</p>
                <p class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ number_format((int) $summary['total_master_kalender'], 0, ',', '.') }}</p>
            </article>
            
            <article class="calendar-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-lg shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Detail Aktivitas</p>
                <p class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ number_format((int) $summary['total_detail_aktivitas'], 0, ',', '.') }}</p>
            </article>
            <article class="calendar-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-lg shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Peserta</p>
                <p class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ number_format((int) $summary['total_peserta'], 0, ',', '.') }}</p>
            </article>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <article class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-xl shadow-slate-900/10 sm:p-6">
                <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Rekap Pelatihan Per Tahun</h2>
                <p class="mt-1 text-sm text-slate-500">Jumlah master agenda dan total peserta tiap tahun.</p>
                <div class="mt-4 h-72">
                    <canvas id="chartTahun"></canvas>
                </div>
            </article>

            <article class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-xl shadow-slate-900/10 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Komposisi Metode Pembelajaran</h2>
                        <p class="mt-1 text-sm text-slate-500">Distribusi metode dari detail aktivitas pada tahun yang dipilih.</p>
                    </div>
                    <form method="GET" action="{{ route('dashboard.index') }}" class="w-full sm:w-44">
                        @if ($selectedTrendTahun !== '')
                            <input type="hidden" name="tahun_tren" value="{{ $selectedTrendTahun }}">
                        @endif
                        <label for="tahun_metode" class="mt-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Filter Tahun</label>
                        <select id="tahun_metode" name="tahun_metode" onchange="this.form.submit()" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            <option value="">Semua Tahun</option>
                            @foreach ($tahunMetodeOptions as $tahunOption)
                                <option value="{{ $tahunOption }}" {{ (string) $selectedMetodeTahun === (string) $tahunOption ? 'selected' : '' }}>{{ $tahunOption }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="mt-4 h-72">
                    <canvas id="chartMetode"></canvas>
                </div>
            </article>
        </section>

        <section class="mt-6">
            <article class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-xl shadow-slate-900/10 sm:p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Tren Pelatihan Tahunan</h2>
                        <p class="mt-1 text-sm text-slate-500">Jumlah pelatihan berdasarkan bulan mulai jadwal pada tahun yang dipilih.</p>
                    </div>
                    <form method="GET" action="{{ route('dashboard.index') }}" class="w-full sm:w-44">
                        @if ($selectedMetodeTahun !== '')
                            <input type="hidden" name="tahun_metode" value="{{ $selectedMetodeTahun }}">
                        @endif
                        <label for="tahun_tren" class="mt-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Filter Tahun</label>
                        <select id="tahun_tren" name="tahun_tren" onchange="this.form.submit()" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700">
                            @foreach ($tahunTrendOptions as $tahunOption)
                                <option value="{{ $tahunOption }}" {{ (string) $selectedTrendTahun === (string) $tahunOption ? 'selected' : '' }}>{{ $tahunOption }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="mt-4 h-80">
                    <canvas id="chartBulanan"></canvas>
                </div>
            </article>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const rekapTahun = @json($rekapTahun);
        const rekapMetode = @json($rekapMetode);
        const labelsTren = @json($labelsTren);
        const valuesTren = @json($valuesTren);

        const tahunLabels = rekapTahun.map((item) => String(item.tahun_kalender));
        const tahunTotalPelatihan = rekapTahun.map((item) => Number(item.total_pelatihan || 0));
        const tahunTotalPeserta = rekapTahun.map((item) => Number(item.total_peserta || 0));

        const metodeLabels = rekapMetode.map((item) => String(item.metode || '-'));
        const metodeValues = rekapMetode.map((item) => Number(item.total || 0));
        const metodeColors = metodeLabels.map((label) => {
            switch (label) {
                case 'klasikal':
                    return '#10b981';
                case 'e-learning':
                    return '#0ea5e9';
                case 'mooc':
                    return '#f59e0b';
                case 'zoom':
                    return '#8b5cf6';
                case 'off-campus':
                    return '#f43f5e';
                case 'cop':
                    return '#06b6d4';
                default:
                    return '#64748b';
            }
        });

        const ctxTahun = document.getElementById('chartTahun');
        if (ctxTahun) {
            new Chart(ctxTahun, {
                type: 'bar',
                data: {
                    labels: tahunLabels,
                    datasets: [
                        {
                            label: 'Total Pelatihan',
                            data: tahunTotalPelatihan,
                            backgroundColor: '#0f766e',
                            borderRadius: 8,
                        },
                        {
                            label: 'Total Peserta',
                            data: tahunTotalPeserta,
                            backgroundColor: '#0891b2',
                            borderRadius: 8,
                        }
                    ]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                }
            });
        }

        const ctxMetode = document.getElementById('chartMetode');
        if (ctxMetode) {
            new Chart(ctxMetode, {
                type: 'doughnut',
                data: {
                    labels: metodeLabels,
                    datasets: [{
                        data: metodeValues,
                        backgroundColor: metodeColors
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                }
            });
        }

        const ctxBulanan = document.getElementById('chartBulanan');
        if (ctxBulanan) {
            new Chart(ctxBulanan, {
                type: 'line',
                data: {
                    labels: labelsTren,
                    datasets: [{
                        label: 'Jumlah Pelatihan',
                        data: valuesTren,
                        borderColor: '#0f766e',
                        backgroundColor: 'rgba(15, 118, 110, 0.14)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3,
                        pointBackgroundColor: '#0f766e'
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                }
            });
        }
    </script>
</body>
</html>
