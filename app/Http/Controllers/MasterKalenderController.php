<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;
use ZipArchive;

class MasterKalenderController extends Controller
{
    public function index(Request $request)
    {
        $nama = trim((string) $request->query('nama', ''));
        $tahun = trim((string) $request->query('tahun', ''));

        if (Schema::hasTable('master_kalender')) {
            $query = DB::table('master_kalender');

            if ($nama !== '') {
                $query->where('nama_kalender', 'like', '%' . $nama . '%');
            }

            if ($tahun !== '') {
                $query->where('tahun_kalender', (int) $tahun);
            }

            $masterKalender = $query
                ->orderByDesc('tahun_kalender')
                ->orderBy('nama_kalender')
                ->paginate(9)
                ->withQueryString();

            $tahunOptions = DB::table('master_kalender')
                ->select('tahun_kalender')
                ->distinct()
                ->orderByDesc('tahun_kalender')
                ->pluck('tahun_kalender');
            $totalMasterSemua = DB::table('master_kalender')->count();
            $totalHasilFilter = $masterKalender->total();
        } else {
            $masterKalender = collect();
            $tahunOptions = collect();
            $totalMasterSemua = 0;
            $totalHasilFilter = 0;
        }

        return view('pelatihan.index', [
            'masterKalender' => $masterKalender,
            'searchNama' => $nama,
            'searchTahun' => $tahun,
            'tahunOptions' => $tahunOptions,
            'totalMasterSemua' => $totalMasterSemua,
            'totalHasilFilter' => $totalHasilFilter,
        ]);
    }

    public function dashboard()
    {
        $timezone = 'Asia/Jakarta';
        $today = \Carbon\Carbon::today($timezone);

        $totalMasterKalender = Schema::hasTable('master_kalender') ? DB::table('master_kalender')->count() : 0;
        $totalMasterWaktu = Schema::hasTable('master_waktu') ? DB::table('master_waktu')->count() : 0;
        $totalDetailAktivitas = Schema::hasTable('detail_aktivitas') ? DB::table('detail_aktivitas')->count() : 0;
        $totalPeserta = Schema::hasTable('master_kalender') ? (int) DB::table('master_kalender')->sum('total_peserta') : 0;

        $rekapTahun = Schema::hasTable('master_kalender')
            ? DB::table('master_kalender')
                ->select(
                    'tahun_kalender',
                    DB::raw('COUNT(*) as total_pelatihan'),
                    DB::raw('COALESCE(SUM(total_peserta), 0) as total_peserta')
                )
                ->groupBy('tahun_kalender')
                ->orderBy('tahun_kalender')
                ->get()
            : collect();

        $rekapMetode = Schema::hasTable('detail_aktivitas')
            ? DB::table('detail_aktivitas')
                ->select(
                    DB::raw('metode_pembelajaran as metode'),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('metode_pembelajaran')
                ->orderBy('metode_pembelajaran')
                ->get()
            : collect();

        $startWindow = $today->copy()->startOfMonth()->subMonths(11);
        $endWindow = $today->copy()->endOfMonth();
        $labels12Bulan = [];
        $valuesByMonth = [];

        for ($month = $startWindow->copy(); $month->lte($endWindow); $month->addMonth()) {
            $key = $month->format('Y-m');
            $labels12Bulan[] = ucfirst($month->locale('id')->isoFormat('MMM YYYY'));
            $valuesByMonth[$key] = 0;
        }

        if (Schema::hasTable('detail_aktivitas')) {
            $rows = DB::table('detail_aktivitas')
                ->select('tanggal_aktivitas')
                ->whereBetween('tanggal_aktivitas', [$startWindow->format('Y-m-d'), $endWindow->format('Y-m-d')])
                ->get();

            foreach ($rows as $row) {
                $monthKey = substr((string) $row->tanggal_aktivitas, 0, 7);

                if (array_key_exists($monthKey, $valuesByMonth)) {
                    $valuesByMonth[$monthKey]++;
                }
            }
        }

        return view('dashboard.index', [
            'summary' => [
                'total_master_kalender' => $totalMasterKalender,
                'total_master_waktu' => $totalMasterWaktu,
                'total_detail_aktivitas' => $totalDetailAktivitas,
                'total_peserta' => $totalPeserta,
            ],
            'rekapTahun' => $rekapTahun,
            'rekapMetode' => $rekapMetode,
            'labels12Bulan' => $labels12Bulan,
            'values12Bulan' => array_values($valuesByMonth),
        ]);
    }

    public function kalender(Request $request)
    {
        $katalogPelatihan = Schema::hasTable('master_kalender')
            ? DB::table('master_kalender')->orderByDesc('tahun_kalender')->orderBy('nama_kalender')->get()
            : collect();

        $masterWaktu = (Schema::hasTable('master_waktu') && Schema::hasTable('master_kalender'))
            ? DB::table('master_waktu')
                ->leftJoin('master_kalender', 'master_waktu.id_kalender', '=', 'master_kalender.id_kalender')
                ->select(
                    'master_waktu.id_waktu',
                    'master_waktu.id_kalender',
                    'master_waktu.tanggal_mulai',
                    'master_waktu.tanggal_selesai',
                    DB::raw("COALESCE(master_kalender.nama_kalender, CONCAT('[ID ', master_waktu.id_kalender, ' tidak ditemukan]')) AS nama_kalender"),
                    'master_kalender.tahun_kalender'
                )
                ->orderBy('master_waktu.tanggal_mulai')
                ->get()
            : collect();

        $aktivitasKalender = (Schema::hasTable('detail_aktivitas') && Schema::hasTable('master_kalender'))
            ? DB::table('detail_aktivitas')
                ->join('master_kalender', 'detail_aktivitas.id_kalender', '=', 'master_kalender.id_kalender')
                ->select(
                    'detail_aktivitas.tanggal_aktivitas',
                    'detail_aktivitas.nama_kegiatan',
                    'detail_aktivitas.metode_pembelajaran',
                    'detail_aktivitas.nama_pengajar',
                    'detail_aktivitas.id_kalender',
                    'master_kalender.nama_kalender'
                )
                ->orderBy('detail_aktivitas.tanggal_aktivitas')
                ->orderBy('detail_aktivitas.id_aktivitas')
                ->get()
            : collect();

        $timezone = 'Asia/Jakarta';
        $selectedDateParam = $request->query('selected_date');
        $selectedDate = null;

        if (is_string($selectedDateParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateParam) === 1) {
            try {
                $selectedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedDateParam, $timezone)->startOfDay();
            } catch (Throwable $exception) {
                $selectedDate = null;
            }
        }

        if (!$selectedDate) {
            $selectedDate = \Carbon\Carbon::today($timezone);
        }

        $agendaTerpilih = (Schema::hasTable('detail_aktivitas') && Schema::hasTable('master_kalender'))
            ? DB::table('detail_aktivitas')
                ->join('master_kalender', 'detail_aktivitas.id_kalender', '=', 'master_kalender.id_kalender')
                ->select(
                    'detail_aktivitas.id_aktivitas',
                    'detail_aktivitas.tanggal_aktivitas',
                    'detail_aktivitas.nama_kegiatan',
                    'detail_aktivitas.metode_pembelajaran',
                    'detail_aktivitas.nama_pengajar',
                    'master_kalender.nama_kalender'
                )
                ->whereDate('detail_aktivitas.tanggal_aktivitas', $selectedDate->format('Y-m-d'))
                ->orderBy('detail_aktivitas.id_aktivitas')
                ->get()
            : collect();

        return view('kalender.index', [
            'katalogPelatihan' => $katalogPelatihan,
            'masterWaktu' => $masterWaktu,
            'aktivitasKalender' => $aktivitasKalender,
            'selectedDate' => $selectedDate,
            'agendaTerpilih' => $agendaTerpilih,
        ]);
    }

    public function detail(Request $request, int $id)
    {
        if (!Schema::hasTable('master_kalender')) {
            abort(404);
        }

        $kalender = DB::table('master_kalender')
            ->where('id_kalender', $id)
            ->first();

        if (!$kalender) {
            abort(404);
        }

        $masterWaktu = Schema::hasTable('master_waktu')
            ? DB::table('master_waktu')
                ->where('id_kalender', $id)
                ->orderBy('tanggal_mulai')
                ->get()
            : collect();

        $timezone = 'Asia/Jakarta';
        $today = \Carbon\Carbon::today($timezone);
        $monthParam = $request->query('month');

        if (is_string($monthParam) && preg_match('/^\d{4}-\d{2}$/', $monthParam) === 1) {
            [$year, $month] = array_map('intval', explode('-', $monthParam));
            $displayedMonth = ($month >= 1 && $month <= 12)
                ? \Carbon\Carbon::createFromDate($year, $month, 1, $timezone)->startOfMonth()
                : $today->copy()->startOfMonth();
        } else {
            $firstWaktu = $masterWaktu->first();
            if ($firstWaktu) {
                $displayedMonth = \Carbon\Carbon::parse($firstWaktu->tanggal_mulai, $timezone)->startOfMonth();
            } else {
                $displayedMonth = $today->copy()->startOfMonth();
            }
        }

        $selectedDateParam = $request->query('selected_date');
        $selectedDate = null;

        if (is_string($selectedDateParam) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDateParam) === 1) {
            try {
                $selectedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $selectedDateParam, $timezone)->startOfDay();
            } catch (Throwable $exception) {
                $selectedDate = null;
            }
        }

        if (!$selectedDate) {
            $selectedDate = $displayedMonth->copy()->startOfMonth();
        }

        $monthStart = $displayedMonth->copy()->startOfMonth()->format('Y-m-d');
        $monthEnd = $displayedMonth->copy()->endOfMonth()->format('Y-m-d');

        $aktivitasBulan = Schema::hasTable('detail_aktivitas')
            ? DB::table('detail_aktivitas')
                ->where('id_kalender', $id)
                ->whereBetween('tanggal_aktivitas', [$monthStart, $monthEnd])
                ->orderBy('tanggal_aktivitas')
                ->orderBy('id_aktivitas')
                ->get()
            : collect();

        $detailAktivitas = Schema::hasTable('detail_aktivitas')
            ? DB::table('detail_aktivitas')
                ->where('id_kalender', $id)
                ->whereDate('tanggal_aktivitas', $selectedDate->format('Y-m-d'))
                ->orderBy('id_aktivitas')
                ->get()
            : collect();

        $selectedDateInRange = $this->isDateWithinMasterWaktu($id, $selectedDate->format('Y-m-d'));

        return view('pelatihan.detail', [
            'kalender' => $kalender,
            'masterWaktu' => $masterWaktu,
            'displayedMonth' => $displayedMonth,
            'selectedDate' => $selectedDate,
            'aktivitasBulan' => $aktivitasBulan,
            'detailAktivitas' => $detailAktivitas,
            'selectedDateInRange' => $selectedDateInRange,
        ]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file_master_kalender' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        if (!Schema::hasTable('master_kalender')) {
            return back()->with('error_upload', 'Tabel master_kalender belum tersedia. Jalankan migrasi terlebih dahulu.');
        }

        $file = $validated['file_master_kalender'];
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $rows = $extension === 'csv'
                ? $this->parseCsv($file->getRealPath())
                : $this->parseXlsx($file->getRealPath());
        } catch (Throwable $exception) {
            return back()->with('error_upload', $exception->getMessage());
        }

        if (count($rows) === 0) {
            return back()->with('error_upload', 'Data tidak ditemukan. Pastikan file berisi kolom nama_kalender, tahun_kalender, dan total_peserta.');
        }

        $payload = [];

        foreach ($rows as $index => $row) {
            $namaKalender = trim((string) ($row['nama_kalender'] ?? ''));
            $tahunKalender = trim((string) ($row['tahun_kalender'] ?? ''));
            $totalPeserta = trim((string) ($row['total_peserta'] ?? '0'));

            if ($namaKalender === '' || $tahunKalender === '') {
                continue;
            }

            if (!preg_match('/^\d{4}$/', $tahunKalender)) {
                return back()->with('error_upload', 'Tahun tidak valid pada baris ke-' . ($index + 1) . '. Gunakan format 4 digit, contoh: 2026.');
            }

            $totalPeserta = preg_replace('/[^\d]/', '', $totalPeserta ?? '');

            if ($totalPeserta === '') {
                $totalPeserta = '0';
            }

            if (!preg_match('/^\d+$/', $totalPeserta)) {
                return back()->with('error_upload', 'Total peserta tidak valid pada baris ke-' . ($index + 1) . '. Gunakan angka bulat, contoh: 120.');
            }

            $payload[] = [
                'nama_kalender' => $namaKalender,
                'tahun_kalender' => (int) $tahunKalender,
                'total_peserta' => (int) $totalPeserta,
            ];
        }

        if (count($payload) === 0) {
            return back()->with('error_upload', 'Tidak ada data valid untuk disimpan.');
        }

        DB::table('master_kalender')->insert($payload);

        return redirect('/')
            ->with('success_upload', count($payload) . ' data master kalender berhasil diunggah.');
    }

    public function storeWaktu(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_kalender' => ['required', 'integer'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        if (!Schema::hasTable('master_waktu') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_waktu', 'Tabel master_waktu atau master_kalender belum tersedia. Jalankan migrasi terlebih dahulu.');
        }

        $masterKalenderExists = DB::table('master_kalender')
            ->where('id_kalender', (int) $validated['id_kalender'])
            ->exists();

        if (!$masterKalenderExists) {
            return back()->with('error_waktu', 'Pelatihan yang dipilih tidak ditemukan.');
        }

        $tanggalMulai = \Carbon\Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d');
        $tanggalSelesai = \Carbon\Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d');

        DB::table('master_waktu')->insert([
            'id_kalender' => (int) $validated['id_kalender'],
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        return redirect()->route('kalender.index', [
            'month' => \Carbon\Carbon::parse($tanggalMulai)->format('Y-m'),
        ])
            ->with('success_waktu', 'Master waktu berhasil ditambahkan.');
    }

    public function storeDetailWaktu(Request $request, int $id): RedirectResponse
    {
        if (!Schema::hasTable('master_waktu') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_waktu_detail', 'Tabel master_waktu atau master_kalender belum tersedia.');
        }

        $masterKalenderExists = DB::table('master_kalender')
            ->where('id_kalender', $id)
            ->exists();

        if (!$masterKalenderExists) {
            abort(404);
        }

        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $tanggalMulai = \Carbon\Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d');
        $tanggalSelesai = \Carbon\Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d');

        DB::table('master_waktu')->insert([
            'id_kalender' => $id,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => $tanggalSelesai,
        ]);

        return redirect()->route('katalog.detail', [
            'id' => $id,
            'month' => \Carbon\Carbon::parse($tanggalMulai)->format('Y-m'),
        ])->with('success_waktu_detail', 'Waktu berhasil ditambahkan.');
    }

    public function updateDetailWaktu(Request $request, int $id, int $idWaktu): RedirectResponse
    {
        if (!Schema::hasTable('master_waktu') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_waktu_detail', 'Tabel master_waktu atau master_kalender belum tersedia.');
        }

        $validated = $request->validate([
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $waktu = DB::table('master_waktu')
            ->where('id_waktu', $idWaktu)
            ->where('id_kalender', $id)
            ->first();

        if (!$waktu) {
            return back()->with('error_waktu_detail', 'Data waktu tidak ditemukan untuk pelatihan ini.');
        }

        $tanggalMulai = \Carbon\Carbon::parse($validated['tanggal_mulai'])->format('Y-m-d');
        $tanggalSelesai = \Carbon\Carbon::parse($validated['tanggal_selesai'])->format('Y-m-d');

        DB::table('master_waktu')
            ->where('id_waktu', $idWaktu)
            ->update([
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
            ]);

        return redirect()->route('katalog.detail', [
            'id' => $id,
            'month' => \Carbon\Carbon::parse($tanggalMulai)->format('Y-m'),
        ])->with('success_waktu_detail', 'Waktu berhasil diperbarui.');
    }

    public function storeDetailAktivitas(Request $request, int $id): RedirectResponse
    {
        if (!Schema::hasTable('detail_aktivitas') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_aktivitas_detail', 'Tabel detail_aktivitas atau master_kalender belum tersedia.');
        }
        if (!Schema::hasTable('master_waktu')) {
            return back()->with('error_aktivitas_detail', 'Tabel master_waktu belum tersedia.');
        }

        $masterKalenderExists = DB::table('master_kalender')
            ->where('id_kalender', $id)
            ->exists();

        if (!$masterKalenderExists) {
            abort(404);
        }

        $hasMasterWaktu = DB::table('master_waktu')
            ->where('id_kalender', $id)
            ->exists();

        if (!$hasMasterWaktu) {
            return back()->with('error_aktivitas_detail', 'Belum ada master waktu untuk pelatihan ini. Tambahkan master waktu terlebih dahulu.');
        }

        $validated = $request->validate([
            'tanggal_mulai_aktivitas' => ['required', 'date'],
            'tanggal_selesai_aktivitas' => ['required', 'date', 'after_or_equal:tanggal_mulai_aktivitas'],
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'metode_pembelajaran' => ['required', 'in:klasikal,e-learning,mooc,cop'],
            'nama_pengajar' => ['required', 'string', 'max:255'],
            'include_weekend' => ['required', 'boolean'],
        ]);

        $tanggalMulai = \Carbon\Carbon::parse($validated['tanggal_mulai_aktivitas'])->startOfDay();
        $tanggalSelesai = \Carbon\Carbon::parse($validated['tanggal_selesai_aktivitas'])->startOfDay();
        $includeWeekend = (bool) $validated['include_weekend'];

        $maxRangeDays = 366;
        if ($tanggalMulai->diffInDays($tanggalSelesai) + 1 > $maxRangeDays) {
            return back()->with('error_aktivitas_detail', 'Rentang tanggal terlalu panjang. Maksimal 366 hari.');
        }

        $payload = [];
        $cursor = $tanggalMulai->copy();

        while ($cursor->lte($tanggalSelesai)) {
            if (!$includeWeekend && $cursor->isWeekend()) {
                $cursor->addDay();
                continue;
            }

            $tanggalAktivitas = $cursor->format('Y-m-d');

            if (!$this->isDateWithinMasterWaktu($id, $tanggalAktivitas)) {
                return back()->with('error_aktivitas_detail', 'Tanggal ' . $cursor->locale('id')->isoFormat('D MMMM YYYY') . ' berada di luar rentang master waktu pelatihan.');
            }

            $payload[] = [
                'id_kalender' => $id,
                'tanggal_aktivitas' => $tanggalAktivitas,
                'nama_kegiatan' => trim($validated['nama_kegiatan']),
                'metode_pembelajaran' => $validated['metode_pembelajaran'],
                'nama_pengajar' => trim($validated['nama_pengajar']),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $cursor->addDay();
        }

        if (count($payload) === 0) {
            return back()->with('error_aktivitas_detail', 'Rentang tanggal tidak memiliki hari yang bisa disimpan. Jika perlu, aktifkan opsi termasuk weekend.');
        }

        DB::table('detail_aktivitas')->insert($payload);

        return redirect()->route('katalog.detail', [
            'id' => $id,
            'month' => $tanggalMulai->format('Y-m'),
            'selected_date' => $tanggalMulai->format('Y-m-d'),
        ])->with('success_aktivitas_detail', count($payload) . ' detail aktivitas berhasil disimpan.');
    }

    public function updateDetailAktivitas(Request $request, int $id, int $idAktivitas): RedirectResponse
    {
        if (!Schema::hasTable('detail_aktivitas') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_aktivitas_edit', 'Tabel detail_aktivitas atau master_kalender belum tersedia.');
        }
        if (!Schema::hasTable('master_waktu')) {
            return back()->with('error_aktivitas_edit', 'Tabel master_waktu belum tersedia.');
        }

        $masterKalenderExists = DB::table('master_kalender')
            ->where('id_kalender', $id)
            ->exists();

        if (!$masterKalenderExists) {
            abort(404);
        }

        $hasMasterWaktu = DB::table('master_waktu')
            ->where('id_kalender', $id)
            ->exists();

        if (!$hasMasterWaktu) {
            return back()->with('error_aktivitas_edit', 'Belum ada master waktu untuk pelatihan ini. Tambahkan master waktu terlebih dahulu.');
        }

        $aktivitas = DB::table('detail_aktivitas')
            ->where('id_aktivitas', $idAktivitas)
            ->where('id_kalender', $id)
            ->first();

        if (!$aktivitas) {
            return back()->with('error_aktivitas_edit', 'Data aktivitas tidak ditemukan untuk pelatihan ini.');
        }

        $validated = $request->validate([
            'edit_tanggal_aktivitas' => ['required', 'date'],
            'edit_nama_kegiatan' => ['required', 'string', 'max:255'],
            'edit_metode_pembelajaran' => ['required', 'in:klasikal,e-learning,mooc,cop'],
            'edit_nama_pengajar' => ['required', 'string', 'max:255'],
        ]);

        $tanggalAktivitas = \Carbon\Carbon::parse($validated['edit_tanggal_aktivitas'])->format('Y-m-d');

        if (!$this->isDateWithinMasterWaktu($id, $tanggalAktivitas)) {
            return back()->with('error_aktivitas_edit', 'Tanggal aktivitas harus berada dalam rentang master waktu pelatihan.');
        }

        DB::table('detail_aktivitas')
            ->where('id_aktivitas', $idAktivitas)
            ->where('id_kalender', $id)
            ->update([
                'tanggal_aktivitas' => $tanggalAktivitas,
                'nama_kegiatan' => trim($validated['edit_nama_kegiatan']),
                'metode_pembelajaran' => $validated['edit_metode_pembelajaran'],
                'nama_pengajar' => trim($validated['edit_nama_pengajar']),
                'updated_at' => now(),
            ]);

        return redirect()->route('katalog.detail', [
            'id' => $id,
            'month' => \Carbon\Carbon::parse($tanggalAktivitas)->format('Y-m'),
            'selected_date' => $tanggalAktivitas,
        ])->with('success_aktivitas_edit', 'Detail aktivitas berhasil diperbarui.');
    }

    public function destroyDetailAktivitas(int $id, int $idAktivitas): RedirectResponse
    {
        if (!Schema::hasTable('detail_aktivitas') || !Schema::hasTable('master_kalender')) {
            return back()->with('error_aktivitas_delete', 'Tabel detail_aktivitas atau master_kalender belum tersedia.');
        }

        $masterKalenderExists = DB::table('master_kalender')
            ->where('id_kalender', $id)
            ->exists();

        if (!$masterKalenderExists) {
            abort(404);
        }

        $aktivitas = DB::table('detail_aktivitas')
            ->where('id_aktivitas', $idAktivitas)
            ->where('id_kalender', $id)
            ->first();

        if (!$aktivitas) {
            return back()->with('error_aktivitas_delete', 'Data aktivitas tidak ditemukan untuk pelatihan ini.');
        }

        $tanggalAktivitas = \Carbon\Carbon::parse($aktivitas->tanggal_aktivitas)->format('Y-m-d');

        DB::table('detail_aktivitas')
            ->where('id_aktivitas', $idAktivitas)
            ->where('id_kalender', $id)
            ->delete();

        return redirect()->route('katalog.detail', [
            'id' => $id,
            'month' => \Carbon\Carbon::parse($tanggalAktivitas)->format('Y-m'),
            'selected_date' => $tanggalAktivitas,
        ])->with('success_aktivitas_delete', 'Detail aktivitas berhasil dihapus.');
    }

    private function isDateWithinMasterWaktu(int $idKalender, string $tanggal): bool
    {
        if (!Schema::hasTable('master_waktu')) {
            return false;
        }

        return DB::table('master_waktu')
            ->where('id_kalender', $idKalender)
            ->whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_selesai', '>=', $tanggal)
            ->exists();
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV tidak dapat dibuka.');
        }

        $rows = [];
        $isHeaderChecked = false;
        $delimiter = ',';

        $firstLine = fgets($handle);
        if ($firstLine !== false) {
            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) < 2) {
                continue;
            }

            $first = trim((string) $data[0]);
            $second = trim((string) $data[1]);
            $third = trim((string) ($data[2] ?? '0'));

            if (!$isHeaderChecked) {
                $isHeaderChecked = true;

                $isHeader = in_array(strtolower($first), ['nama_kalender', 'nama kalender', 'nama'], true)
                    || in_array(strtolower($second), ['tahun_kalender', 'tahun kalender', 'tahun'], true)
                    || in_array(strtolower($third), ['total_peserta', 'total peserta', 'peserta'], true);

                if ($isHeader) {
                    continue;
                }
            }

            $rows[] = [
                'nama_kalender' => $first,
                'tahun_kalender' => $second,
                'total_peserta' => $third,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsx(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak dapat dibaca.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);

            if ($xml !== false && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }

                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $sheetPath = $this->resolveFirstSheetPath($workbookXml, $workbookRelsXml);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Sheet pertama tidak ditemukan pada file Excel.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        $rowIndex = 0;

        foreach ($xml->sheetData->row as $row) {
            $rowIndex++;
            $values = [];

            foreach ($row->c as $cell) {
                $value = (string) ($cell->v ?? '');
                $type = (string) ($cell['t'] ?? '');
                $cellRef = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromCellRef($cellRef);

                if ($type === 's') {
                    $sharedIndex = (int) $value;
                    $cellValue = $sharedStrings[$sharedIndex] ?? '';
                } else {
                    $cellValue = $value;
                }

                $values[$columnIndex] = $cellValue;
            }

            if (count($values) < 2) {
                continue;
            }

            $first = trim((string) ($values[0] ?? ''));
            $second = trim((string) ($values[1] ?? ''));
            $third = trim((string) ($values[2] ?? '0'));

            if ($rowIndex === 1) {
                $isHeader = in_array(strtolower($first), ['nama_kalender', 'nama kalender', 'nama'], true)
                    || in_array(strtolower($second), ['tahun_kalender', 'tahun kalender', 'tahun'], true)
                    || in_array(strtolower($third), ['total_peserta', 'total peserta', 'peserta'], true);

                if ($isHeader) {
                    continue;
                }
            }

            $rows[] = [
                'nama_kalender' => $first,
                'tahun_kalender' => $second,
                'total_peserta' => $third,
            ];
        }

        return $rows;
    }

    private function parseCsvWaktu(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File CSV master waktu tidak dapat dibuka.');
        }

        $rows = [];
        $isHeaderChecked = false;
        $delimiter = ',';

        $firstLine = fgets($handle);
        if ($firstLine !== false) {
            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);
        }

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($data) < 3) {
                continue;
            }

            $first = trim((string) $data[0]);
            $second = trim((string) $data[1]);
            $third = trim((string) $data[2]);

            if (!$isHeaderChecked) {
                $isHeaderChecked = true;

                $isHeader = in_array(strtolower($first), ['id_kalender', 'id kalender', 'id'], true)
                    || in_array(strtolower($second), ['tanggal_mulai', 'tanggal mulai', 'mulai'], true)
                    || in_array(strtolower($third), ['tanggal_selesai', 'tanggal selesai', 'selesai'], true);

                if ($isHeader) {
                    continue;
                }
            }

            $rows[] = [
                'id_kalender' => $first,
                'tanggal_mulai' => $second,
                'tanggal_selesai' => $third,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsxWaktu(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel master waktu tidak dapat dibaca.');
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml !== false) {
            $xml = @simplexml_load_string($sharedStringsXml);

            if ($xml !== false && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                        continue;
                    }

                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $workbookRelsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        $sheetPath = $this->resolveFirstSheetPath($workbookXml, $workbookRelsXml);
        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();

        if ($sheetXml === false) {
            throw new RuntimeException('Sheet pertama tidak ditemukan pada file Excel master waktu.');
        }

        $xml = @simplexml_load_string($sheetXml);
        if ($xml === false || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        $rowIndex = 0;

        foreach ($xml->sheetData->row as $row) {
            $rowIndex++;
            $values = [];

            foreach ($row->c as $cell) {
                $value = (string) ($cell->v ?? '');
                $type = (string) ($cell['t'] ?? '');
                $cellRef = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnIndexFromCellRef($cellRef);

                if ($type === 's') {
                    $sharedIndex = (int) $value;
                    $cellValue = $sharedStrings[$sharedIndex] ?? '';
                } else {
                    $cellValue = $value;
                }

                $values[$columnIndex] = $cellValue;
            }

            if (count($values) < 3) {
                continue;
            }

            $first = trim((string) ($values[0] ?? ''));
            $second = trim((string) ($values[1] ?? ''));
            $third = trim((string) ($values[2] ?? ''));

            if ($rowIndex === 1) {
                $isHeader = in_array(strtolower($first), ['id_kalender', 'id kalender', 'id'], true)
                    || in_array(strtolower($second), ['tanggal_mulai', 'tanggal mulai', 'mulai'], true)
                    || in_array(strtolower($third), ['tanggal_selesai', 'tanggal selesai', 'selesai'], true);

                if ($isHeader) {
                    continue;
                }
            }

            $rows[] = [
                'id_kalender' => $first,
                'tanggal_mulai' => $second,
                'tanggal_selesai' => $third,
            ];
        }

        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $bestDelimiter = ',';
        $bestCount = -1;

        foreach ($candidates as $candidate) {
            $count = substr_count($line, $candidate);
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    private function resolveFirstSheetPath(string|false $workbookXml, string|false $workbookRelsXml): string
    {
        if ($workbookXml === false || $workbookRelsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($workbookRelsXml);

        if ($workbook === false || $rels === false || !isset($workbook->sheets->sheet[0])) {
            return 'xl/worksheets/sheet1.xml';
        }

        $namespaceMain = $workbook->getNamespaces(true);
        $namespaceRel = $namespaceMain['r'] ?? null;

        $firstSheet = $workbook->sheets->sheet[0];
        $relationshipId = $namespaceRel
            ? (string) $firstSheet->attributes($namespaceRel, true)->id
            : '';

        if ($relationshipId === '') {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipNodes = $rels->xpath('//*[local-name()="Relationship"]');
        if (!is_array($relationshipNodes)) {
            return 'xl/worksheets/sheet1.xml';
        }

        foreach ($relationshipNodes as $node) {
            $id = (string) ($node['Id'] ?? '');
            if ($id !== $relationshipId) {
                continue;
            }

            $target = (string) ($node['Target'] ?? '');
            if ($target === '') {
                break;
            }

            $target = ltrim($target, '/');
            return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private function columnIndexFromCellRef(string $cellRef): int
    {
        if ($cellRef === '') {
            return 0;
        }

        if (!preg_match('/^[A-Z]+/i', $cellRef, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[0]);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max($index - 1, 0);
    }
}
