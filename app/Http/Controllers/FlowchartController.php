<?php

namespace App\Http\Controllers;

use App\Models\FlowchartSubmission;
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

        return view('flowchart', compact('submissions', 'user'));
    }

    /**
     * Simpan submission flowchart baru.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'student_name' => 'nullable|string|max:100',
            'class_name'   => 'nullable|string|max:50',
            'case_key'     => 'nullable|string|max:50',
            'diagram_data' => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $caseKey = $validated['case_key'] ?? ($diagram['case_key'] ?? 'luas_segitiga');
        $assessment = $this->evaluateDiagram($diagram, $caseKey);

        $submission = FlowchartSubmission::create([
            'user_id'      => $user ? $user->id : null,
            'student_name' => $validated['student_name'] ?? ($user ? $user->name : 'Siswa Praktikan'),
            'class_name'   => $validated['class_name'] ?? ($user && $user->class_name ? $user->class_name : '10 RPL 1'),
            'diagram_data' => $diagram,
            'score'        => $assessment['score'],
            'feedback'     => $assessment['feedback'],
            'checked_at'   => now(),
        ]);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success'    => true,
                'message'    => 'Flowchart berhasil disimpan!',
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
            'student_name' => 'nullable|string|max:100',
            'class_name'   => 'nullable|string|max:50',
            'case_key'     => 'nullable|string|max:50',
            'diagram_data' => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $caseKey = $validated['case_key'] ?? ($diagram['case_key'] ?? 'luas_segitiga');
        $assessment = $this->evaluateDiagram($diagram, $caseKey);

        $submission = FlowchartSubmission::create([
            'user_id'      => $user ? $user->id : null,
            'student_name' => $validated['student_name'] ?? ($user ? $user->name : 'Siswa Praktikan'),
            'class_name'   => $validated['class_name'] ?? ($user && $user->class_name ? $user->class_name : '10 RPL 1'),
            'diagram_data' => $diagram,
            'score'        => $assessment['score'],
            'feedback'     => $assessment['feedback'],
            'checked_at'   => now(),
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
        // 3. EVALUASI GARIS ALIR & KONEKTOR PANAH (25 Poin)
        // ====================================================================
        $nodeIds = array_column($nodes, 'id');
        $validEdges = 0;
        foreach ($edges as $edge) {
            $src = $edge['source'] ?? null;
            $tgt = $edge['target'] ?? null;
            if ($src && $tgt && in_array($src, $nodeIds, true) && in_array($tgt, $nodeIds, true) && $src !== $tgt) {
                $validEdges++;
            }
        }

        if (count($edges) > 0 && $validEdges === count($edges)) {
            $score += 25;
            $feedback[] = '✅ Alur konektor panah terhubung dengan runtut dan valid dari awal hingga akhir.';
        } elseif (count($edges) > 0) {
            $score += 12;
            $feedback[] = '⚠️ Terdapat beberapa garis penghubung yang belum terpasang ke simbol yang sah.';
        } else {
            $feedback[] = '⚠️ Hubungkan simbol-simbol di atas menggunakan garis panah penghubung.';
        }

        return [
            'score'    => min(100, max(0, $score)),
            'feedback' => $feedback,
        ];
    }
}