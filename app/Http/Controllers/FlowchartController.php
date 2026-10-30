<?php

namespace App\Http\Controllers;

use App\Models\FlowchartSubmission;
use App\Models\CaseStudy;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class FlowchartController extends Controller
{
    /**
     * Tampilkan halaman editor flowchart untuk siswa.
     */
    public function index()
    {
        $user = Auth::user();

        // Jika siswa login, utamakan riwayat miliknya
        if ($user && $user->isSiswa()) {
            $submissions = FlowchartSubmission::where('user_id', $user->id)
                ->orWhere('student_name', $user->name)
                ->orderByDesc('created_at')
                ->get();
        } else {
            $submissions = FlowchartSubmission::orderByDesc('created_at')->get();
        }

        $caseStudies = CaseStudy::where('is_active', true)
            ->whereNotIn('case_key', ['bebas', '21', 'aaaaa'])
            ->where('title', 'not like', '%aaaaa%')
            ->orderBy('id')
            ->get();

        return view('flowchart', compact('submissions', 'user', 'caseStudies'));
    }

    /**
     * Dapatkan daftar studi kasus aktif untuk klien/frontend.
     */
    public function getCaseStudies()
    {
        return response()->json(
            CaseStudy::where('is_active', true)
                ->whereNotIn('case_key', ['bebas', '21', 'aaaaa'])
                ->where('title', 'not like', '%aaaaa%')
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * Simpan submission flowchart baru atau perbarui remedial di database.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'submission_id' => 'nullable|integer',
            'student_name'  => 'nullable|string|max:100',
            'class_name'    => 'nullable|string|max:50',
            'case_key'      => 'nullable|string|max:50',
            'diagram_data'  => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $caseKey = $validated['case_key'] ?? ($diagram['case_key'] ?? 'luas_segitiga');
        $assessment = $this->evaluateDiagram($diagram, $caseKey);
        $targetName = trim($validated['student_name'] ?? ($user ? $user->name : 'Siswa Praktikan'));
        $targetClass = trim($validated['class_name'] ?? ($user && $user->class_name ? $user->class_name : 'X TO 3'));

        $isDemo = ($user && ($user->class_name === 'Kelas Demo' || $user->email === 'demo@sekolah.id' || str_contains(strtolower($user->name), 'demo')))
            || strtolower($targetName) === 'siswa demo'
            || str_contains(strtolower($targetName), 'demo')
            || $targetClass === 'Kelas Demo';

        if ($isDemo) {
            $targetClass = 'Kelas Demo';
        }

        // Cek submission sebelumnya untuk penanganan remedial di tabel flowchart_submissions
        $existing = null;
        if (!empty($validated['submission_id'])) {
            $existing = FlowchartSubmission::find($validated['submission_id']);
        }
        if (!$existing) {
            $existing = FlowchartSubmission::where('case_key', $caseKey)
                ->where(function ($q) use ($user, $targetName) {
                    if (!empty($targetName)) {
                        $q->where('student_name', $targetName)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower($targetName)]);
                    }
                    if ($user) {
                        $q->orWhere('user_id', $user->id)
                          ->orWhere('student_name', $user->name)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower(trim($user->name))]);
                    }
                })
                ->latest()
                ->first();
        }

        // Akun Demo bebas mengerjakan sebanyak apapun tanpa batas (tetap masuk ke Kelas Demo)
        if ($isDemo) {
            if ($existing) {
                $existing->update([
                    'score'        => $assessment['score'],
                    'feedback'     => $assessment['feedback'],
                    'diagram_data' => $diagram,
                    'class_name'   => 'Kelas Demo',
                    'checked_at'   => now(),
                ]);
                $submission = $existing;
            } else {
                $submission = FlowchartSubmission::create([
                    'user_id'          => $user ? $user->id : null,
                    'student_name'     => $targetName,
                    'class_name'       => 'Kelas Demo',
                    'case_key'         => $caseKey,
                    'diagram_data'     => $diagram,
                    'score'            => $assessment['score'],
                    'feedback'         => $assessment['feedback'],
                    'is_remedial_open' => false,
                    'checked_at'       => now(),
                ]);
            }
        } elseif ($existing && ($existing->is_remedial_open || $existing->score < 75)) {
            // Jika siswa sedang remedial atau memiliki catatan belum tuntas, perbarui langsung di database
            $isTuntas = $assessment['score'] >= 75;
            $existing->update([
                'score'            => max($existing->score, $assessment['score']),
                'feedback'         => $assessment['feedback'],
                'diagram_data'     => $diagram,
                'is_remedial_open' => $isTuntas ? false : $existing->is_remedial_open,
                'checked_at'       => now(),
            ]);
            $submission = $existing;
        } else {
            $submission = FlowchartSubmission::create([
                'user_id'          => $user ? $user->id : null,
                'student_name'     => $targetName,
                'class_name'       => $targetClass,
                'case_key'         => $caseKey,
                'diagram_data'     => $diagram,
                'score'            => $assessment['score'],
                'feedback'         => $assessment['feedback'],
                'is_remedial_open' => false,
                'checked_at'       => now(),
            ]);
        }

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Flowchart berhasil diperbarui di database flowchart_submissions!',
                'submission' => $submission,
            ]);
        }

        return redirect()->route('flowchart.index')->with('success', 'Flowchart berhasil disimpan.');
    }

    /**
     * Periksa diagram dan berikan skor serta umpan balik otomatis.
     */
    public function check(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'submission_id' => 'nullable',
            'student_name'  => 'nullable|string|max:100',
            'class_name'    => 'nullable|string|max:50',
            'case_key'      => 'nullable|string|max:50',
            'diagram_data'  => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $caseKey = $validated['case_key'] ?? ($diagram['case_key'] ?? 'luas_segitiga');
        $assessment = $this->evaluateDiagram($diagram, $caseKey);

        $studentName = trim($validated['student_name'] ?? ($user ? $user->name : 'Siswa Praktikan'));
        $className = trim($validated['class_name'] ?? ($user && $user->class_name ? $user->class_name : 'X TO 3'));

        $isDemo = ($user && ($user->class_name === 'Kelas Demo' || $user->email === 'demo@sekolah.id' || str_contains(strtolower($user->name), 'demo')))
            || strtolower($studentName) === 'siswa demo'
            || str_contains(strtolower($studentName), 'demo')
            || $className === 'Kelas Demo';

        if ($isDemo) {
            $className = 'Kelas Demo';
        }

        // Cari riwayat pengerjaan sebelumnya untuk siswa ini pada tabel flowchart_submissions
        $existing = null;
        if (!empty($validated['submission_id']) && is_numeric($validated['submission_id'])) {
            $existing = FlowchartSubmission::find($validated['submission_id']);
        }

        if (!$existing) {
            $existing = FlowchartSubmission::where('case_key', $caseKey)
                ->where(function ($q) use ($user, $studentName) {
                    if (!empty($studentName)) {
                        $q->where('student_name', $studentName)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower($studentName)]);
                    }
                    if ($user) {
                        $q->orWhere('user_id', $user->id)
                          ->orWhere('student_name', $user->name)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower(trim($user->name))]);
                    }
                })
                ->latest()
                ->first();
        }

        // Jika tidak ditemukan dengan caseKey persis, cari apakah ada submission siswa yang remedialnya sedang dibuka
        if (!$existing) {
            $existing = FlowchartSubmission::where('is_remedial_open', true)
                ->where(function ($q) use ($user, $studentName) {
                    if (!empty($studentName)) {
                        $q->where('student_name', $studentName)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower($studentName)]);
                    }
                    if ($user) {
                        $q->orWhere('user_id', $user->id)
                          ->orWhere('student_name', $user->name)
                          ->orWhereRaw('LOWER(TRIM(student_name)) = ?', [strtolower(trim($user->name))]);
                    }
                })
                ->latest()
                ->first();
        }

        // AKUN DEMO: Bebas mengerjakan sebanyak apapun tanpa batas (tidak terkena lock 1x pengerjaan / lock remidi)
        // Data hasil evaluasi selalu tersimpan ke "Kelas Demo" di tabel flowchart_submissions
        if ($isDemo) {
            if ($existing) {
                $existing->update([
                    'score'        => $assessment['score'],
                    'feedback'     => $assessment['feedback'],
                    'diagram_data' => $diagram,
                    'class_name'   => 'Kelas Demo',
                    'checked_at'   => now(),
                ]);
                $submission = $existing;
            } else {
                $submission = FlowchartSubmission::create([
                    'user_id'          => $user ? $user->id : null,
                    'student_name'     => $studentName,
                    'class_name'       => 'Kelas Demo',
                    'case_key'         => $caseKey,
                    'diagram_data'     => $diagram,
                    'score'            => $assessment['score'],
                    'is_remedial_open' => false,
                    'feedback'         => $assessment['feedback'],
                    'checked_at'       => now(),
                ]);
            }

            return response()->json([
                'success'             => true,
                'is_demo'             => true,
                'is_remedial_updated' => false,
                'message'             => '🎉 Evaluasi selesai! Skor Anda: ' . $assessment['score'] . '/100. (Akun Demo: Bebas mencoba & mengerjakan ulang tanpa batas pengerjaan).',
                'submission_id'       => $submission->id,
                'score'               => $assessment['score'],
                'feedback'            => $assessment['feedback'],
            ]);
        }

        if ($existing) {
            // Aturan 1: Jika sudah tuntas (>= 75), studi kasus hanya bisa dikerjakan 1 kali
            if ($existing->score >= 75 && !$existing->is_remedial_open) {
                return response()->json([
                    'success'       => false,
                    'is_locked'     => true,
                    'message'       => 'Studi kasus ini sudah Anda kerjakan dan dinyatakan TUNTAS dengan skor ' . $existing->score . '/100. Setiap studi kasus hanya bisa dikerjakan 1 kali saja.',
                    'submission_id' => $existing->id,
                    'score'         => $existing->score,
                    'feedback'      => $existing->feedback,
                ], 403);
            }

            // Aturan 2: Jika belum tuntas (< 75), harus dicek apakah guru sudah membuka akses remedial
            if (!$existing->is_remedial_open && $existing->score < 75) {
                return response()->json([
                    'success'       => false,
                    'is_locked'     => true,
                    'need_remedial' => true,
                    'message'       => 'Studi kasus ini sudah dikerjakan dengan skor belum tuntas (' . $existing->score . '/100). Setiap studi kasus hanya bisa dikerjakan 1 kali. Silakan minta Guru Anda untuk membuka tombol "Buka Remidi" di Rekap Nilai agar dapat mengerjakan ulang.',
                    'submission_id' => $existing->id,
                    'score'         => $existing->score,
                    'feedback'      => $existing->feedback,
                ], 403);
            }

            // Aturan 3: Guru telah membuka remedial
            // Jika nilai baru tuntas (>= 75), nilai belum tuntas akan otomatis TERGANTI di database flowchart_submissions!
            if ($assessment['score'] >= 75) {
                $existing->update([
                    'score'            => $assessment['score'],
                    'feedback'         => $assessment['feedback'],
                    'diagram_data'     => $diagram,
                    'is_remedial_open' => false, // Tutup kembali remedial karena sudah tuntas
                    'checked_at'       => now(),
                ]);

                return response()->json([
                    'success'             => true,
                    'is_remedial_updated' => true,
                    'message'             => '🎉 Luar biasa! Remedial Anda berhasil dan nilai lama telah diganti menjadi TUNTAS (' . $assessment['score'] . '/100)! Database telah diperbarui.',
                    'submission_id'       => $existing->id,
                    'score'               => $assessment['score'],
                    'feedback'            => $assessment['feedback'],
                ]);
            } else {
                // Jika masih belum tuntas (< 75), perbarui nilai tertinggi & catatan umpan balik di database
                $existing->update([
                    'score'        => max($existing->score, $assessment['score']),
                    'feedback'     => $assessment['feedback'],
                    'diagram_data' => $diagram,
                    'checked_at'   => now(),
                ]);

                return response()->json([
                    'success'             => true,
                    'is_remedial_updated' => false,
                    'message'             => 'Nilai pengerjaan ulang Anda (' . $assessment['score'] . '/100) masih belum mencapai kriteria tuntas (≥ 75). Silakan perbaiki diagram alir Anda.',
                    'submission_id'       => $existing->id,
                    'score'               => $assessment['score'],
                    'feedback'            => $assessment['feedback'],
                ]);
            }
        }

        // Pengerjaan pertama kali (Simpan ke flowchart_submissions)
        $submission = FlowchartSubmission::create([
            'user_id'          => $user ? $user->id : null,
            'student_name'     => $studentName,
            'class_name'       => $className,
            'case_key'         => $caseKey,
            'diagram_data'     => $diagram,
            'score'            => $assessment['score'],
            'is_remedial_open' => false,
            'feedback'         => $assessment['feedback'],
            'checked_at'       => now(),
        ]);

        return response()->json([
            'success'       => true,
            'message'       => 'Pemeriksaan selesai.',
            'submission_id' => $submission->id,
            'score'         => $submission->score,
            'feedback'      => $submission->feedback,
        ]);
    }

    /**
     * Logika penilaian diagram flowchart otomatis berdasarkan jenis simbol dan kesesuaian teks logika.
     */
    private function evaluateDiagram(array $diagram, string $caseKey = 'luas_segitiga'): array
    {
        $nodes = $diagram['nodes'] ?? [];
        $edges = $diagram['edges'] ?? [];

        // Deteksi kasus secara cerdas jika kasus bebas tapi teksnya condong ke kasus tertentu
        if ($caseKey === 'bebas' || empty($caseKey)) {
            $allLabels = strtolower(implode(' ', array_map(fn($n) => $n['data']['label'] ?? '', $nodes)));
            if (str_contains($allLabels, 'alas') || str_contains($allLabels, 'tinggi') || str_contains($allLabels, 'segitiga')) {
                $caseKey = 'luas_segitiga';
            } elseif (str_contains($allLabels, '75') || str_contains($allLabels, 'lulus') || str_contains($allLabels, 'remedial')) {
                $caseKey = 'kelulusan';
            }
        }

        $terminators = [];
        $ioNodes = [];
        $processNodes = [];
        $decisionNodes = [];

        foreach ($nodes as $node) {
            $type = $node['type'] ?? '';
            $rawLabel = trim($node['data']['label'] ?? '');
            $cleanLabel = strtolower($rawLabel);
            $item = ['raw' => $rawLabel, 'clean' => $cleanLabel];

            if ($type === 'terminator') $terminators[] = $item;
            elseif ($type === 'io') $ioNodes[] = $item;
            elseif ($type === 'process') $processNodes[] = $item;
            elseif ($type === 'decision') $decisionNodes[] = $item;
        }

        $feedback = [];
        $score = 0;

        // ====================================================================
        // 1. EVALUASI TERMINATOR (Mulai & Selesai) - 20 Poin
        // ====================================================================
        $hasStart = false;
        $hasEnd = false;
        foreach ($terminators as $t) {
            if (str_contains($t['clean'], 'mulai') || str_contains($t['clean'], 'start') || $t['clean'] === 'mulai') {
                $hasStart = true;
            }
            if (str_contains($t['clean'], 'selesai') || str_contains($t['clean'], 'end') || str_contains($t['clean'], 'stop') || $t['clean'] === 'selesai') {
                $hasEnd = true;
            }
        }

        if ($hasStart) {
            $score += 10;
            $feedback[] = '✅ Simbol Mulai (Terminator) dengan teks "Mulai" sudah tepat.';
        } else {
            $feedback[] = '⚠️ Tambahkan simbol Terminator dengan teks "Mulai" sebagai penanda awal alur.';
        }

        if ($hasEnd) {
            $score += 10;
            $feedback[] = '✅ Simbol Selesai (Terminator) dengan teks "Selesai" sudah tepat.';
        } else {
            $feedback[] = '⚠️ Tambahkan simbol Terminator dengan teks "Selesai" sebagai penanda akhir program.';
        }

        // ====================================================================
        // 2. EVALUASI KESESUAIAN TEKS SESUAI STUDI KASUS (55 Poin)
        // ====================================================================
        if ($caseKey === 'luas_segitiga') {
            // STUDI KASUS 1: LUAS SEGITIGA
            // A. Teks Input Alas & Tinggi (15 Poin)
            $hasInputAlas = false;
            $hasInputTinggi = false;
            $hasDefaultIO = false;

            foreach ($ioNodes as $io) {
                $lbl = $io['clean'];
                if ($lbl === 'baca / cetak data' || $lbl === 'input data' || empty($lbl)) {
                    $hasDefaultIO = true;
                }
                if (str_contains($lbl, 'alas') || preg_match('/\b(alas|a)\b/i', $lbl)) {
                    $hasInputAlas = true;
                }
                if (str_contains($lbl, 'tinggi') || preg_match('/\b(tinggi|t)\b/i', $lbl)) {
                    $hasInputTinggi = true;
                }
            }

            if ($hasInputAlas && $hasInputTinggi) {
                $score += 15;
                $feedback[] = '✅ Teks Input tepat: Terdeteksi masukan data alas dan tinggi segitiga.';
            } elseif ($hasInputAlas || $hasInputTinggi) {
                $score += 8;
                $feedback[] = '⚠️ Teks Input belum lengkap: Pastikan memasukkan kedua nilai sekaligus (Alas DAN Tinggi).';
            } else {
                if ($hasDefaultIO) {
                    $feedback[] = '⚠️ Teks simbol Input/Output masih berupa contoh bawaan. Ganti dengan teks nyata: "Input Alas dan Tinggi".';
                } else {
                    $feedback[] = '⚠️ Tambahkan simbol Input/Output (Jajar Genjang) dengan teks masukan: "Input Alas dan Tinggi".';
                }
            }

            // B. Teks Output Luas (15 Poin)
            $hasOutputLuas = false;
            foreach ($ioNodes as $io) {
                $lbl = $io['clean'];
                if (str_contains($lbl, 'luas') && !str_contains($lbl, '=')) {
                    $hasOutputLuas = true;
                    break;
                } elseif (str_contains($lbl, 'cetak') || str_contains($lbl, 'tampil') || str_contains($lbl, 'output') || str_contains($lbl, 'print') || str_contains($lbl, 'hasil')) {
                    if (str_contains($lbl, 'luas') || count($ioNodes) > 1) {
                        $hasOutputLuas = true;
                        break;
                    }
                }
            }

            if ($hasOutputLuas) {
                $score += 15;
                $feedback[] = '✅ Teks Output tepat: Terdeteksi pencetakan/keluaran hasil nilai luas segitiga.';
            } else {
                $feedback[] = '⚠️ Tambahkan simbol Input/Output untuk mencetak hasil, contoh: "Tampilkan Luas" atau "Cetak Hasil Luas".';
            }

            // C. Teks Proses Rumus Luas (25 Poin)
            $hasValidFormula = false;
            $hasPartialFormula = false;

            foreach ($processNodes as $proc) {
                $lbl = $proc['clean'];
                $hasMult = (str_contains($lbl, 'alas') && str_contains($lbl, 'tinggi')) || (str_contains($lbl, 'a') && str_contains($lbl, 't') && str_contains($lbl, '*'));
                $hasHalf = str_contains($lbl, '0.5') || str_contains($lbl, '1/2') || str_contains($lbl, '/ 2') || str_contains($lbl, '/2');

                if (($hasMult && $hasHalf) || str_contains($lbl, '0.5 * alas * tinggi') || str_contains($lbl, '0.5*alas*tinggi') || str_contains($lbl, 'alas * tinggi / 2') || (str_contains($lbl, 'luas') && str_contains($lbl, '='))) {
                    $hasValidFormula = true;
                    break;
                } elseif (str_contains($lbl, 'luas') || str_contains($lbl, 'alas') || str_contains($lbl, 'tinggi')) {
                    $hasPartialFormula = true;
                }
            }

            if ($hasValidFormula) {
                $score += 25;
                $feedback[] = '✅ Teks Proses tepat: Rumus perhitungan luas (Luas = 0.5 × Alas × Tinggi) sudah sesuai.';
            } elseif ($hasPartialFormula) {
                $score += 12;
                $feedback[] = '⚠️ Teks Proses belum memuat rumus lengkap. Tuliskan rumus perhitungan: "Luas = 0.5 * alas * tinggi" atau "Luas = alas * tinggi / 2".';
            } elseif (count($processNodes) > 0) {
                $score += 5;
                $feedback[] = '⚠️ Teks simbol Proses masih berupa placeholder bawaan. Tuliskan rumus nyata: "Luas = 0.5 * alas * tinggi".';
            } else {
                $feedback[] = '⚠️ Tambahkan simbol Proses (Persegi Panjang) dengan rumus: "Luas = 0.5 * alas * tinggi".';
            }

        } elseif ($caseKey === 'kelulusan') {
            // STUDI KASUS 2: KELULUSAN
            // A. Teks Input Nilai Ujian (15 Poin)
            $hasInputNilai = false;
            foreach ($ioNodes as $io) {
                $lbl = $io['clean'];
                if (str_contains($lbl, 'nilai') || str_contains($lbl, 'ujian') || str_contains($lbl, 'skor') || str_contains($lbl, 'score') || str_contains($lbl, 'angka')) {
                    $hasInputNilai = true;
                    break;
                }
            }

            if ($hasInputNilai) {
                $score += 15;
                $feedback[] = '✅ Teks Input tepat: Terdeteksi pembacaan masukan nilai ujian siswa.';
            } elseif (count($ioNodes) > 0) {
                $score += 8;
                $feedback[] = '⚠️ Teks Input belum spesifik: Tuliskan masukan untuk nilai ujian (contoh: "Input Nilai Ujian").';
            } else {
                $feedback[] = '⚠️ Tambahkan simbol Input/Output (Jajar Genjang) untuk membaca nilai: "Input Nilai Ujian".';
            }

            // B. Teks Keputusan / Percabangan Syarat Nilai (25 Poin)
            // 15 poin untuk teks kondisi (menguji >= 75 / > 74 / < 75 / kkm)
            // 10 poin untuk struktur percabangan (memiliki minimal 2 cabang keluar: Ya dan Tidak)
            $hasValidDecisionText = false;
            $hasPartialDecisionText = false;

            foreach ($decisionNodes as $dec) {
                $lbl = $dec['clean'];
                if (str_contains($lbl, '75') || str_contains($lbl, '74') || str_contains($lbl, '>=') || str_contains($lbl, 'kkm') || (str_contains($lbl, 'nilai') && (str_contains($lbl, '>') || str_contains($lbl, '<')))) {
                    $hasValidDecisionText = true;
                    break;
                } elseif (str_contains($lbl, '?') || str_contains($lbl, 'lulus') || str_contains($lbl, 'syarat')) {
                    $hasPartialDecisionText = true;
                }
            }

            if ($hasValidDecisionText) {
                $score += 15;
                $feedback[] = '✅ Teks Keputusan tepat: Terdeteksi pengujian syarat kriteria kelulusan (Apakah Nilai >= 75?).';
            } elseif ($hasPartialDecisionText) {
                $score += 8;
                $feedback[] = '⚠️ Teks Keputusan belum menyebutkan batas KKM nilai 75. Tuliskan syarat lengkap (contoh: "Apakah Nilai >= 75?").';
            } elseif (count($decisionNodes) > 0) {
                $score += 4;
                $feedback[] = '⚠️ Teks Keputusan masih berupa placeholder bawaan. Ubah dengan kondisi syarat: "Apakah Nilai >= 75?".';
            } else {
                $feedback[] = '⚠️ Tambahkan simbol Keputusan (Belah Ketupat) untuk memeriksa kondisi kelulusan: "Nilai >= 75?".';
            }

            // Cek apakah simbol keputusan memiliki minimal 2 garis keluar (cabang Ya & Tidak)
            $decisionHasTwoBranches = false;
            $hasBranchLabels = false;

            foreach ($nodes as $n) {
                if (($n['type'] ?? '') === 'decision') {
                    $decId = $n['id'] ?? '';
                    $outgoingEdges = array_values(array_filter($edges, fn($e) => ($e['source'] ?? '') === $decId));
                    if (count($outgoingEdges) >= 2) {
                        $decisionHasTwoBranches = true;

                        $labels = array_map(fn($e) => strtolower(trim($e['label'] ?? '')), $outgoingEdges);
                        $handles = array_map(fn($e) => strtolower(trim($e['sourceHandle'] ?? '')), $outgoingEdges);

                        $hasYes = in_array('ya', $labels, true) || in_array('yes', $labels, true) || in_array('left', $handles, true);
                        $hasNo  = in_array('tidak', $labels, true) || in_array('no', $labels, true) || in_array('right', $handles, true);

                        if ($hasYes && $hasNo) {
                            $hasBranchLabels = true;
                        }
                        break;
                    }
                }
            }

            if ($decisionHasTwoBranches) {
                $score += 10;
                if ($hasBranchLabels) {
                    $feedback[] = '✅ Struktur percabangan sangat baik: Simbol Keputusan memiliki 2 cabang alur keluar lengkap dengan cabang "Ya" dan "Tidak".';
                } else {
                    $feedback[] = '✅ Struktur percabangan tepat: Simbol Keputusan memiliki 2 cabang alur keluar (Ya dan Tidak).';
                }
            } elseif (count($decisionNodes) > 0) {
                $feedback[] = '⚠️ Simbol Keputusan harus memiliki 2 cabang panah keluar: satu untuk kondisi "Ya" (Lulus) dan satu untuk kondisi "Tidak" (Remedial).';
            }

            // C. Teks Output Lulus / Remedial (15 Poin)
            $candidateNodes = array_merge($ioNodes, $processNodes);
            $hasOutputLulus = false;
            $hasOutputRemedial = false;

            foreach ($candidateNodes as $node) {
                $lbl = $node['clean'];
                if (str_contains($lbl, 'lulus') && !str_contains($lbl, 'tidak lulus') && !str_contains($lbl, 'belum lulus')) {
                    $hasOutputLulus = true;
                }
                if (str_contains($lbl, 'remedial') || str_contains($lbl, 'tidak lulus') || str_contains($lbl, 'belum lulus') || str_contains($lbl, 'gagal')) {
                    $hasOutputRemedial = true;
                }
            }

            if ($hasOutputLulus && $hasOutputRemedial) {
                $score += 15;
                $feedback[] = '✅ Teks Output tepat: Terdeteksi penanganan kedua kemungkinan hasil (LULUS dan REMEDIAL).';
            } elseif ($hasOutputLulus || $hasOutputRemedial) {
                $score += 8;
                $feedback[] = '⚠️ Teks Output baru memuat salah satu cabang. Pastikan menangani kedua kemungkinan: "LULUS" dan "REMEDIAL".';
            } else {
                $feedback[] = '⚠️ Tambahkan simbol Output untuk menampilkan hasil seleksi: "Cetak LULUS" dan "Cetak REMEDIAL".';
            }

        } elseif ($caseKey !== 'bebas' && ($customCase = CaseStudy::where('case_key', $caseKey)->first())) {
            // ================================================================
            // STUDI KASUS DINAMIS YANG DITAMBAHKAN OLEH GURU
            // ================================================================
            $isBranching = $customCase->flow_type === 'branching';

            // A. Evaluasi Input (15 Poin)
            $inputKeywords = array_filter(array_map('trim', explode(',', strtolower($customCase->expected_inputs ?? ''))));
            $allIoCleanText = implode(' ', array_column($ioNodes, 'clean'));
            $matchedInputKeywords = [];
            foreach ($inputKeywords as $kw) {
                if (!empty($kw) && str_contains($allIoCleanText, $kw)) {
                    $matchedInputKeywords[] = $kw;
                }
            }

            $hasDefaultIO = false;
            foreach ($ioNodes as $io) {
                $lbl = $io['clean'];
                if ($lbl === 'baca / cetak data' || $lbl === 'input data' || empty($lbl)) {
                    $hasDefaultIO = true;
                }
            }

            if (!empty($inputKeywords)) {
                if (count($matchedInputKeywords) >= count($inputKeywords)) {
                    $score += 15;
                    $feedback[] = '✅ Teks Input tepat: Terdeteksi masukan data lengkap (' . implode(', ', $matchedInputKeywords) . ').';
                } elseif (count($matchedInputKeywords) > 0) {
                    $score += 15;
                    $feedback[] = '✅ Teks Input tepat: Terdeteksi masukan data (' . implode(', ', $matchedInputKeywords) . ').';
                } else {
                    if ($hasDefaultIO) {
                        $feedback[] = '⚠️ Teks simbol Input masih berupa teks contoh. Masukkan nama data: ' . ($customCase->expected_inputs ?: 'Data Masukan') . '.';
                    } else {
                        $feedback[] = '⚠️ Tambahkan simbol Input/Output (Jajar Genjang) dengan masukan: ' . ($customCase->expected_inputs ?: 'Data Masukan') . '.';
                    }
                }
            } else {
                if (count($ioNodes) > 0 && !$hasDefaultIO) {
                    $score += 15;
                    $feedback[] = '✅ Teks Input disertakan dengan instruksi data yang jelas.';
                } elseif (count($ioNodes) > 0) {
                    $score += 8;
                    $feedback[] = '⚠️ Ganti teks bawaan simbol Input dengan variabel data yang diproses.';
                } else {
                    $feedback[] = '⚠️ Tambahkan simbol Input/Output (Jajar Genjang) untuk membaca data.';
                }
            }

            if ($isBranching) {
                // B. Evaluasi Percabangan (25 Poin: 15 teks kondisi + 10 struktur 2 cabang)
                $decisionKeywords = array_filter(array_map('trim', explode(',', strtolower($customCase->expected_decision ?? ''))));
                $hasValidDecisionText = false;

                foreach ($decisionNodes as $dec) {
                    $lbl = $dec['clean'];
                    if (!empty($decisionKeywords)) {
                        foreach ($decisionKeywords as $dkw) {
                            if (!empty($dkw) && str_contains($lbl, $dkw)) {
                                $hasValidDecisionText = true;
                                break 2;
                            }
                        }
                    } elseif (!empty($lbl) && $lbl !== 'kondisi ?' && $lbl !== 'kondisi?') {
                        $hasValidDecisionText = true;
                        break;
                    }
                }

                if ($hasValidDecisionText) {
                    $score += 15;
                    $feedback[] = '✅ Teks Keputusan tepat: Terdeteksi pengujian syarat kondisi (' . ($customCase->expected_decision ?: 'Kondisi') . ').';
                } elseif (count($decisionNodes) > 0) {
                    $score += 6;
                    $feedback[] = '⚠️ Teks Keputusan belum spesifik. Tuliskan syarat kondisi: "' . ($customCase->expected_decision ?: 'Kondisi?') . '".';
                } else {
                    $feedback[] = '⚠️ Tambahkan simbol Keputusan (Belah Ketupat) untuk menguji kondisi percabangan.';
                }

                // Cek 2 cabang panah keluar (Ya dan Tidak)
                $decisionHasTwoBranches = false;
                $hasBranchLabels = false;

                foreach ($nodes as $n) {
                    if (($n['type'] ?? '') === 'decision') {
                        $decId = $n['id'] ?? '';
                        $outgoingEdges = array_values(array_filter($edges, fn($e) => ($e['source'] ?? '') === $decId));
                        if (count($outgoingEdges) >= 2) {
                            $decisionHasTwoBranches = true;
                            $labels = array_map(fn($e) => strtolower(trim($e['label'] ?? '')), $outgoingEdges);
                            $handles = array_map(fn($e) => strtolower(trim($e['sourceHandle'] ?? '')), $outgoingEdges);
                            $hasYes = in_array('ya', $labels, true) || in_array('yes', $labels, true) || in_array('left', $handles, true);
                            $hasNo  = in_array('tidak', $labels, true) || in_array('no', $labels, true) || in_array('right', $handles, true);
                            if ($hasYes && $hasNo) {
                                $hasBranchLabels = true;
                            }
                            break;
                        }
                    }
                }

                if ($decisionHasTwoBranches) {
                    $score += 10;
                    if ($hasBranchLabels) {
                        $feedback[] = '✅ Struktur percabangan sangat baik: Simbol Keputusan memiliki 2 cabang alur keluar lengkap dengan cabang "Ya" dan "Tidak".';
                    } else {
                        $feedback[] = '✅ Struktur percabangan tepat: Simbol Keputusan memiliki 2 cabang alur keluar (Ya dan Tidak).';
                    }
                } elseif (count($decisionNodes) > 0) {
                    $feedback[] = '⚠️ Simbol Keputusan harus memiliki 2 cabang panah keluar: satu untuk kondisi "Ya" dan satu untuk kondisi "Tidak".';
                }

                // C. Evaluasi Output Cabang Ya & Tidak (15 Poin)
                $candidateNodes = array_merge($ioNodes, $processNodes);
                $expectedYes = strtolower(trim($customCase->expected_yes_output ?? ''));
                $expectedNo = strtolower(trim($customCase->expected_no_output ?? ''));
                $hasYesOut = false;
                $hasNoOut = false;

                foreach ($candidateNodes as $cn) {
                    $lbl = $cn['clean'];
                    if (!empty($expectedYes) && str_contains($lbl, $expectedYes)) {
                        $hasYesOut = true;
                    }
                    if (!empty($expectedNo) && str_contains($lbl, $expectedNo)) {
                        $hasNoOut = true;
                    }
                }

                if (!empty($expectedYes) && !empty($expectedNo)) {
                    if ($hasYesOut && $hasNoOut) {
                        $score += 15;
                        $feedback[] = "✅ Teks Output tepat: Terdeteksi penanganan kedua kemungkinan hasil ('$expectedYes' dan '$expectedNo').";
                    } elseif ($hasYesOut || $hasNoOut) {
                        $score += 8;
                        $feedback[] = "⚠️ Teks Output baru menangani salah satu cabang. Pastikan menampilkan kedua status: '$expectedYes' dan '$expectedNo'.";
                    } else {
                        $feedback[] = "⚠️ Tambahkan simbol Output untuk menampilkan hasil: '$expectedYes' dan '$expectedNo'.";
                    }
                } elseif (count($ioNodes) > 1) {
                    $score += 15;
                    $feedback[] = '✅ Teks Output terdeteksi untuk kedua jalur cabang alur.';
                } else {
                    $score += 7;
                    $feedback[] = '💡 Tips: Sediakan simbol Output untuk masing-masing cabang keputusan.';
                }

            } else {
                // Alur Sekuensial: B. Evaluasi Proses / Rumus (25 Poin)
                $processKeywords = array_filter(array_map('trim', explode(',', strtolower($customCase->expected_process ?? ''))));
                $hasValidProcess = false;

                foreach ($processNodes as $proc) {
                    $lbl = $proc['clean'];
                    if (!empty($processKeywords)) {
                        foreach ($processKeywords as $pkw) {
                            if (!empty($pkw) && str_contains($lbl, $pkw)) {
                                $hasValidProcess = true;
                                break 2;
                            }
                        }
                    } elseif (!empty($lbl) && $lbl !== 'langkah perhitungan...' && $lbl !== 'hitung data') {
                        $hasValidProcess = true;
                        break;
                    }
                }

                if ($hasValidProcess) {
                    $score += 25;
                    $feedback[] = '✅ Teks Proses tepat: Terdeteksi rumus/langkah perhitungan yang sesuai (' . ($customCase->expected_process ?: 'Perhitungan') . ').';
                } elseif (count($processNodes) > 0) {
                    $score += 10;
                    $feedback[] = '⚠️ Teks Proses belum sesuai rumus. Tuliskan rumus: "' . ($customCase->expected_process ?: 'Rumus Perhitungan') . '".';
                } else {
                    $feedback[] = '⚠️ Tambahkan simbol Proses (Persegi Panjang) dengan langkah: "' . ($customCase->expected_process ?: 'Rumus Perhitungan') . '".';
                }

                // C. Evaluasi Output (15 Poin)
                $outputKeywords = array_filter(array_map('trim', explode(',', strtolower($customCase->expected_outputs ?? ''))));
                $hasValidOutput = false;

                foreach ($ioNodes as $io) {
                    $lbl = $io['clean'];
                    if (!empty($outputKeywords)) {
                        foreach ($outputKeywords as $okw) {
                            if (!empty($okw) && str_contains($lbl, $okw)) {
                                $hasValidOutput = true;
                                break 2;
                            }
                        }
                    } elseif (str_contains($lbl, 'cetak') || str_contains($lbl, 'tampil') || str_contains($lbl, 'output') || count($ioNodes) > 1) {
                        $hasValidOutput = true;
                        break;
                    }
                }

                if ($hasValidOutput) {
                    $score += 15;
                    $feedback[] = '✅ Teks Output tepat: Terdeteksi pencetakan/keluaran hasil ke layar (' . ($customCase->expected_outputs ?: 'Keluaran') . ').';
                } else {
                    $feedback[] = '⚠️ Tambahkan simbol Input/Output untuk mencetak hasil: "' . ($customCase->expected_outputs ?: 'Cetak Hasil') . '".';
                }
            }

        } else {
            // STUDI KASUS MANDIRI / BEBAS
            $meaningfulIO = 0;
            foreach ($ioNodes as $io) {
                $lbl = $io['clean'];
                if (!empty($lbl) && $lbl !== 'baca / cetak data' && $lbl !== 'input data') {
                    $meaningfulIO++;
                }
            }

            if ($meaningfulIO > 0) {
                $score += 25;
                $feedback[] = "✅ Teks Input/Output bermakna: Memuat instruksi data masukan/keluaran yang jelas ($meaningfulIO simbol).";
            } elseif (count($ioNodes) > 0) {
                $score += 10;
                $feedback[] = '⚠️ Teks pada simbol Input/Output masih berupa placeholder. Tuliskan nama variabel atau data yang diproses.';
            } else {
                $feedback[] = '💡 Tips: Tambahkan simbol Input/Output untuk membaca masukan atau mencetak hasil.';
            }

            $meaningfulProcess = 0;
            foreach ($processNodes as $proc) {
                $lbl = $proc['clean'];
                if (!empty($lbl) && $lbl !== 'langkah perhitungan...' && $lbl !== 'hitung data') {
                    $meaningfulProcess++;
                }
            }

            if ($meaningfulProcess > 0) {
                $score += 20;
                $feedback[] = "✅ Teks Proses bermakna: Memuat langkah komputasi data yang spesifik ($meaningfulProcess langkah).";
            } elseif (count($processNodes) > 0) {
                $score += 10;
                $feedback[] = '⚠️ Teks pada simbol Proses masih berupa teks contoh. Tuliskan rumus atau instruksi nyata.';
            }

            if (count($decisionNodes) > 0) {
                $hasValidDecision = false;
                foreach ($decisionNodes as $dec) {
                    $lbl = $dec['clean'];
                    if (!empty($lbl) && $lbl !== 'kondisi ?' && $lbl !== 'kondisi?') {
                        $hasValidDecision = true;
                        break;
                    }
                }
                if ($hasValidDecision) {
                    $score += 10;
                    $feedback[] = '✅ Teks Keputusan bermakna: Memuat kondisi percabangan yang spesifik.';
                } else {
                    $feedback[] = '⚠️ Teks pada simbol Keputusan masih berupa contoh "Kondisi?". Ubah dengan pertanyaan syarat.';
                }
            }
        }

        // ====================================================================
        // 3. EVALUASI KETEPATAN GARIS ALIR & LOGIKA PANAH FLOWCHART (25 Poin)
        // ====================================================================
        $customCaseObj = ($caseKey !== 'luas_segitiga' && $caseKey !== 'kelulusan' && $caseKey !== 'bebas')
            ? (isset($customCase) && $customCase ? $customCase : CaseStudy::where('case_key', $caseKey)->first())
            : null;

        $arrowAssessment = $this->evaluateFlowlines($nodes, $edges, $caseKey, $customCaseObj);
        $score += $arrowAssessment['score'];
        foreach ($arrowAssessment['feedback'] as $fb) {
            $feedback[] = $fb;
        }

        // Jika terdapat kesalahan fatal pada arah panah / logika percabangan, batasi nilai maksimal (belum tuntas < 75)
        if (!empty($arrowAssessment['hasCriticalFlowError'])) {
            $score = min($score, 65);
            $feedback[] = '⛔ Alur logika algoritma belum benar: Perbaiki arah panah atau percabangan yang keliru agar alur algoritma valid.';
        }

        return [
            'score'    => min(100, max(0, $score)),
            'feedback' => $feedback,
        ];
    }

    /**
     * Evaluasi ketepatan arah, urutan logika algoritma, dan konektivitas garis panah alir (edges).
     */
    private function evaluateFlowlines(array $nodes, array $edges, string $caseKey, ?CaseStudy $customCase = null): array
    {
        $feedback = [];
        $score = 0;

        if (empty($nodes)) {
            return ['score' => 0, 'feedback' => ['⚠️ Belum ada simbol flowchart yang disusun.']];
        }

        if (empty($edges)) {
            return [
                'score'    => 0,
                'feedback' => ['⚠️ Hubungkan simbol-simbol di atas menggunakan garis panah alir sesuai urutan algoritma.'],
            ];
        }

        $nodeIds = array_column($nodes, 'id');
        $nodeMap = [];
        foreach ($nodes as $n) {
            $nodeMap[$n['id']] = $n;
        }

        // 1. Validitas & Integritas Garis Panah (Maks 6 Poin)
        $validEdges = [];
        $invalidEdgeCount = 0;
        foreach ($edges as $edge) {
            $src = $edge['source'] ?? null;
            $tgt = $edge['target'] ?? null;
            if ($src && $tgt && isset($nodeMap[$src]) && isset($nodeMap[$tgt]) && $src !== $tgt) {
                $validEdges[] = $edge;
            } else {
                $invalidEdgeCount++;
            }
        }

        if ($invalidEdgeCount === 0 && count($validEdges) > 0) {
            $score += 6;
        } elseif (count($validEdges) > 0) {
            $score += 3;
            $feedback[] = '⚠️ Terdapat garis panah yang terlepas atau belum terhubung ke simbol yang sah.';
        } else {
            return [
                'score'    => 0,
                'feedback' => ['⚠️ Garis panah penghubung belum terpasang dengan benar pada simbol-simbol flowchart.'],
            ];
        }

        // Peta ketetanggaan (Adjacency maps)
        $outgoing = [];
        $incoming = [];
        foreach ($nodeIds as $id) {
            $outgoing[$id] = [];
            $incoming[$id] = [];
        }

        foreach ($validEdges as $e) {
            $src = $e['source'];
            $tgt = $e['target'];
            $edgeData = [
                'target'       => $tgt,
                'source'       => $src,
                'label'        => strtolower(trim($e['label'] ?? '')),
                'sourceHandle' => strtolower(trim($e['sourceHandle'] ?? '')),
            ];
            $outgoing[$src][] = $edgeData;
            $incoming[$tgt][] = $edgeData;
        }

        $cleanLabels = [];
        foreach ($nodes as $n) {
            $id = $n['id'];
            $cleanLabels[$id] = strtolower(trim($n['data']['label'] ?? ''));
        }

        // Klasifikasi peran simbol dalam diagram
        $startNode = null;
        $endNode = null;
        $inputNodes = [];
        $processNodes = [];
        $decisionNodes = [];
        $outputNodes = [];

        foreach ($nodes as $n) {
            $id = $n['id'];
            $type = $n['type'] ?? '';
            $lbl = $cleanLabels[$id];

            if ($type === 'terminator') {
                if (str_contains($lbl, 'mulai') || str_contains($lbl, 'start') || str_contains($lbl, 'begin')) {
                    $startNode = $n;
                } elseif (str_contains($lbl, 'selesai') || str_contains($lbl, 'end') || str_contains($lbl, 'stop') || str_contains($lbl, 'tamat')) {
                    $endNode = $n;
                }
            } elseif ($type === 'decision') {
                $decisionNodes[] = $n;
            } elseif ($type === 'io') {
                $isOut = str_contains($lbl, 'cetak') || str_contains($lbl, 'tampil') || str_contains($lbl, 'output') || str_contains($lbl, 'print') || str_contains($lbl, 'hasil') || str_contains($lbl, 'lulus') || str_contains($lbl, 'remedial');
                if ($isOut) {
                    $outputNodes[] = $n;
                } else {
                    $inputNodes[] = $n;
                }
            } elseif ($type === 'process') {
                $isOut = str_contains($lbl, 'lulus') || str_contains($lbl, 'remedial');
                if ($isOut) {
                    $outputNodes[] = $n;
                } else {
                    $processNodes[] = $n;
                }
            }
        }

        // Helper fungsi reachability BFS & direct edge (mencegah loop tak terhingga)
        $hasDirectEdge = function ($srcId, $tgtId) use ($outgoing) {
            if (!$srcId || !$tgtId) return false;
            foreach ($outgoing[$srcId] ?? [] as $out) {
                if ($out['target'] === $tgtId) return true;
            }
            return false;
        };

        $hasPath = function ($startId, $targetId) use ($outgoing) {
            if (!$startId || !$targetId || $startId === $targetId) return false;
            $queue = [$startId];
            $visited = [$startId => true];
            while (!empty($queue)) {
                $curr = array_shift($queue);
                if ($curr === $targetId) return true;
                foreach ($outgoing[$curr] ?? [] as $out) {
                    $nxt = $out['target'];
                    if (!isset($visited[$nxt])) {
                        $visited[$nxt] = true;
                        $queue[] = $nxt;
                    }
                }
            }
            return false;
        };

        // 2. Evaluasi Arah Panah Terminator Mulai & Selesai (Maks 5 Poin)
        $terminatorScore = 5;
        $terminatorErrors = 0;

        if ($startNode) {
            $sId = $startNode['id'];
            if (empty($outgoing[$sId])) {
                $feedback[] = '⚠️ Simbol "Mulai" belum memiliki panah keluar untuk mengalirkan langkah awal.';
                $terminatorErrors++;
            }
            if (!empty($incoming[$sId])) {
                $feedback[] = '⚠️ Arah panah keliru: Simbol "Mulai" adalah titik awal program dan tidak boleh menerima panah masuk.';
                $terminatorErrors++;
            }
        }

        if ($endNode) {
            $eId = $endNode['id'];
            if (empty($incoming[$eId])) {
                $feedback[] = '⚠️ Simbol "Selesai" belum menerima panah masuk dari langkah akhir diagram.';
                $terminatorErrors++;
            }
            if (!empty($outgoing[$eId])) {
                $feedback[] = '⚠️ Arah panah keliru: Simbol "Selesai" adalah akhir alur dan tidak boleh memiliki panah keluar.';
                $terminatorErrors++;
            }
        }

        if ($terminatorErrors === 0 && $startNode && $endNode) {
            $score += $terminatorScore;
            $feedback[] = '✅ Arah panah Terminator tepat: Panah mengalir keluar dari "Mulai" dan bermuara ke "Selesai".';
        } elseif ($terminatorErrors === 1) {
            $score += 3;
        }

        // 3. Evaluasi Ketepatan Urutan & Arah Panah Algoritma (Maks 10 Poin)
        $isBranching = ($caseKey === 'kelulusan') || ($customCase && $customCase->flow_type === 'branching');
        $hasCriticalFlowError = false;

        if (!$isBranching) {
            // === ALUR SEKUENSIAL (LUAS SEGITIGA & SEKUENSIAL LAINNYA) ===
            // Standar urutan: Mulai ➔ Input ➔ Proses Rumus ➔ Output ➔ Selesai
            $seqScore = 0;
            $seqErrors = [];

            // A. Mulai ➔ Input
            $hasReversedStartInput = false;
            if ($startNode && !empty($inputNodes)) {
                foreach ($inputNodes as $inp) {
                    if ($hasPath($inp['id'], $startNode['id'])) {
                        $hasReversedStartInput = true;
                        $hasCriticalFlowError = true;
                        $seqErrors[] = '⚠️ Alur panah terbalik: Panah harus mengalir dari "Mulai" ke "Input", bukan sebaliknya.';
                        break;
                    }
                }
            }

            $startDirectlyToInput = false;
            if ($startNode && !empty($inputNodes)) {
                foreach ($inputNodes as $inp) {
                    if ($hasDirectEdge($startNode['id'], $inp['id'])) {
                        $startDirectlyToInput = true;
                        break;
                    }
                }
            }

            if ($startDirectlyToInput && !$hasReversedStartInput) {
                $seqScore += 2.5;
            } elseif ($startNode && !empty($inputNodes) && !$hasReversedStartInput) {
                $startTargets = array_column($outgoing[$startNode['id']] ?? [], 'target');
                if (!empty($startTargets)) {
                    $firstTargetId = $startTargets[0];
                    $firstTargetLbl = $cleanLabels[$firstTargetId] ?? '';
                    if (str_contains($firstTargetLbl, 'luas') || str_contains($firstTargetLbl, '=')) {
                        $seqErrors[] = '⚠️ Urutan alur keliru: Simbol "Mulai" terhubung langsung ke Proses rumus. Masukkan data terlebih dahulu (Mulai ➔ Input).';
                        $hasCriticalFlowError = true;
                    } else {
                        $seqErrors[] = '⚠️ Simbol "Mulai" seharusnya terhubung langsung ke simbol Input terlebih dahulu.';
                    }
                } else {
                    $seqErrors[] = '⚠️ Simbol "Mulai" seharusnya terhubung ke simbol Input.';
                }
            }

            // B. Input ➔ Proses Rumus
            $hasReversedInputProcess = false;
            if (!empty($inputNodes) && !empty($processNodes)) {
                foreach ($processNodes as $prc) {
                    foreach ($inputNodes as $inp) {
                        if ($hasPath($prc['id'], $inp['id'])) {
                            $hasReversedInputProcess = true;
                            $hasCriticalFlowError = true;
                            $seqErrors[] = '⚠️ Alur panah terbalik: Data masukan (Input) harus mengalir menuju langkah perhitungan (Proses Rumus), bukan sebaliknya.';
                            break 2;
                        }
                    }
                }
            }

            $inputLeadsToProcess = false;
            if (!empty($inputNodes) && !empty($processNodes)) {
                foreach ($inputNodes as $inp) {
                    foreach ($processNodes as $prc) {
                        if ($hasDirectEdge($inp['id'], $prc['id']) || $hasPath($inp['id'], $prc['id'])) {
                            $inputLeadsToProcess = true;
                            break 2;
                        }
                    }
                }
            }

            if ($inputLeadsToProcess && !$hasReversedInputProcess) {
                $seqScore += 2.5;
            } elseif (!empty($inputNodes) && !empty($processNodes) && !$hasReversedInputProcess) {
                $seqErrors[] = '⚠️ Hubungkan simbol Input ke simbol Proses Rumus agar nilai data dapat dihitung.';
            }

            // C. Proses Rumus ➔ Output
            $hasReversedProcessOutput = false;
            if (!empty($processNodes) && !empty($outputNodes)) {
                foreach ($outputNodes as $out) {
                    foreach ($processNodes as $prc) {
                        if ($hasPath($out['id'], $prc['id'])) {
                            $hasReversedProcessOutput = true;
                            $hasCriticalFlowError = true;
                            $seqErrors[] = '⚠️ Alur panah terbalik: Hasil baru dapat dicetak setelah rumus perhitungan selesai diproses (Proses Rumus ➔ Output Cetak).';
                            break 2;
                        }
                    }
                }
            }

            $processLeadsToOutput = false;
            if (!empty($processNodes) && !empty($outputNodes)) {
                foreach ($processNodes as $prc) {
                    foreach ($outputNodes as $out) {
                        if ($hasDirectEdge($prc['id'], $out['id']) || $hasPath($prc['id'], $out['id'])) {
                            $processLeadsToOutput = true;
                            break 2;
                        }
                    }
                }
            } elseif (empty($processNodes) && !empty($inputNodes) && !empty($outputNodes)) {
                foreach ($inputNodes as $inp) {
                    foreach ($outputNodes as $out) {
                        if ($hasDirectEdge($inp['id'], $out['id']) || $hasPath($inp['id'], $out['id'])) {
                            $processLeadsToOutput = true;
                            break 2;
                        }
                    }
                }
            }

            if ($processLeadsToOutput && !$hasReversedProcessOutput) {
                $seqScore += 2.5;
            } elseif (!empty($outputNodes) && !$hasReversedProcessOutput) {
                $seqErrors[] = '⚠️ Hubungkan langkah perhitungan (Proses Rumus) menuju simbol Output cetak hasil.';
            }

            // D. Output ➔ Selesai
            $hasReversedOutputEnd = false;
            if (!empty($outputNodes) && $endNode) {
                foreach ($outputNodes as $out) {
                    if ($hasPath($endNode['id'], $out['id'])) {
                        $hasReversedOutputEnd = true;
                        $hasCriticalFlowError = true;
                        $seqErrors[] = '⚠️ Alur panah terbalik: Simbol Output harus mengalir menuju "Selesai", bukan sebaliknya.';
                        break;
                    }
                }
            }

            $outputLeadsToEnd = false;
            if (!empty($outputNodes) && $endNode) {
                foreach ($outputNodes as $out) {
                    if ($hasDirectEdge($out['id'], $endNode['id']) || $hasPath($out['id'], $endNode['id'])) {
                        $outputLeadsToEnd = true;
                        break;
                    }
                }
            }

            if ($outputLeadsToEnd && !$hasReversedOutputEnd) {
                $seqScore += 2.5;
            } elseif (!empty($outputNodes) && $endNode && !$hasReversedOutputEnd) {
                $seqErrors[] = '⚠️ Hubungkan simbol Output cetak hasil menuju simbol akhir "Selesai".';
            }

            if ($hasCriticalFlowError) {
                $seqScore = 0;
            }
            $score += $seqScore;
            if ($seqScore >= 10 && empty($seqErrors)) {
                $feedback[] = '✅ Urutan alur panah tepat: Mulai ➔ Input ➔ Proses Rumus ➔ Output ➔ Selesai tersambung runtut.';
            } else {
                foreach ($seqErrors as $err) {
                    $feedback[] = $err;
                }
            }

        } else {
            // === ALUR PERCABANGAN (KELULUSAN NILAI & PERCABANGAN LAINNYA) ===
            // Standar urutan: Mulai ➔ Input ➔ Keputusan (Belah Ketupat) ➔ Cabang Ya/Tidak ➔ Output ➔ Selesai
            $branchScore = 0;
            $branchErrors = [];

            // A. Mulai ➔ Input
            $startLeadsToInput = false;
            if ($startNode && !empty($inputNodes)) {
                foreach ($inputNodes as $inp) {
                    if ($hasDirectEdge($startNode['id'], $inp['id']) || $hasPath($startNode['id'], $inp['id'])) {
                        $startLeadsToInput = true;
                        break;
                    }
                }
            }

            if ($startLeadsToInput) {
                $branchScore += 2;
            } elseif ($startNode && !empty($inputNodes)) {
                $branchErrors[] = '⚠️ Simbol "Mulai" seharusnya mengarah ke simbol Input Nilai terlebih dahulu.';
            }

            // B. Input ➔ Keputusan
            $hasReversedInputDecision = false;
            if (!empty($decisionNodes) && !empty($inputNodes)) {
                foreach ($decisionNodes as $dec) {
                    foreach ($inputNodes as $inp) {
                        if ($hasPath($dec['id'], $inp['id'])) {
                            $hasReversedInputDecision = true;
                            $hasCriticalFlowError = true;
                            $branchErrors[] = '⚠️ Alur panah terbalik: Nilai harus diinputkan sebelum diperiksa dalam simbol Keputusan (Input Nilai ➔ Keputusan).';
                            break 2;
                        }
                    }
                }
            }

            $inputLeadsToDecision = false;
            if (!empty($inputNodes) && !empty($decisionNodes)) {
                foreach ($inputNodes as $inp) {
                    foreach ($decisionNodes as $dec) {
                        if ($hasDirectEdge($inp['id'], $dec['id']) || $hasPath($inp['id'], $dec['id'])) {
                            $inputLeadsToDecision = true;
                            break 2;
                        }
                    }
                }
            }

            if ($inputLeadsToDecision && !$hasReversedInputDecision) {
                $branchScore += 2;
            } elseif (!empty($inputNodes) && !empty($decisionNodes) && !$hasReversedInputDecision) {
                $branchErrors[] = '⚠️ Hubungkan simbol Input Nilai ke simbol Keputusan.';
            }

            // C. Pemeriksaan Ketepatan Cabang "Ya" dan "Tidak" dari Simbol Keputusan
            if (!empty($decisionNodes)) {
                $mainDec = $decisionNodes[0];
                $decId = $mainDec['id'];
                $outEdges = $outgoing[$decId] ?? [];

                // Cari node Lulus dan node Remedial
                $lulusNodes = [];
                $remedialNodes = [];
                foreach ($nodes as $n) {
                    $lbl = $cleanLabels[$n['id']];
                    if (str_contains($lbl, 'lulus') && !str_contains($lbl, 'tidak lulus') && !str_contains($lbl, 'belum')) {
                        $lulusNodes[] = $n['id'];
                    }
                    if (str_contains($lbl, 'remedial') || str_contains($lbl, 'tidak lulus') || str_contains($lbl, 'gagal')) {
                        $remedialNodes[] = $n['id'];
                    }
                }

                $yesTargetId = null;
                $noTargetId = null;

                foreach ($outEdges as $oe) {
                    $lbl = $oe['label'];
                    $hnd = $oe['sourceHandle'];
                    $tgt = $oe['target'];

                    if ($lbl === 'ya' || $lbl === 'yes' || str_contains($lbl, 'ya') || $hnd === 'left') {
                        $yesTargetId = $tgt;
                    } elseif ($lbl === 'tidak' || $lbl === 'no' || str_contains($lbl, 'tidak') || $hnd === 'right') {
                        $noTargetId = $tgt;
                    }
                }

                $isSwapped = false;
                if ($yesTargetId && (in_array($yesTargetId, $remedialNodes, true) || (!empty($remedialNodes) && $hasPath($yesTargetId, $remedialNodes[0])))) {
                    $isSwapped = true;
                }
                if ($noTargetId && (in_array($noTargetId, $lulusNodes, true) || (!empty($lulusNodes) && $hasPath($noTargetId, $lulusNodes[0])))) {
                    $isSwapped = true;
                }

                if ($isSwapped) {
                    $hasCriticalFlowError = true;
                    $branchErrors[] = '⚠️ Alur percabangan tertukar: Cabang "Ya" (kondisi Nilai >= 75 terpenuhi) harus menuju ke "LULUS", sedangkan cabang "Tidak" menuju ke "REMEDIAL".';
                } elseif (count($outEdges) >= 2 && $yesTargetId && $noTargetId) {
                    $branchScore += 3;
                    $feedback[] = '✅ Arah percabangan tepat: Cabang "Ya" mengarah ke hasil Lulus dan cabang "Tidak" ke hasil Remedial.';
                } elseif (count($outEdges) >= 2) {
                    $branchScore += 2;
                    $feedback[] = '✅ Simbol Keputusan memiliki 2 cabang keluar (Ya dan Tidak).';
                } elseif (count($outEdges) === 1) {
                    $branchScore += 1;
                    $branchErrors[] = '⚠️ Simbol Keputusan baru memiliki 1 cabang keluar. Hubungkan cabang kedua untuk kondisi sebaliknya.';
                }
            }

            // D. Cabang Output Mengalir ke Selesai
            if ($endNode && !empty($outputNodes)) {
                $reachCount = 0;
                foreach ($outputNodes as $out) {
                    if ($hasDirectEdge($out['id'], $endNode['id']) || $hasPath($out['id'], $endNode['id'])) {
                        $reachCount++;
                    }
                }

                if ($reachCount >= count($outputNodes) && count($outputNodes) >= 2) {
                    $branchScore += 3;
                } elseif ($reachCount > 0) {
                    $branchScore += 1.5;
                    $branchErrors[] = '⚠️ Salah satu cabang luaran belum terhubung ke simbol "Selesai". Pastikan seluruh cabang bermuara ke Selesai.';
                } else {
                    $branchErrors[] = '⚠️ Hubungkan kedua cabang luaran (Lulus dan Remedial) menuju simbol akhir "Selesai".';
                }
            }

            if ($hasCriticalFlowError) {
                $branchScore = 0;
            }
            $score += $branchScore;
            if ($branchScore >= 10 && empty($branchErrors)) {
                $feedback[] = '✅ Urutan percabangan alur panah sangat baik: Keputusan bercabang dengan tepat dan berakhir di Selesai.';
            } else {
                foreach ($branchErrors as $err) {
                    $feedback[] = $err;
                }
            }
        }

        // 4. Keterhubungan Seluruh Simbol & Jalur Utuh Mulai ➔ Selesai (Maks 4 Poin)
        $isolatedNodes = [];
        $deadEndNodes = [];
        foreach ($nodes as $n) {
            $id = $n['id'];
            $lbl = $n['data']['label'] ?? 'Simbol';
            $inCount = count($incoming[$id] ?? []);
            $outCount = count($outgoing[$id] ?? []);

            if ($inCount === 0 && $outCount === 0) {
                $isolatedNodes[] = $lbl;
            } elseif ($inCount > 0 && $outCount === 0 && ($n['type'] ?? '') !== 'terminator') {
                $deadEndNodes[] = $lbl;
            }
        }

        if (!empty($isolatedNodes)) {
            $feedback[] = '⚠️ Simbol "' . implode('", "', array_slice($isolatedNodes, 0, 2)) . '" belum terhubung ke dalam alur (tidak memiliki panah).';
        }

        if (!empty($deadEndNodes)) {
            $feedback[] = '⚠️ Alur buntu: Simbol "' . $deadEndNodes[0] . '" belum memiliki panah keluar untuk melanjutkan alur.';
        }

        // Cek jalur utuh Mulai ➔ Selesai
        $fullPathExists = ($startNode && $endNode && $hasPath($startNode['id'], $endNode['id']));

        if ($fullPathExists && empty($isolatedNodes) && empty($deadEndNodes)) {
            $score += 4;
            $feedback[] = '✅ Alur flowchart utuh: Seluruh simbol terhubung dalam satu kesatuan alur dari Mulai hingga Selesai.';
        } elseif ($fullPathExists) {
            $score += 2;
        } else {
            $feedback[] = '⚠️ Alur terputus: Belum ada jalur panah yang tersambung utuh dari simbol "Mulai" hingga mencapai "Selesai".';
        }

        return [
            'score'                => min(25, max(0, $score)),
            'feedback'             => $feedback,
            'hasCriticalFlowError' => $hasCriticalFlowError,
        ];
    }
}