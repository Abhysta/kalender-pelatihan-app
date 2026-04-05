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
        $labels12Bulan = $labels12Bulan ?? [];
        $values12Bulan = $values12Bulan ?? [];
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="calendar-card rounded-3xl border border-white/70 bg-white/90 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Dashboard</p>
                    <h1 class="mt-1 font-['Space_Grotesk'] text-3xl font-bold text-slate-900 sm:text-4xl">Rekap Data Pelatihan</h1>
                    <p class="mt-2 text-sm text-slate-600">Ringkasan cepat data master agenda, waktu pelatihan, dan detail aktivitas.</p>
                </div>
                <nav class="inline-flex flex-wrap items-center gap-1 rounded-full bg-slate-100 p-1 ring-1 ring-slate-200">
                    <a href="{{ route('katalog.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Katalog</a>
                    <a href="{{ route('kalender.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Kalender</a>
                    <a href="{{ route('dashboard.index') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">Dashboard</a>
                </nav>
            </div>
        </header>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="calendar-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-lg shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Master Kalender</p>
                <p class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ number_format((int) $summary['total_master_kalender'], 0, ',', '.') }}</p>
            </article>
            <article class="calendar-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-lg shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Master Waktu</p>
                <p class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ number_format((int) $summary['total_master_waktu'], 0, ',', '.') }}</p>
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
                <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Komposisi Metode Pembelajaran</h2>
                <p class="mt-1 text-sm text-slate-500">Distribusi metode dari seluruh detail aktivitas.</p>
                <div class="mt-4 h-72">
                    <canvas id="chartMetode"></canvas>
                </div>
            </article>
        </section>

        <section class="mt-6">
            <article class="calendar-card rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-xl shadow-slate-900/10 sm:p-6">
                <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Tren Aktivitas 12 Bulan Terakhir</h2>
                <p class="mt-1 text-sm text-slate-500">Jumlah detail aktivitas per bulan (rolling 12 bulan).</p>
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
        const labels12Bulan = @json($labels12Bulan);
        const values12Bulan = @json($values12Bulan);

        const tahunLabels = rekapTahun.map((item) => String(item.tahun_kalender));
        const tahunTotalPelatihan = rekapTahun.map((item) => Number(item.total_pelatihan || 0));
        const tahunTotalPeserta = rekapTahun.map((item) => Number(item.total_peserta || 0));

        const metodeLabels = rekapMetode.map((item) => String(item.metode || '-'));
        const metodeValues = rekapMetode.map((item) => Number(item.total || 0));

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
                        backgroundColor: ['#10b981', '#0ea5e9', '#f59e0b', '#06b6d4', '#64748b']
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
                    labels: labels12Bulan,
                    datasets: [{
                        label: 'Jumlah Aktivitas',
                        data: values12Bulan,
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
