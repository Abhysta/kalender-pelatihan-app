<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalender App</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="calendar-surface min-h-screen text-slate-800 antialiased">
    @php
        $timezone = 'Asia/Jakarta';
        $today = \Carbon\Carbon::today($timezone);
        $monthParam = request()->query('month');

        if (is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam) === 1) {
            [$year, $month] = array_map('intval', explode('-', $monthParam));
            $displayedMonth = ($month >= 1 && $month <= 12)
                ? \Carbon\Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth()
                : $today->copy()->startOfMonth();
        } else {
            $displayedMonth = $today->copy()->startOfMonth();
        }

        $currentMonth = ucfirst($displayedMonth->locale('id')->isoFormat('MMMM YYYY'));
        $previousMonthParam = $displayedMonth->copy()->subMonth()->format('Y-m');
        $nextMonthParam = $displayedMonth->copy()->addMonth()->format('Y-m');
        $todayMonthParam = $today->copy()->format('Y-m');

        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

        $katalogPelatihan = $katalogPelatihan ?? collect();
        $masterWaktu = $masterWaktu ?? collect();
        $aktivitasKalender = $aktivitasKalender ?? collect();
        $selectedDate = ($selectedDate ?? $today->copy())->copy()->startOfDay();
        $agendaTerpilih = $agendaTerpilih ?? collect();
        $isAdmin = auth()->check() && auth()->user()?->isAdmin();

        $startDate = $displayedMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endDate = $displayedMonth->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);
        $eventsByDate = [];

        foreach ($aktivitasKalender as $aktivitas) {
            $dateKey = (string) $aktivitas->tanggal_aktivitas;

            $eventsByDate[$dateKey][] = [
                'title' => $aktivitas->nama_kegiatan,
                'type' => 'aktivitas',
                'metode' => $aktivitas->metode_pembelajaran,
            ];
        }

        $calendarCells = [];

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $inCurrentMonth = $date->isSameMonth($displayedMonth);
            $dayNumber = (int) $date->format('j');
            $dateKey = $date->format('Y-m-d');

            $calendarCells[] = [
                'day' => $dayNumber,
                'muted' => !$inCurrentMonth,
                'is_today' => $date->isSameDay($today),
                'is_selected' => $date->isSameDay($selectedDate),
                'date_iso' => $dateKey,
                // Keep event labels only in active month cells to avoid messy edge weeks.
                'events' => $inCurrentMonth ? ($eventsByDate[$dateKey] ?? []) : [],
            ];
        }
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <div class="calendar-glow pointer-events-none absolute left-1/2 top-0 h-80 w-80 -translate-x-1/2 rounded-full blur-3xl"></div>

        <div class="relative grid items-start gap-6 lg:grid-cols-[1.6fr_0.8fr]">
            <section class="calendar-card overflow-hidden rounded-3xl border border-white/60 bg-white/80 shadow-2xl shadow-slate-900/10 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/80 px-4 py-5 sm:px-6">
                    <div>
                        <nav class="mb-4 inline-flex flex-wrap items-center gap-1 rounded-full bg-slate-100 p-1 ring-1 ring-slate-200">
                            <a href="{{ route('katalog.index') }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Katalog</a>
                            <a href="{{ route('kalender.index') }}" class="rounded-full bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm">Kalender</a>
                            <a href="{{ route('dashboard.index') }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Dashboard</a>
                        </nav>
                        <p class="font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Kalender Kerja</p>
                        <h1 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900 sm:text-3xl">{{ $currentMonth }}</h1>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 p-1">
                        <a class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:text-slate-900" href="{{ url()->current() . '?month=' . $previousMonthParam }}">Sebelumnya</a>
                        <a class="rounded-full bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700" href="{{ url()->current() . '?month=' . $todayMonthParam }}">Hari Ini</a>
                        <a class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:text-slate-900" href="{{ url()->current() . '?month=' . $nextMonthParam }}">Berikutnya</a>
                    </div>
                </div>

                <div class="grid grid-cols-7 border-b border-slate-200/80 bg-slate-50/70 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 sm:px-4">
                    @foreach ($days as $day)
                        <div class="py-2">{{ $day }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-px bg-slate-200/70 p-px">
                    @foreach ($calendarCells as $cell)
                        <a
                            href="{{ route('kalender.index', ['month' => $displayedMonth->format('Y-m'), 'selected_date' => $cell['date_iso']]) }}"
                            class="group block min-h-[94px] bg-white p-2.5 transition hover:bg-teal-50/60 sm:min-h-[110px] sm:p-3 {{ $cell['is_selected'] ? 'ring-2 ring-teal-400 ring-inset' : '' }}"
                        >
                            <div class="flex items-start justify-between">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $cell['is_selected'] ? 'bg-teal-700 text-white shadow' : ($cell['is_today'] ? 'bg-teal-600 text-white shadow' : ($cell['muted'] ? 'text-slate-400' : 'text-slate-700')) }}">
                                    {{ $cell['day'] }}
                                </span>
                                @if (count($cell['events']) > 0)
                                    <span class="h-2 w-2 rounded-full bg-teal-500"></span>
                                @endif
                            </div>

                            <div class="mt-2 space-y-1">
                                @php
                                    $visibleEvents = array_slice($cell['events'], 0, 2);
                                    $hiddenEventsCount = max(count($cell['events']) - count($visibleEvents), 0);
                                @endphp
                                @foreach ($visibleEvents as $event)
                                    <p class="truncate rounded-md px-2 py-1 text-[11px] font-medium {{ ($event['metode'] ?? '') === 'klasikal'
                                        ? 'bg-emerald-100 text-emerald-800'
                                        : (($event['metode'] ?? '') === 'e-learning'
                                            ? 'bg-sky-100 text-sky-800'
                                            : (($event['metode'] ?? '') === 'mooc'
                                                ? 'bg-amber-100 text-amber-800'
                                                : (($event['metode'] ?? '') === 'zoom'
                                                    ? 'bg-violet-100 text-violet-800'
                                                    : (($event['metode'] ?? '') === 'off-campus'
                                                        ? 'bg-rose-100 text-rose-800'
                                                        : 'bg-cyan-100 text-cyan-800')))) }}">
                                        {{ $event['title'] }}
                                    </p>
                                @endforeach
                                @if ($hiddenEventsCount > 0)
                                    <p class="truncate rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-500">
                                        +{{ $hiddenEventsCount }} agenda
                                    </p>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <aside class="space-y-6">
                <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/85 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                    <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Agenda Hari Ini</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $selectedDate->copy()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>

                    <div class="mt-5 max-h-56 overflow-y-auto space-y-3">
                        @forelse ($agendaTerpilih as $agenda)
                            <div class="rounded-2xl border p-3 {{ $agenda->metode_pembelajaran === 'klasikal'
                                ? 'border-emerald-200 bg-emerald-50'
                                : ($agenda->metode_pembelajaran === 'e-learning'
                                    ? 'border-sky-200 bg-sky-50'
                                    : ($agenda->metode_pembelajaran === 'mooc'
                                        ? 'border-amber-200 bg-amber-50'
                                        : ($agenda->metode_pembelajaran === 'zoom'
                                            ? 'border-violet-200 bg-violet-50'
                                            : ($agenda->metode_pembelajaran === 'off-campus'
                                                ? 'border-rose-200 bg-rose-50'
                                                : 'border-cyan-200 bg-cyan-50')))) }}">
                                <p class="text-xs font-semibold uppercase tracking-wide {{ $agenda->metode_pembelajaran === 'klasikal'
                                    ? 'text-emerald-700'
                                    : ($agenda->metode_pembelajaran === 'e-learning'
                                        ? 'text-sky-700'
                                        : ($agenda->metode_pembelajaran === 'mooc'
                                            ? 'text-amber-700'
                                            : ($agenda->metode_pembelajaran === 'zoom'
                                                ? 'text-violet-700'
                                                : ($agenda->metode_pembelajaran === 'off-campus'
                                                    ? 'text-rose-700'
                                                    : 'text-cyan-700')))) }}">
                                    {{ strtoupper($agenda->metode_pembelajaran) }}
                                </p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $agenda->nama_kegiatan }}</p>
                                <p class="mt-1 text-xs text-slate-600">Pelatihan: {{ $agenda->nama_kalender }}</p>
                                <p class="mt-1 text-xs text-slate-600">Pengajar: {{ $agenda->nama_pengajar }}</p>
                            </div>
                        @empty
                            <article class="rounded-2xl border border-dashed border-slate-300 bg-white p-3 text-sm text-slate-500">
                                Belum ada detail aktivitas untuk tanggal terpilih.
                            </article>
                        @endforelse
                    </div>
                </section>

                <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/85 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Master Waktu Pelatihan</h2>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $masterWaktu->count() }} Jadwal</span>
                    </div>

                    <div class="mt-4 max-h-56 space-y-2 overflow-y-auto pr-1">
                        @forelse ($masterWaktu as $waktu)
                            <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <p class="text-sm font-semibold text-slate-900">{{ $waktu->nama_kalender }}</p>
                                <p class="mt-1 text-xs text-slate-500">ID Kalender: {{ $waktu->id_kalender }}</p>
                                <p class="mt-1 text-xs font-medium text-teal-700">
                                    {{ \Carbon\Carbon::parse($waktu->tanggal_mulai)->locale('id')->isoFormat('D MMM YYYY') }}
                                    -
                                    {{ \Carbon\Carbon::parse($waktu->tanggal_selesai)->locale('id')->isoFormat('D MMM YYYY') }}
                                </p>
                                <a href="{{ route('kalender.index', ['month' => \Carbon\Carbon::parse($waktu->tanggal_mulai)->format('Y-m')]) }}" class="mt-1 inline-flex text-xs font-semibold text-slate-600 hover:text-slate-900">
                                    Lihat Bulan
                                </a>
                            </article>
                        @empty
                            <article class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">
                                Belum ada data master waktu.
                            </article>
                        @endforelse
                    </div>
                </section>

                @if ($isAdmin)
                    <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/85 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h2 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Katalog Pelatihan</h2>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ count($katalogPelatihan) }} Kelas</span>
                        </div>

                        <div class="mt-4 max-h-72 space-y-2 overflow-y-auto pr-1">
                            @forelse ($katalogPelatihan as $pelatihan)
                                <article class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Master Kalender {{ $pelatihan->tahun_kalender }}</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">{{ $pelatihan->nama_kalender }}</p>
                                    <div class="mt-1 flex items-center justify-between">
                                        <p class="text-xs text-slate-500">Peserta: {{ number_format((int) $pelatihan->total_peserta, 0, ',', '.') }}</p>
                                        <a href="{{ route('katalog.detail', ['id' => $pelatihan->id_kalender]) }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">Detail</a>
                                    </div>
                                </article>
                            @empty
                                <article class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">
                                    Belum ada data master kalender.
                                </article>
                            @endforelse
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </main>
</body>
</html>
