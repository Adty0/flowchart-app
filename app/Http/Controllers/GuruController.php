<?php

namespace App\Http\Controllers;

use App\Models\FlowchartSubmission;
use App\Models\User;
use App\Models\CaseStudy;
use App\Services\XlsxGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GuruController extends Controller
{
    /**
     * Tampilkan dashboard rekapitulasi penilaian untuk Guru (Dikelompokkan per kelas).
     */
    public function dashboard(Request $request)
    {
        $search = $request->query('search');
        $filterStatus = $request->query('status'); // 'lulus', 'remedial'
        $selectedClass = $request->query('class_name'); // '10 RPL 1', dst

        $query = FlowchartSubmission::with('user')->orderByDesc('created_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('class_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($filterStatus === 'lulus') {
            $query->where('score', '>=', 75);
        } elseif ($filterStatus === 'remedial') {
            $query->where('score', '<', 75);
        }

        if ($selectedClass) {
            $query->where('class_name', $selectedClass);
        }

        $submissions = $query->get();

        // Daftar Semua Kelas Unik (Dari Pengguna & Submission)
        $classList = User::where('role', 'siswa')
            ->whereNotNull('class_name')
            ->pluck('class_name')
            ->merge(FlowchartSubmission::whereNotNull('class_name')->pluck('class_name'))
            ->unique()
            ->sort()
            ->values();

        // Pengelompokan Data Submission Per Kelas
        $groupedSubmissions = $submissions->groupBy(function ($item) {
            return $item->class_name ?: 'Kelas Umum';
        });

        // Statistik Sesuai Rombel yang Dipilih (atau Keseluruhan jika Semua Rombel)
        $allSubmissions = FlowchartSubmission::all();
        $statSubmissions = $selectedClass
            ? $allSubmissions->where('class_name', $selectedClass)
            : $allSubmissions;

        $totalSubmissions = $statSubmissions->count();
        $totalSiswa = $selectedClass
            ? User::where('role', 'siswa')->where('class_name', $selectedClass)->count()
            : User::where('role', 'siswa')->count();
        $siswaSubmit = $statSubmissions->pluck('student_name')->unique()->count();
        $averageScore = $totalSubmissions > 0 ? round($statSubmissions->avg('score'), 1) : 0;
        $lulusCount = $statSubmissions->where('score', '>=', 75)->count();
        $remedialCount = $statSubmissions->where('score', '<', 75)->count();

        // Statistik Per Kelas (Untuk Ringkasan Komparasi Performa)
        $classStats = [];
        foreach ($classList as $cls) {
            $classSubs = $allSubmissions->where('class_name', $cls);
            $classTotal = $classSubs->count();
            $classAvg = $classTotal > 0 ? round($classSubs->avg('score'), 1) : 0;
            $classLulus = $classSubs->where('score', '>=', 75)->count();
            $classRemedial = $classSubs->where('score', '<', 75)->count();

            $classStats[$cls] = [
                'total'    => $classTotal,
                'avg'      => $classAvg,
                'lulus'    => $classLulus,
                'remedial' => $classRemedial,
            ];
        }

        // Daftar Studi Kasus untuk Tab Manajemen Studi Kasus
        $caseStudies = CaseStudy::withCount('submissions')->orderBy('id')->get();

        return view('guru.dashboard', compact(
            'submissions',
            'groupedSubmissions',
            'classList',
            'classStats',
            'caseStudies',
            'totalSubmissions',
            'totalSiswa',
            'siswaSubmit',
            'averageScore',
            'lulusCount',
            'remedialCount',
            'search',
            'filterStatus',
            'selectedClass'
        ));
    }

    /**
     * Tambahkan studi kasus baru beserta alur dan kunci koreksinya oleh guru.
     */
    public function storeCaseStudy(Request $request)
    {
        $validated = $request->validate([
            'title'               => 'required|string|max:150',
            'flow_type'           => 'required|in:sequential,branching',
            'icon'                => 'nullable|string|max:30',
            'badge'               => 'nullable|string|max:60',
            'formula'             => 'nullable|string|max:500',
            'description'         => 'required|string',
            'expected_inputs'     => 'nullable|string|max:1000',
            'expected_process'    => 'nullable|string|max:1000',
            'expected_decision'   => 'nullable|string|max:1000',
            'expected_yes_output' => 'nullable|string|max:255',
            'expected_no_output'  => 'nullable|string|max:255',
            'expected_outputs'    => 'nullable|string|max:1000',
            'extra_inputs'        => 'nullable|array',
            'extra_process'       => 'nullable|array',
            'extra_decisions'     => 'nullable|array',
            'extra_outputs'       => 'nullable|array',
        ]);

        // Gabungkan seluruh masukan input (utama + tambahan opsional)
        $inputsList = array_values(array_filter(array_merge(
            !empty($validated['expected_inputs']) ? array_map('trim', explode(',', $validated['expected_inputs'])) : [],
            isset($validated['extra_inputs']) ? array_map('trim', (array)$validated['extra_inputs']) : []
        )));
        $combinedInputs = implode(', ', array_unique($inputsList));

        // Gabungkan seluruh proses/rumus (utama + tambahan opsional)
        $processList = array_values(array_filter(array_merge(
            !empty($validated['expected_process']) ? array_map('trim', explode(',', $validated['expected_process'])) : [],
            isset($validated['extra_process']) ? array_map('trim', (array)$validated['extra_process']) : []
        )));
        $combinedProcess = implode(', ', array_unique($processList));

        // Gabungkan seluruh keputusan/syarat (utama + tambahan opsional)
        $decisionList = array_values(array_filter(array_merge(
            !empty($validated['expected_decision']) ? [$validated['expected_decision']] : [],
            isset($validated['extra_decisions']) ? array_map('trim', (array)$validated['extra_decisions']) : []
        )));
        $combinedDecision = implode(', ', array_unique($decisionList));

        // Gabungkan seluruh output luaran (utama + tambahan opsional)
        $outputList = array_values(array_filter(array_merge(
            !empty($validated['expected_outputs']) ? array_map('trim', explode(',', $validated['expected_outputs'])) : [],
            isset($validated['extra_outputs']) ? array_map('trim', (array)$validated['extra_outputs']) : []
        )));
        $combinedOutputs = implode(', ', array_unique($outputList));

        $slug = Str::slug($validated['title'], '_');
        if (empty($slug)) {
            $slug = 'kasus_' . time();
        }
        if (CaseStudy::where('case_key', $slug)->exists()) {
            $slug .= '_' . time();
        }

        $badge = $validated['badge'] ?: ($validated['flow_type'] === 'branching' ? 'Alur Percabangan' : 'Alur Sekuensial');
        $icon = $validated['icon'] ?: ($validated['flow_type'] === 'branching' ? '🔀' : '📐');

        // Susun struktur diagram kunci jawaban (reference flow)
        $steps = [];
        $steps[] = ['type' => 'terminator', 'label' => 'Mulai', 'desc' => 'Titik awal alur algoritma'];

        if (!empty($inputsList)) {
            foreach ($inputsList as $inp) {
                $steps[] = ['type' => 'io', 'label' => 'Input ' . $inp, 'desc' => 'Membaca masukan: ' . $inp];
            }
        } else {
            $steps[] = ['type' => 'io', 'label' => 'Input Data', 'desc' => 'Membaca data masukan'];
        }

        if ($validated['flow_type'] === 'branching') {
            if (!empty($decisionList)) {
                foreach ($decisionList as $dec) {
                    $steps[] = ['type' => 'decision', 'label' => $dec, 'desc' => 'Pemeriksaan kondisi logika'];
                }
            } else {
                $steps[] = ['type' => 'decision', 'label' => 'Syarat Kondisi?', 'desc' => 'Pemeriksaan kondisi logika'];
            }
            $steps[] = ['type' => 'io', 'label' => ($validated['expected_yes_output'] ? 'Cetak ' . $validated['expected_yes_output'] : 'Output Ya'), 'desc' => 'Cabang YA (Kondisi Terpenuhi)'];
            $steps[] = ['type' => 'io', 'label' => ($validated['expected_no_output'] ? 'Cetak ' . $validated['expected_no_output'] : 'Output Tidak'), 'desc' => 'Cabang TIDAK (Kondisi Tidak Terpenuhi)'];
        } else {
            if (!empty($processList)) {
                foreach ($processList as $prc) {
                    $steps[] = ['type' => 'process', 'label' => $prc, 'desc' => 'Rumus perhitungan: ' . $prc];
                }
            } else {
                $steps[] = ['type' => 'process', 'label' => 'Hitung / Proses Data', 'desc' => 'Komputasi manipulasi data'];
            }

            if (!empty($outputList)) {
                foreach ($outputList as $out) {
                    $steps[] = ['type' => 'io', 'label' => 'Cetak ' . $out, 'desc' => 'Menampilkan hasil nilai: ' . $out];
                }
            } else {
                $steps[] = ['type' => 'io', 'label' => 'Cetak Hasil', 'desc' => 'Menampilkan hasil luaran'];
            }
        }

        $steps[] = ['type' => 'terminator', 'label' => 'Selesai', 'desc' => 'Titik akhir alur algoritma'];

        $connections = $validated['flow_type'] === 'branching'
            ? [
                'Mulai ➔ Input (' . ($combinedInputs ?: 'Data') . ') ➔ Keputusan (' . ($combinedDecision ?: 'Kondisi') . ')',
                'Keputusan [Ya] ➔ ' . ($validated['expected_yes_output'] ?: 'Output Lulus') . ' ➔ Selesai',
                'Keputusan [Tidak] ➔ ' . ($validated['expected_no_output'] ?: 'Output Remedial') . ' ➔ Selesai',
            ]
            : [
                'Mulai ➔ Input (' . ($combinedInputs ?: 'Data') . ') ➔ Proses (' . ($combinedProcess ?: 'Rumus') . ') ➔ Output (' . ($combinedOutputs ?: 'Hasil') . ') ➔ Selesai'
            ];

        $referenceFlow = [
            'steps' => $steps,
            'connections' => $connections,
        ];

        $scoringRubric = $validated['flow_type'] === 'branching'
            ? ['terminator' => 20, 'input' => 15, 'decision' => 25, 'output' => 15, 'edges' => 25]
            : ['terminator' => 20, 'input' => 15, 'process' => 25, 'output' => 15, 'edges' => 25];

        CaseStudy::create([
            'case_key'            => $slug,
            'title'               => $validated['title'],
            'icon'                => $icon,
            'badge'               => $badge,
            'formula'             => $validated['formula'],
            'description'         => $validated['description'],
            'flow_type'           => $validated['flow_type'],
            'expected_inputs'     => $combinedInputs,
            'expected_process'    => $combinedProcess,
            'expected_decision'   => $combinedDecision,
            'expected_yes_output' => $validated['expected_yes_output'] ?? null,
            'expected_no_output'  => $validated['expected_no_output'] ?? null,
            'expected_outputs'    => $combinedOutputs,
            'reference_flow'      => $referenceFlow,
            'scoring_rubric'      => $scoringRubric,
            'is_default'          => false,
            'is_active'           => true,
            'created_by'          => Auth::id(),
        ]);

        return redirect()->route('guru.dashboard', ['tab' => 'studi_kasus'])
            ->with('success', 'Studi kasus baru "' . $validated['title'] . '" berhasil ditambahkan beserta alur dan kunci koreksinya!');
    }

    /**
     * Hapus studi kasus buatan guru.
     */
    public function destroyCaseStudy($id)
    {
        $case = CaseStudy::findOrFail($id);

        if ($case->is_default) {
            return redirect()->route('guru.dashboard', ['tab' => 'studi_kasus'])
                ->with('error', 'Studi kasus bawaan sistem tidak dapat dihapus.');
        }

        $title = $case->title;
        $case->delete();

        return redirect()->route('guru.dashboard', ['tab' => 'studi_kasus'])
            ->with('success', 'Studi kasus "' . $title . '" berhasil dihapus.');
    }

    /**
     * Cetak / Ekspor hasil penilaian siswa ke format Microsoft Excel (.xls).
     */
    public function exportExcel(Request $request)
    {
        $search = $request->query('search');
        $filterStatus = $request->query('status');
        $selectedClass = $request->query('class_name');

        $query = FlowchartSubmission::with(['user', 'caseStudy'])->orderBy('class_name')->orderBy('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('class_name', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($sub) use ($search) {
                      $sub->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($filterStatus === 'lulus') {
            $query->where('score', '>=', 75);
        } elseif ($filterStatus === 'remedial') {
            $query->where('score', '<', 75);
        }

        if ($selectedClass) {
            $query->where('class_name', $selectedClass);
        }

        $submissions = $query->get()->filter(function ($sub) {
            $name = strtolower(trim($sub->student_name ?? ''));
            return $name !== 'contoh1' && $name !== 'adit' && !str_contains($name, 'contoh1');
        })->values();

        // Mapping NISN Resmi Siswa X TO 3
        $studentNisnMap = [
            'RAFIANSYAH CIPTA PRANATA' => '0106572131',
            'RAFIQI DWI SAPUTRA' => '0109053073',
            'RAIHAN NUR RAHMAN' => '0102160972',
            'RANGGA SAPUTRA' => '0107837151',
            'RAVAEL DEWA FAHLEVI' => '0103998108',
            'REHAN DWI OKTAVIAN' => '0107376882',
            'RENO ADIAN SAPUTRA' => '0118540361',
            'RIDWAN KURNIAWAN' => '0103670451',
            'RIZKY EDY HIKMAWAN' => '0106392063',
            "SA'I FADLI" => '0106399619',
            'SATRIA BAGUS NURROHMAN' => '0111405486',
            'SATRIA GUTAMA' => '0107669333',
            'SATYA NURIL TAQIYAN' => '0107397653',
            'SHAH SULTAN ARIFIN' => '0101682196',
            'TEGAR ABADY' => '0101223593',
            'TEGAR GUSTI WARDANA' => '0101037491',
            'TEXAS PUTRA WIJAYA' => '0104999663',
            'WISNU BUDI SANTOSO' => '0114072927',
            'YARDAN ATSAQIF' => '0101294104',
            'YUSUF BINTANG PRATAMA' => '0104490586',
            'YUSUF THORIQ EL FAROBI' => '0103213869',
            'ZUAN ADITYA FIRMANSYAH' => '0116085711',
            'ADDIN NOOR MAULANA' => '0105535891',
            'ARYA BAGUS NUGROHO' => '0102018742',
            'ARYA FAHREZA' => '0108465352',
            'CAKRAWALA LINTANG SABAKHALILLAH' => '0101394467',
            'EGIT MAHARDIKA' => '0102727958',
            'ENGGAR WIBOWO MUKTI' => '0103349017',
            'ERLINO DWI SAPUTRA' => '0104382559',
            'FAHRI RAHMADANI' => '0107878508',
            'HAFIZH MAULANA AHMAD' => '0105540344',
            'ILHAM NUR YUWANA' => '0112054807',
            'IQBAL RAFA SAPUTRA' => '0108497094',
            'IRFANO DIKA PRATAMA' => '0107988726',
            'LANJAR REZKY BAKTI AJI SAPUTRA' => '0101764559',
            'NAUFAL LATIF CHAIRUL HAKAN' => '0108854779',
        ];

        // Assign calculated_nisn ke setiap submission
        foreach ($submissions as $sub) {
            $studentUpper = strtoupper(trim($sub->student_name ?? ''));
            if ($sub->user && preg_match('/^([0-9]+)@/', $sub->user->email, $m)) {
                $sub->calculated_nisn = $m[1];
            } elseif (isset($studentNisnMap[$studentUpper])) {
                $sub->calculated_nisn = $studentNisnMap[$studentUpper];
            } else {
                $sub->calculated_nisn = '-';
            }
        }

        $totalSubmissions = $submissions->count();
        $tuntasCount = $submissions->where('score', '>=', 75)->count();
        $remedialCount = $submissions->where('score', '<', 75)->count();
        $averageScore = $totalSubmissions > 0 ? round($submissions->avg('score'), 1) : 0;
        $percentageTuntas = $totalSubmissions > 0 ? round(($tuntasCount / $totalSubmissions) * 100, 1) : 0;

        $user = auth()->user();
        $teacherName = $user ? $user->name : 'Nur Aditya Romadhon S.Kom';
        $caseStudyTitle = 'Hitung Luas Segitiga';

        $safeClass = $selectedClass ? preg_replace('/[^A-Za-z0-9_-]/', '_', $selectedClass) : 'X_TO_3';
        $format = $request->query('format', 'xlsx');

        // Siapkan Baris Data untuk Ekspor
        $headers = [
            'NO',
            'NISN',
            'NAMA SISWA',
            'KELAS',
            'STUDI KASUS',
            'SKOR',
            'STATUS',
            'CATATAN EVALUASI & FEEDBACK',
            'WAKTU'
        ];

        $rows = [];
        foreach ($submissions as $idx => $sub) {
            $score = (int)($sub->score ?? 0);
            $status = $score >= 75 ? 'TUNTAS' : ($sub->is_remedial_open ? 'REMIDI (DIBUKA)' : 'REMEDIAL');

            $fb = '-';
            if (is_array($sub->feedback)) {
                $fb = implode('; ', $sub->feedback);
            } elseif ($sub->feedback) {
                $fb = (string)$sub->feedback;
            }
            $fb = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $fb);

            $timeObj = $sub->checked_at ?: $sub->created_at;
            $waktu = $timeObj ? $timeObj->format('d/m/Y H:i') : '08:15';

            $caseTitle = $sub->caseStudy ? $sub->caseStudy->title : ($sub->case_key ? ucwords(str_replace('_', ' ', $sub->case_key)) : 'Hitung Luas Segitiga');

            $rows[] = [
                $idx + 1,
                (string)$sub->calculated_nisn,
                strtoupper(trim($sub->student_name ?? '')),
                $sub->class_name ?: 'X TO 3',
                $caseTitle,
                $score,
                $status,
                trim($fb),
                $waktu,
            ];
        }

        // Format CSV (Fallback super-ringan & kompatibel 100%)
        if ($format === 'csv') {
            $csvFilename = 'Rekap_Nilai_Flowchart_' . $safeClass . '_' . date('Ymd_His') . '.csv';
            $csvLines = [];
            $csvLines[] = 'sep=;';
            $csvLines[] = implode(';', $headers);
            foreach ($rows as $r) {
                $escaped = array_map(function ($val) {
                    return '"' . str_replace('"', '""', (string)$val) . '"';
                }, $r);
                $csvLines[] = implode(';', $escaped);
            }
            $csvOutput = "\xEF\xBB\xBF" . implode("\r\n", $csvLines);

            return response($csvOutput, 200, [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $csvFilename . '"',
                'Content-Length'      => strlen($csvOutput),
                'Pragma'              => 'public',
                'Expires'             => '0',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            ]);
        }

        // Format Asli Microsoft Excel (.xlsx) OpenXML
        $xlsxFilename = 'Rekap_Nilai_Flowchart_' . $safeClass . '_' . date('Ymd_His') . '.xlsx';
        $meta = [
            'Guru Pengampu'       => $teacherName,
            'Rombel / Kelas'      => $selectedClass ?: 'Semua Kelas (X TO 3)',
            'Materi / Kasus'      => $caseStudyTitle,
            'Standar KKM'         => '75 / 100',
            'Total Siswa Dinilai' => $totalSubmissions . ' Siswa',
            'Ketuntasan'          => $tuntasCount . ' Tuntas | ' . $remedialCount . ' Remedial (' . $percentageTuntas . '%)',
            'Rata-rata Kelas'     => $averageScore . ' / 100',
            'Waktu Unduh'         => date('d F Y, H:i') . ' WIB',
        ];

        try {
            $xlsxBinary = XlsxGenerator::generate('LABORATORIUM FLOWCHART & PEMROGRAMAN DASAR', $meta, $headers, $rows);

            return response($xlsxBinary, 200, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $xlsxFilename . '"',
                'Content-Length'      => strlen($xlsxBinary),
                'Pragma'              => 'public',
                'Expires'             => '0',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            ]);
        } catch (\Throwable $e) {
            // Fallback jika terjadi kegagalan sistem kompresi zip
            $csvFilename = 'Rekap_Nilai_Flowchart_' . $safeClass . '_' . date('Ymd_His') . '.csv';
            $csvLines = [];
            $csvLines[] = 'sep=;';
            $csvLines[] = implode(';', $headers);
            foreach ($rows as $r) {
                $escaped = array_map(function ($val) {
                    return '"' . str_replace('"', '""', (string)$val) . '"';
                }, $r);
                $csvLines[] = implode(';', $escaped);
            }
            $csvOutput = "\xEF\xBB\xBF" . implode("\r\n", $csvLines);

            return response($csvOutput, 200, [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="' . $csvFilename . '"',
                'Content-Length'      => strlen($csvOutput),
                'Pragma'              => 'public',
                'Expires'             => '0',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            ]);
        }
    }

    /**
     * Buka / Tutup akses remedial bagi siswa yang belum tuntas.
     */
    public function toggleRemedial($id)
    {
        $submission = FlowchartSubmission::findOrFail($id);
        $submission->is_remedial_open = !$submission->is_remedial_open;
        $submission->save();

        $msg = $submission->is_remedial_open
            ? 'Akses remedial untuk "' . $submission->student_name . '" berhasil DIBUKA. Siswa dapat mengerjakan ulang di kanvas.'
            : 'Akses remedial untuk "' . $submission->student_name . '" berhasil DITUTUP.';

        if (request()->expectsJson() || request()->isJson()) {
            return response()->json([
                'success'          => true,
                'is_remedial_open' => $submission->is_remedial_open,
                'message'          => $msg,
            ]);
        }

        return redirect()->route('guru.dashboard')->with('success', $msg);
    }

    /**
     * Hapus submission tertentu oleh guru.
     */
    public function destroy($id)
    {
        $submission = FlowchartSubmission::findOrFail($id);
        $submission->delete();

        return redirect()->route('guru.dashboard')->with('success', 'Data hasil praktikum berhasil dihapus.');
    }
}
