<?php

namespace App\Http\Controllers;

use App\Models\FlowchartSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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

        return view('guru.dashboard', compact(
            'submissions',
            'groupedSubmissions',
            'classList',
            'classStats',
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
     * Hapus submission tertentu oleh guru.
     */
    public function destroy($id)
    {
        $submission = FlowchartSubmission::findOrFail($id);
        $submission->delete();

        return redirect()->route('guru.dashboard')->with('success', 'Data hasil praktikum berhasil dihapus.');
    }
}
