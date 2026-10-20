<?php

namespace App\Http\Controllers;

use App\Models\FlowchartSubmission;
use App\Models\User;
use App\Models\CaseStudy;
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

        // Statistik Keseluruhan
        $allSubmissions = FlowchartSubmission::all();
        $totalSubmissions = $allSubmissions->count();
        $totalSiswa = User::where('role', 'siswa')->count();
        $siswaSubmit = $allSubmissions->pluck('student_name')->unique()->count();
        $averageScore = $totalSubmissions > 0 ? round($allSubmissions->avg('score'), 1) : 0;
        $lulusCount = $allSubmissions->where('score', '>=', 75)->count();
        $remedialCount = $allSubmissions->where('score', '<', 75)->count();

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
            'formula'             => 'nullable|string|max:255',
            'description'         => 'required|string',
            'expected_inputs'     => 'nullable|string|max:255',
            'expected_process'    => 'nullable|string|max:255',
            'expected_decision'   => 'nullable|string|max:255',
            'expected_yes_output' => 'nullable|string|max:100',
            'expected_no_output'  => 'nullable|string|max:100',
            'expected_outputs'    => 'nullable|string|max:255',
        ]);

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

        if (!empty($validated['expected_inputs'])) {
            $steps[] = ['type' => 'io', 'label' => 'Input ' . $validated['expected_inputs'], 'desc' => 'Membaca masukan: ' . $validated['expected_inputs']];
        } else {
            $steps[] = ['type' => 'io', 'label' => 'Input Data', 'desc' => 'Membaca data masukan'];
        }

        if ($validated['flow_type'] === 'branching') {
            $decText = !empty($validated['expected_decision']) ? $validated['expected_decision'] : 'Syarat Kondisi?';
            $steps[] = ['type' => 'decision', 'label' => $decText, 'desc' => 'Pemeriksaan kondisi logika'];
            $steps[] = ['type' => 'io', 'label' => ($validated['expected_yes_output'] ? 'Cetak ' . $validated['expected_yes_output'] : 'Output Ya'), 'desc' => 'Cabang YA (Kondisi Terpenuhi)'];
            $steps[] = ['type' => 'io', 'label' => ($validated['expected_no_output'] ? 'Cetak ' . $validated['expected_no_output'] : 'Output Tidak'), 'desc' => 'Cabang TIDAK (Kondisi Tidak Terpenuhi)'];
        } else {
            if (!empty($validated['expected_process'])) {
                $steps[] = ['type' => 'process', 'label' => $validated['expected_process'], 'desc' => 'Rumus perhitungan: ' . $validated['expected_process']];
            } else {
                $steps[] = ['type' => 'process', 'label' => 'Hitung / Proses Data', 'desc' => 'Komputasi manipulasi data'];
            }

            if (!empty($validated['expected_outputs'])) {
                $steps[] = ['type' => 'io', 'label' => 'Cetak ' . $validated['expected_outputs'], 'desc' => 'Menampilkan hasil nilai luaran'];
            } else {
                $steps[] = ['type' => 'io', 'label' => 'Cetak Hasil', 'desc' => 'Menampilkan hasil luaran'];
            }
        }

        $steps[] = ['type' => 'terminator', 'label' => 'Selesai', 'desc' => 'Titik akhir alur algoritma'];

        $connections = $validated['flow_type'] === 'branching'
            ? [
                'Mulai ➔ Input ➔ Keputusan (' . ($validated['expected_decision'] ?: 'Kondisi') . ')',
                'Keputusan [Ya] ➔ ' . ($validated['expected_yes_output'] ?: 'Output Lulus') . ' ➔ Selesai',
                'Keputusan [Tidak] ➔ ' . ($validated['expected_no_output'] ?: 'Output Remedial') . ' ➔ Selesai',
            ]
            : [
                'Mulai ➔ Input (' . ($validated['expected_inputs'] ?: 'Data') . ') ➔ Proses (' . ($validated['expected_process'] ?: 'Rumus') . ') ➔ Output (' . ($validated['expected_outputs'] ?: 'Hasil') . ') ➔ Selesai'
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
            'expected_inputs'     => $validated['expected_inputs'],
            'expected_process'    => $validated['expected_process'],
            'expected_decision'   => $validated['expected_decision'],
            'expected_yes_output' => $validated['expected_yes_output'],
            'expected_no_output'  => $validated['expected_no_output'],
            'expected_outputs'    => $validated['expected_outputs'],
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
     * Hapus submission tertentu oleh guru.
     */
    public function destroy($id)
    {
        $submission = FlowchartSubmission::findOrFail($id);
        $submission->delete();

        return redirect()->route('guru.dashboard')->with('success', 'Data hasil praktikum berhasil dihapus.');
    }
}
