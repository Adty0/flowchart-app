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
            'diagram_data' => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $assessment = $this->evaluateDiagram($diagram);

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
            'diagram_data' => 'required',
        ]);

        $diagram = is_string($validated['diagram_data'])
            ? json_decode($validated['diagram_data'], true)
            : $validated['diagram_data'];

        if (!is_array($diagram)) {
            $diagram = ['nodes' => [], 'edges' => []];
        }

        $assessment = $this->evaluateDiagram($diagram);

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
     * Logika penilaian diagram flowchart otomatis.
     */
    private function evaluateDiagram(array $diagram): array
    {
        $nodes = $diagram['nodes'] ?? [];
        $edges = $diagram['edges'] ?? [];

        $types = array_column($nodes, 'type');
        $labels = array_map(function ($node) {
            $label = $node['data']['label'] ?? '';
            return strtolower(trim($label));
        }, $nodes);

        $feedback = [];
        $score = 0;

        // 1. Simbol Mulai (Terminator)
        $hasStart = in_array('terminator', $types, true) && (
            in_array('mulai', $labels, true) ||
            in_array('start', $labels, true) ||
            count(array_filter($labels, fn($l) => str_contains($l, 'mulai') || str_contains($l, 'start'))) > 0
        );

        if ($hasStart) {
            $score += 20;
            $feedback[] = '✅ Simbol Mulai (Terminator) sudah tersedia dengan tepat.';
        } else {
            $feedback[] = '⚠️ Tambahkan simbol Terminator dengan teks "Mulai".';
        }

        // 2. Simbol Selesai (Terminator)
        $hasEnd = in_array('terminator', $types, true) && (
            in_array('selesai', $labels, true) ||
            in_array('end', $labels, true) ||
            count(array_filter($labels, fn($l) => str_contains($l, 'selesai') || str_contains($l, 'end'))) > 0
        );

        if ($hasEnd) {
            $score += 20;
            $feedback[] = '✅ Simbol Selesai (Terminator) sudah tersedia dengan tepat.';
        } else {
            $feedback[] = '⚠️ Tambahkan simbol Terminator dengan teks "Selesai".';
        }

        // 3. Simbol Proses / Input-Output
        $hasProcessOrIO = in_array('process', $types, true) || in_array('io', $types, true);
        if ($hasProcessOrIO) {
            $score += 20;
            $feedback[] = '✅ Simbol Proses atau Input-Output sudah disertakan.';
        } else {
            $feedback[] = '⚠️ Tambahkan setidaknya satu langkah Proses atau Input/Output.';
        }

        // 4. Garis penghubung / alir
        if (count($edges) > 0) {
            $score += 20;
            $feedback[] = '✅ Garis alir (konektor panah) sudah menghubungkan simbol.';
        } else {
            $feedback[] = '⚠️ Hubungkan simbol menggunakan garis panah penghubung.';
        }

        // 5. Validitas koneksi
        $nodeIds = array_column($nodes, 'id');
        $validEdges = count(array_filter(
            $edges,
            fn($edge) => in_array($edge['source'] ?? null, $nodeIds, true)
                      && in_array($edge['target'] ?? null, $nodeIds, true)
        ));

        if (count($edges) > 0 && $validEdges === count($edges)) {
            $score += 20;
            $feedback[] = '✅ Semua konektor memiliki hubungan yang valid antar simbol.';
        } elseif (count($edges) > 0) {
            $feedback[] = '⚠️ Ada beberapa garis penghubung yang belum terhubung ke simbol yang sah.';
        } else {
            $feedback[] = '⚠️ Pastikan seluruh simbol terhubung dari awal hingga akhir.';
        }

        return [
            'score' => min(100, $score),
            'feedback' => $feedback,
        ];
    }
}