<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Katalog Pelatihan</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="calendar-surface min-h-screen text-slate-800 antialiased">
    @php
        $masterKalender = $masterKalender ?? collect();
        $tahunOptions = $tahunOptions ?? collect();
        $searchNama = $searchNama ?? '';
        $searchTahun = $searchTahun ?? '';
        $totalMasterSemua = $totalMasterSemua ?? (method_exists($masterKalender, 'count') ? $masterKalender->count() : 0);
        $totalHasilFilter = $totalHasilFilter ?? (method_exists($masterKalender, 'count') ? $masterKalender->count() : 0);
    @endphp

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <header class="calendar-card rounded-3xl border border-white/70 bg-white/85 p-6 shadow-2xl shadow-slate-900/10 backdrop-blur sm:p-8">
            <p class="font-['Instrument_Sans'] text-sm font-semibold uppercase tracking-[0.2em] text-teal-700">BPSDM 2026</p>
            <h1 class="mt-2 font-['Space_Grotesk'] text-3xl font-bold text-slate-900 sm:text-4xl">Katalog Daftar Pelatihan</h1>
            <p class="mt-3 max-w-2xl text-sm text-slate-600 sm:text-base">Data katalog di bawah ini langsung berasal dari master kalender yang tersimpan di database.</p>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <nav class="inline-flex items-center gap-1 rounded-full bg-slate-100 p-1 ring-1 ring-slate-200">
                    <a href="{{ route('katalog.index') }}" class="rounded-full bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm">Katalog</a>
                    <a href="{{ route('dashboard.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Dashboard</a>
                    <a href="{{ route('kalender.index') }}" class="rounded-full px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white hover:text-slate-900">Kalender</a>
                </nav>
                <span class="rounded-full bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200">Total Master: {{ $totalMasterSemua }}</span>
            </div>
        </header>

        <section class="calendar-card mt-6 rounded-3xl border border-slate-200/80 bg-white/90 p-5 shadow-xl shadow-slate-900/10 backdrop-blur sm:p-6">
            <div class="grid gap-5 lg:grid-cols-[1fr_1.3fr]">
                <div>
                    <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Upload Master Kalender</h2>
                    <p class="mt-2 text-sm text-slate-600">Unggah file CSV atau Excel (`.xlsx`) dengan kolom: `nama_kalender`, `tahun_kalender`, `total_peserta`.</p>

                    @if (session('success_upload'))
                        <p class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">{{ session('success_upload') }}</p>
                    @endif

                    @if (session('error_upload'))
                        <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-medium text-rose-700">{{ session('error_upload') }}</p>
                    @endif

                    <form id="upload-master-form" action="{{ route('master-kalender.upload') }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-3">
                        @csrf
                        <label for="file_master_kalender" class="block text-sm font-semibold text-slate-700">File Master Kalender</label>
                        <input
                            id="file_master_kalender"
                            name="file_master_kalender"
                            type="file"
                            accept=".csv,.xlsx"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white"
                            required
                        />
                        @error('file_master_kalender')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror

                        <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">
                            Upload Data
                        </button>
                    </form>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <h3 class="font-['Space_Grotesk'] text-xl font-bold text-slate-900">Preview Upload File</h3>
                        <span id="preview-count" class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">0 Baris</span>
                    </div>

                    <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-600">Nama Kalender</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-600">Tahun</th>
                                    <th class="px-3 py-2 text-left font-semibold text-slate-600">Total Peserta</th>
                                </tr>
                            </thead>
                            <tbody id="preview-body" class="divide-y divide-slate-200 bg-white">
                                <tr id="preview-empty-row">
                                    <td colspan="3" class="px-3 py-5 text-center text-slate-500">Belum ada file dipilih.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-['Space_Grotesk'] text-2xl font-bold text-slate-900">Katalog Dari Master Kalender</h2>
                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">{{ $totalHasilFilter }} Data</span>
            </div>

            <form method="GET" action="{{ route('katalog.index') }}" class="mb-10 rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="nama" class="mb-1 block text-sm font-semibold text-slate-700">Cari Nama Master Agenda</label>
                        <input id="nama" name="nama" type="text" value="{{ $searchNama }}" placeholder="Contoh: Pelatihan Kepemimpinan" class="block h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700" />
                    </div>
                    <div>
                        <label for="tahun" class="mb-1 block text-sm font-semibold text-slate-700">Filter Tahun</label>
                        <select id="tahun" name="tahun" class="block h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700">
                            <option value="">Semua Tahun</option>
                            @foreach ($tahunOptions as $tahunOption)
                                <option value="{{ $tahunOption }}" {{ (string) $searchTahun === (string) $tahunOption ? 'selected' : '' }}>{{ $tahunOption }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="submit" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-700">Cari</button>
                    <a href="{{ route('katalog.index') }}" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">Reset</a>
                </div>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($masterKalender as $item)
                    <article class="calendar-card rounded-2xl border border-slate-200/80 bg-white/90 p-5 shadow-xl shadow-slate-900/10 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Master Kalender</p>
                        <h3 class="mt-2 font-['Space_Grotesk'] text-xl font-bold text-slate-900">{{ $item->nama_kalender }}</h3>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2">
                                <dt class="text-slate-500">Tahun</dt>
                                <dd class="font-semibold text-slate-800">{{ $item->tahun_kalender }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2">
                                <dt class="text-slate-500">Total Peserta</dt>
                                <dd class="font-semibold text-slate-800">{{ number_format((int) $item->total_peserta, 0, ',', '.') }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-lg bg-slate-100 px-3 py-2">
                                <dt class="text-slate-500">ID Kalender</dt>
                                <dd class="font-semibold text-slate-800">{{ $item->id_kalender }}</dd>
                            </div>
                        </dl>

                        <a href="{{ route('katalog.detail', ['id' => $item->id_kalender]) }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                            Detail
                        </a>
                    </article>
                @empty
                    <article class="calendar-card rounded-2xl border border-dashed border-slate-300 bg-white/80 p-6 text-center text-slate-500 sm:col-span-2 xl:col-span-3">
                        Data master kalender belum tersedia. Silakan upload file terlebih dahulu.
                    </article>
                @endforelse
            </div>

            @if (method_exists($masterKalender, 'hasPages') && $masterKalender->hasPages())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white/90 p-3 shadow-sm">
                    {{ $masterKalender->links() }}
                </div>
            @endif
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        const uploadForm = document.getElementById('upload-master-form');
        const fileInput = document.getElementById('file_master_kalender');
        const previewBody = document.getElementById('preview-body');
        const previewCount = document.getElementById('preview-count');

        const setEmptyPreview = (message = 'Belum ada file dipilih.') => {
            previewBody.innerHTML = `<tr id="preview-empty-row"><td colspan="3" class="px-3 py-5 text-center text-slate-500">${message}</td></tr>`;
            previewCount.textContent = '0 Baris';
        };

        const renderRows = (rows) => {
            if (!rows.length) {
                setEmptyPreview('Tidak ada data valid di file.');
                return;
            }

            previewBody.innerHTML = '';

            rows.forEach((row) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 py-2 text-slate-900">${row.nama_kalender || ''}</td>
                    <td class="px-3 py-2 text-slate-700">${row.tahun_kalender || ''}</td>
                    <td class="px-3 py-2 text-slate-700">${row.total_peserta || 0}</td>
                `;
                previewBody.appendChild(tr);
            });

            previewCount.textContent = `${rows.length} Baris`;
        };

        const normalizeRows = (rows) => {
            if (!rows || !rows.length) return [];

            const firstRow = rows[0].map((value) => String(value ?? '').trim().toLowerCase());
            const hasHeader = firstRow.includes('nama_kalender') || firstRow.includes('nama kalender') || firstRow.includes('tahun_kalender');
            const dataRows = hasHeader ? rows.slice(1) : rows;

            return dataRows
                .map((row) => ({
                    nama_kalender: String(row[0] ?? '').trim(),
                    tahun_kalender: String(row[1] ?? '').trim(),
                    total_peserta: String(row[2] ?? '0').trim(),
                }))
                .filter((row) => row.nama_kalender !== '' || row.tahun_kalender !== '' || row.total_peserta !== '');
        };

        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files?.[0];

            if (!file) {
                setEmptyPreview();
                return;
            }

            try {
                const extension = file.name.split('.').pop().toLowerCase();
                const reader = new FileReader();

                reader.onload = (e) => {
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    const firstSheetName = workbook.SheetNames[0];
                    const firstSheet = workbook.Sheets[firstSheetName];
                    const rows = XLSX.utils.sheet_to_json(firstSheet, { header: 1, raw: false });
                    renderRows(normalizeRows(rows));
                };

                if (extension === 'csv' || extension === 'xlsx') {
                    reader.readAsArrayBuffer(file);
                } else {
                    setEmptyPreview('Format file tidak didukung. Gunakan CSV atau XLSX.');
                }
            } catch (error) {
                setEmptyPreview('Gagal membaca file. Periksa format file Anda.');
            }
        });

    </script>
</body>
</html>
