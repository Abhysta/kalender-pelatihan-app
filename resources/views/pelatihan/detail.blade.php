<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Detail Katalog</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="calendar-surface min-h-screen text-slate-800 antialiased">
    @php
        $timezone = 'Asia/Jakarta';
        $today = \Carbon\Carbon::today($timezone);
        $displayedMonth = ($displayedMonth ?? $today->copy())->copy()->startOfMonth();

        $currentMonth = ucfirst($displayedMonth->locale('id')->isoFormat('MMMM YYYY'));
        $previousMonthParam = $displayedMonth->copy()->subMonth()->format('Y-m');
        $nextMonthParam = $displayedMonth->copy()->addMonth()->format('Y-m');
        $todayMonthParam = $today->copy()->format('Y-m');

        $days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
        $selectedDate = ($selectedDate ?? $displayedMonth->copy()->startOfMonth())->copy()->startOfDay();
        $aktivitasBulan = $aktivitasBulan ?? collect();
        $selectedDateInRange = $selectedDateInRange ?? false;
        $isAdmin = auth()->check() && auth()->user()?->isAdmin();

        $aktivitasByDate = [];
        foreach ($aktivitasBulan as $aktivitas) {
            $aktivitasByDate[$aktivitas->tanggal_aktivitas][] = $aktivitas;
        }

        $startDate = $displayedMonth->copy()->startOfWeek(\Carbon\Carbon::MONDAY);
        $endDate = $displayedMonth->copy()->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $coveredDates = [];
        $rangeEventsByDate = [];

        foreach ($masterWaktu as $waktu) {
            try {
                $rangeStart = \Carbon\Carbon::parse($waktu->tanggal_mulai, $timezone)->startOfDay();
                $rangeEnd = \Carbon\Carbon::parse($waktu->tanggal_selesai, $timezone)->startOfDay();
            } catch (\Throwable $exception) {
                continue;
            }

            if ($rangeEnd->lt($startDate) || $rangeStart->gt($endDate)) {
                continue;
            }

            $cursor = $rangeStart->copy();
            if ($cursor->lt($startDate)) {
                $cursor = $startDate->copy();
            }

            $cursorEnd = $rangeEnd->copy();
            if ($cursorEnd->gt($endDate)) {
                $cursorEnd = $endDate->copy();
            }

            while ($cursor->lte($cursorEnd)) {
                $key = $cursor->format('Y-m-d');
                $coveredDates[$key] = true;
                $cursor->addDay();
            }
        }

        foreach (array_keys($coveredDates) as $dateKey) {
            $aktivitasTanggal = $aktivitasByDate[$dateKey] ?? [];

            if (count($aktivitasTanggal) > 0) {
                foreach ($aktivitasTanggal as $aktivitas) {
                    $rangeEventsByDate[$dateKey][] = [
                        'title' => $aktivitas->nama_kegiatan,
                        'type' => 'aktivitas',
                        'metode' => $aktivitas->metode_pembelajaran,
                    ];
                }
                continue;
            }

            $dateObject = \Carbon\Carbon::parse($dateKey, $timezone);

            $rangeEventsByDate[$dateKey][] = [
                'title' => $dateObject->isWeekend() ? 'Libur' : $kalender->nama_kalender,
                'type' => $dateObject->isWeekend() ? 'libur' : 'placeholder',
                'metode' => null,
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
                'events' => $inCurrentMonth ? ($rangeEventsByDate[$dateKey] ?? []) : [],
            ];
        }

        $showLiburCard = !$isAdmin
            && $selectedDateInRange
            && $selectedDate->isWeekend()
            && $detailAktivitas->isEmpty();
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <section class="calendar-card rounded-3xl border border-white/70 bg-white/90 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <nav class="inline-flex flex-wrap items-center gap-1 rounded-full bg-slate-100 p-1 ring-1 ring-slate-200">
                    <a href="{{ route('katalog.index') }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Katalog</a>
                    <a href="{{ route('dashboard.index') }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Dashboard</a>
                    <a href="{{ route('kalender.index') }}" class="rounded-full px-3 py-1.5 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Kalender Umum</a>
                    <span class="rounded-full bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm">Detail</span>
                </nav>
            </div>

            <p class="mt-4 font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">Detail Katalog</p>
            <h1 class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900">{{ $kalender->nama_kalender }}</h1>

            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-slate-100 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ID Kalender</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $kalender->id_kalender }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ $kalender->tahun_kalender }}</p>
                </div>
                <div class="rounded-xl bg-slate-100 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total Peserta</p>
                    <p class="mt-1 text-lg font-bold text-slate-900">{{ number_format((int) $kalender->total_peserta, 0, ',', '.') }}</p>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.55fr_0.85fr]">
            <section class="calendar-card overflow-hidden rounded-3xl border border-white/60 bg-white/90 shadow-2xl shadow-slate-900/10 backdrop-blur">
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/80 px-4 py-5 sm:px-6">
                    <div>
                        <p class="font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.18em] text-teal-700">Kalender Pelatihan</p>
                        <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900 sm:text-3xl">{{ $currentMonth }}</h2>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 p-1">
                        <a class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:text-slate-900" href="{{ route('katalog.detail', ['id' => $kalender->id_kalender, 'month' => $previousMonthParam]) }}">Sebelumnya</a>
                        <a class="rounded-full bg-teal-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-700" href="{{ route('katalog.detail', ['id' => $kalender->id_kalender, 'month' => $todayMonthParam]) }}">Hari Ini</a>
                        <a class="rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:text-slate-900" href="{{ route('katalog.detail', ['id' => $kalender->id_kalender, 'month' => $nextMonthParam]) }}">Berikutnya</a>
                    </div>
                </div>

                <div class="grid grid-cols-7 border-b border-slate-200/80 bg-slate-50/70 px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-slate-500 sm:px-4">
                    @foreach ($days as $day)
                        <div class="py-2">{{ $day }}</div>
                    @endforeach
                </div>

                <div class="grid grid-cols-7 gap-px bg-slate-200/70 p-px">
                    @foreach ($calendarCells as $cell)
                        @php
                            $visibleEvents = array_slice($cell['events'], 0, 2);
                            $hiddenEvents = array_slice($cell['events'], 2);
                            $hiddenCount = count($hiddenEvents);
                            $collapseId = 'more-' . str_replace('-', '', $cell['date_iso']) . '-' . $loop->index;
                        @endphp

                        <article class="group min-h-23.5 bg-white p-2.5 transition hover:bg-teal-50/60 sm:min-h-27.5 sm:p-3 {{ $cell['is_selected'] ? 'ring-2 ring-teal-400 ring-inset' : '' }}">
                            <a href="{{ route('katalog.detail', ['id' => $kalender->id_kalender, 'month' => $displayedMonth->format('Y-m'), 'selected_date' => $cell['date_iso']]) }}" class="flex items-start justify-between">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold {{ $cell['is_selected'] ? 'bg-teal-700 text-white shadow' : ($cell['is_today'] ? 'bg-teal-600 text-white shadow' : ($cell['muted'] ? 'text-slate-400' : 'text-slate-700')) }}">
                                    {{ $cell['day'] }}
                                </span>
                                @if (count($cell['events']) > 0)
                                    <span class="h-2 w-2 rounded-full bg-slate-500"></span>
                                @endif
                            </a>

                            <div class="mt-2 space-y-1">
                                @foreach ($visibleEvents as $event)
                                    @php
                                        $eventBadgeClass = match (true) {
                                            ($event['type'] ?? '') === 'placeholder' => 'bg-slate-200 text-slate-600',
                                            ($event['type'] ?? '') === 'libur' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
                                            ($event['metode'] ?? '') === 'klasikal' => 'bg-emerald-100 text-emerald-800',
                                            ($event['metode'] ?? '') === 'e-learning' => 'bg-sky-100 text-sky-800',
                                            ($event['metode'] ?? '') === 'mooc' => 'bg-amber-100 text-amber-800',
                                            ($event['metode'] ?? '') === 'zoom' => 'bg-violet-100 text-violet-800',
                                            ($event['metode'] ?? '') === 'off-campus' => 'bg-rose-100 text-rose-800',
                                            default => 'bg-cyan-100 text-cyan-800',
                                        };
                                    @endphp
                                    <p class="truncate rounded-md px-2 py-1 text-[11px] font-medium {{ $eventBadgeClass }}">
                                        {{ $event['title'] }}
                                    </p>
                                @endforeach

                                @if ($hiddenCount > 0)
                                    <div id="{{ $collapseId }}" class="hidden space-y-1">
                                        @foreach ($hiddenEvents as $event)
                                            @php
                                                $eventBadgeClass = match (true) {
                                                    ($event['type'] ?? '') === 'placeholder' => 'bg-slate-200 text-slate-600',
                                                    ($event['type'] ?? '') === 'libur' => 'bg-rose-100 text-rose-800 ring-1 ring-rose-200',
                                                    ($event['metode'] ?? '') === 'klasikal' => 'bg-emerald-100 text-emerald-800',
                                                    ($event['metode'] ?? '') === 'e-learning' => 'bg-sky-100 text-sky-800',
                                                    ($event['metode'] ?? '') === 'mooc' => 'bg-amber-100 text-amber-800',
                                                    ($event['metode'] ?? '') === 'zoom' => 'bg-violet-100 text-violet-800',
                                                    ($event['metode'] ?? '') === 'off-campus' => 'bg-rose-100 text-rose-800',
                                                    default => 'bg-cyan-100 text-cyan-800',
                                                };
                                            @endphp
                                            <p class="truncate rounded-md px-2 py-1 text-[11px] font-medium {{ $eventBadgeClass }}">
                                                {{ $event['title'] }}
                                            </p>
                                        @endforeach
                                    </div>

                                    <button
                                        type="button"
                                        class="text-[11px] font-semibold text-slate-500 hover:text-slate-700"
                                        data-collapse-target="{{ $collapseId }}"
                                        data-label-more="+{{ $hiddenCount }} lainnya"
                                        data-label-less="Tutup"
                                    >
                                        +{{ $hiddenCount }} lainnya
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <aside class="space-y-6">
                <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                    <h3 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Detail Aktivitas</h3>
                    <p class="mt-1 text-sm text-slate-500">Tanggal terpilih: {{ $selectedDate->copy()->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</p>
                    @if (!$selectedDateInRange)
                        <p class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800">
                            Tanggal terpilih berada di luar rentang master waktu. Aktivitas untuk tanggal di luar rentang tidak akan disimpan.
                        </p>
                    @endif
                    @if ($isAdmin)
                        <p class="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                            Isi rentang tanggal agar kegiatan bisa dibuat sekaligus tanpa klik satu-satu tanggal.
                        </p>
                    @endif

                    @if (session('success_aktivitas_detail'))
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">{{ session('success_aktivitas_detail') }}</p>
                    @endif

                    @if (session('error_aktivitas_detail'))
                        <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ session('error_aktivitas_detail') }}</p>
                    @endif
                    @if (session('success_aktivitas_edit'))
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">{{ session('success_aktivitas_edit') }}</p>
                    @endif
                    @if (session('error_aktivitas_edit'))
                        <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ session('error_aktivitas_edit') }}</p>
                    @endif
                    @if (session('success_aktivitas_delete'))
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">{{ session('success_aktivitas_delete') }}</p>
                    @endif
                    @if (session('error_aktivitas_delete'))
                        <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ session('error_aktivitas_delete') }}</p>
                    @endif

                    @if ($isAdmin)
                        @php
                            $defaultTanggalMulaiAktivitas = old('tanggal_mulai_aktivitas', $selectedDate->format('Y-m-d'));
                            $defaultTanggalSelesaiAktivitas = old('tanggal_selesai_aktivitas', $selectedDate->format('Y-m-d'));
                            $defaultIncludeWeekend = old('include_weekend', '0');
                        @endphp
                        <form action="{{ route('katalog.aktivitas.store', ['id' => $kalender->id_kalender]) }}" method="POST" class="mt-4 space-y-3">
                            @csrf
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label for="tanggal_mulai_aktivitas" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Mulai Kegiatan</label>
                                    <input id="tanggal_mulai_aktivitas" name="tanggal_mulai_aktivitas" type="date" value="{{ $defaultTanggalMulaiAktivitas }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                                    @error('tanggal_mulai_aktivitas')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="tanggal_selesai_aktivitas" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Selesai Kegiatan</label>
                                    <input id="tanggal_selesai_aktivitas" name="tanggal_selesai_aktivitas" type="date" value="{{ $defaultTanggalSelesaiAktivitas }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                                    @error('tanggal_selesai_aktivitas')
                                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="nama_kegiatan" class="mb-1 block text-sm font-semibold text-slate-700">Nama Kegiatan</label>
                                <input id="nama_kegiatan" name="nama_kegiatan" type="text" value="{{ old('nama_kegiatan') }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                                @error('nama_kegiatan')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="metode_pembelajaran" class="mb-1 block text-sm font-semibold text-slate-700">Metode Pembelajaran</label>
                                <select id="metode_pembelajaran" name="metode_pembelajaran" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required>
                                    <option value="">Pilih metode</option>
                                    @foreach (['klasikal', 'e-learning', 'mooc', 'cop', 'zoom', 'off-campus'] as $metode)
                                        <option value="{{ $metode }}" {{ old('metode_pembelajaran') === $metode ? 'selected' : '' }}>
                                            {{ strtoupper($metode) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('metode_pembelajaran')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="include_weekend" class="mb-1 block text-sm font-semibold text-slate-700">Termasuk Weekend?</label>
                                <select id="include_weekend" name="include_weekend" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required>
                                    <option value="0" {{ (string) $defaultIncludeWeekend === '0' ? 'selected' : '' }}>Tidak, isi hanya hari kerja</option>
                                    <option value="sabtu" {{ (string) $defaultIncludeWeekend === 'sabtu' ? 'selected' : '' }}>Ya, Hari Sabtu</option>
                                    <option value="1" {{ (string) $defaultIncludeWeekend === '1' ? 'selected' : '' }}>Ya, termasuk Sabtu dan Minggu</option>
                                </select>
                                @error('include_weekend')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="nama_pengajar" class="mb-1 block text-sm font-semibold text-slate-700">Nama Pengajar</label>
                                <input id="nama_pengajar" name="nama_pengajar" type="text" value="{{ old('nama_pengajar') }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                                @error('nama_pengajar')
                                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="rounded-xl bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800">Simpan Detail Aktivitas</button>
                        </form>
                    @endif

                    <div class="mt-4 max-h-56 space-y-2 overflow-y-auto pr-1">
                        @forelse ($detailAktivitas as $aktivitas)
                            <article class="rounded-xl border px-3 py-2.5 {{ $aktivitas->metode_pembelajaran === 'klasikal'
                                ? 'border-emerald-200 bg-emerald-50'
                                    : ($aktivitas->metode_pembelajaran === 'e-learning'
                                        ? 'border-sky-200 bg-sky-50'
                                        : ($aktivitas->metode_pembelajaran === 'mooc'
                                            ? 'border-amber-200 bg-amber-50'
                                            : ($aktivitas->metode_pembelajaran === 'zoom'
                                                ? 'border-violet-200 bg-violet-50'
                                                : ($aktivitas->metode_pembelajaran === 'off-campus'
                                                    ? 'border-rose-200 bg-rose-50'
                                                    : 'border-cyan-200 bg-cyan-50')))) }}">
                                <p class="text-sm font-semibold text-slate-900">{{ $aktivitas->nama_kegiatan }}</p>
                                <p class="mt-1 text-xs uppercase tracking-wide {{ $aktivitas->metode_pembelajaran === 'klasikal'
                                    ? 'text-emerald-700'
                                    : ($aktivitas->metode_pembelajaran === 'e-learning'
                                        ? 'text-sky-700'
                                        : ($aktivitas->metode_pembelajaran === 'mooc'
                                            ? 'text-amber-700'
                                            : ($aktivitas->metode_pembelajaran === 'zoom'
                                                ? 'text-violet-700'
                                                : ($aktivitas->metode_pembelajaran === 'off-campus'
                                                    ? 'text-rose-700'
                                                    : 'text-cyan-700')))) }}">{{ strtoupper($aktivitas->metode_pembelajaran) }}</p>
                                <p class="mt-1 text-xs text-slate-600">Pengajar: {{ $aktivitas->nama_pengajar }}</p>
                                <p class="mt-1 text-xs text-slate-500">Tanggal: {{ \Carbon\Carbon::parse($aktivitas->tanggal_aktivitas)->locale('id')->isoFormat('D MMMM YYYY') }}</p>

                                @if ($isAdmin)
                                    <details class="mt-2 rounded-lg border border-slate-200 bg-white p-2">
                                        <summary class="cursor-pointer text-xs font-semibold text-teal-700">Edit aktivitas ini</summary>
                                        <form action="{{ route('katalog.aktivitas.update', ['id' => $kalender->id_kalender, 'idAktivitas' => $aktivitas->id_aktivitas]) }}" method="POST" class="mt-2 space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Tanggal Aktivitas</label>
                                                <input name="edit_tanggal_aktivitas" type="date" value="{{ old('edit_tanggal_aktivitas', \Carbon\Carbon::parse($aktivitas->tanggal_aktivitas)->format('Y-m-d')) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-700" required />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nama Kegiatan</label>
                                                <input name="edit_nama_kegiatan" type="text" value="{{ old('edit_nama_kegiatan', $aktivitas->nama_kegiatan) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-700" required />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Metode Pembelajaran</label>
                                                <select name="edit_metode_pembelajaran" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-700" required>
                                                @foreach (['klasikal', 'e-learning', 'mooc', 'cop', 'zoom', 'off-campus'] as $metode)
                                                    <option value="{{ $metode }}" {{ old('edit_metode_pembelajaran', $aktivitas->metode_pembelajaran) === $metode ? 'selected' : '' }}>
                                                        {{ strtoupper($metode) }}
                                                    </option>
                                                @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Nama Pengajar</label>
                                                <input name="edit_nama_pengajar" type="text" value="{{ old('edit_nama_pengajar', $aktivitas->nama_pengajar) }}" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-700" required />
                                            </div>
                                            <button type="submit" class="w-full rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700">Simpan Perubahan</button>
                                        </form>
                                    </details>

                                    <form action="{{ route('katalog.aktivitas.destroy', ['id' => $kalender->id_kalender, 'idAktivitas' => $aktivitas->id_aktivitas]) }}" method="POST" class="mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 ring-1 ring-rose-200 transition hover:bg-rose-100" onclick="return confirm('Hapus detail aktivitas ini?')">Hapus Aktivitas</button>
                                    </form>
                                @endif
                            </article>
                        @empty
                            @if ($showLiburCard)
                                <article class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-4 text-center">
                                    <p class="text-sm font-semibold text-rose-800">Libur</p>
                                    <p class="mt-1 text-xs text-rose-700">Tanggal weekend ini tidak dijadwalkan untuk aktivitas pelatihan.</p>
                                </article>
                            @else
                                <article class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">
                                    Belum ada detail aktivitas pada tanggal ini.
                                </article>
                            @endif
                        @endforelse
                    </div>
                </section>

                @if ($isAdmin)
                <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                    <h3 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Tambah Waktu</h3>
                    <p class="mt-1 text-sm text-slate-500">Tambah rentang waktu untuk pelatihan ini.</p>

                    @if (session('success_waktu_detail'))
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">{{ session('success_waktu_detail') }}</p>
                    @endif

                    @if (session('error_waktu_detail'))
                        <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ session('error_waktu_detail') }}</p>
                    @endif

                    <form action="{{ route('katalog.waktu.store', ['id' => $kalender->id_kalender]) }}" method="POST" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <label for="tanggal_mulai" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Mulai</label>
                            <input id="tanggal_mulai" name="tanggal_mulai" type="date" value="{{ old('tanggal_mulai') }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                            @error('tanggal_mulai')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="tanggal_selesai" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Selesai</label>
                            <input id="tanggal_selesai" name="tanggal_selesai" type="date" value="{{ old('tanggal_selesai') }}" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700" required />
                            @error('tanggal_selesai')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700">Simpan Waktu</button>
                    </form>
                </section>
                @endif

                @if ($isAdmin)
                <section class="calendar-card rounded-3xl border border-slate-200/70 bg-white/90 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
                    <div class="flex items-center justify-between">
                        <h3 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Edit Waktu</h3>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ $masterWaktu->count() }} Data</span>
                    </div>

                    <div class="mt-4 max-h-96 space-y-3 overflow-y-auto pr-1">
                        @forelse ($masterWaktu as $waktu)
                            <form action="{{ route('katalog.waktu.update', ['id' => $kalender->id_kalender, 'idWaktu' => $waktu->id_waktu]) }}" method="POST" class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                @csrf
                                @method('PUT')
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">ID Waktu {{ $waktu->id_waktu }}</p>
                                <div class="mt-2 grid gap-2">
                                    <input name="tanggal_mulai" type="date" value="{{ \Carbon\Carbon::parse($waktu->tanggal_mulai)->format('Y-m-d') }}" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700" required />
                                    <input name="tanggal_selesai" type="date" value="{{ \Carbon\Carbon::parse($waktu->tanggal_selesai)->format('Y-m-d') }}" class="block w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-sm text-slate-700" required />
                                </div>
                                <button type="submit" class="mt-2 w-full rounded-lg bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 ring-1 ring-slate-300 transition hover:bg-slate-100">Update Waktu</button>
                            </form>
                        @empty
                            <article class="rounded-xl border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-sm text-slate-500">
                                Belum ada data waktu untuk pelatihan ini.
                            </article>
                        @endforelse
                    </div>
                </section>
                @endif
            </aside>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-collapse-target]').forEach((button) => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-collapse-target');
                const target = document.getElementById(targetId);
                if (!target) return;

                const isHidden = target.classList.contains('hidden');
                target.classList.toggle('hidden', !isHidden);
                button.textContent = isHidden
                    ? button.getAttribute('data-label-less')
                    : button.getAttribute('data-label-more');
            });
        });
    </script>
</body>
</html>
