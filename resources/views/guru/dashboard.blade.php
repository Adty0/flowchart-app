<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Rekapitulasi Per Kelas - Portal Guru</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="bg-[#F8FAFC] text-slate-800 min-h-screen antialiased">

    <!-- Navigation Bar Guru -->
    <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 sticky top-0 z-40 shadow-xs no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white font-extrabold text-lg shadow-md shadow-indigo-500/20">
                    👨‍🏫
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Portal Guru: Rekapitulasi Nilai Per Kelas</span>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">Pendidik</span>
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{ Auth::user()->name }} ({{ Auth::user()->email }})
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a 
                    href="{{ route('flowchart.index') }}" 
                    class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all"
                >
                    <span>⚡</span>
                    <span>Buka Kanvas Flowchart</span>
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 hover:border-rose-200 text-slate-700 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1"
                    >
                        <span>🚪</span>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-semibold flex items-center justify-between shadow-sm no-print">
                <span class="flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </span>
            </div>
        @endif

        <!-- KARTU STATISTIK GLOBAL -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Total Submission</div>
                <div class="text-2xl font-black text-slate-900">{{ $totalSubmissions }}</div>
                <div class="text-[11px] text-slate-400 mt-1">Diagram alir terkumpul</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Siswa Praktikan</div>
                <div class="text-2xl font-black text-blue-600">{{ $siswaSubmit }} <span class="text-xs text-slate-400 font-normal">/ {{ $totalSiswa }} siswa</span></div>
                <div class="text-[11px] text-slate-400 mt-1">Partisipasi kelas</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Rata-Rata Nilai</div>
                <div class="text-2xl font-black text-indigo-600">{{ $averageScore }} <span class="text-xs text-slate-400 font-normal">/ 100</span></div>
                <div class="text-[11px] text-indigo-500 font-semibold mt-1">Nilai gabungan kelas</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-emerald-200 bg-emerald-50/30 shadow-xs">
                <div class="text-emerald-700 text-xs font-bold uppercase tracking-wider mb-1">Tuntas (≥ 75)</div>
                <div class="text-2xl font-black text-emerald-600">{{ $lulusCount }}</div>
                <div class="text-[11px] text-emerald-600 font-semibold mt-1">Memenuhi kriteria</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-amber-200 bg-amber-50/30 shadow-xs">
                <div class="text-amber-700 text-xs font-bold uppercase tracking-wider mb-1">Remedial (&lt; 75)</div>
                <div class="text-2xl font-black text-amber-600">{{ $remedialCount }}</div>
                <div class="text-[11px] text-amber-600 font-semibold mt-1">Perlu perbaikan alur</div>
            </div>
        </div>

        <!-- RINGKASAN PERFORMA PER KELAS -->
        <section class="bg-white rounded-3xl border border-slate-200 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>🏫</span>
                        <span>Ringkasan & Komparasi Antar Kelas</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Klik salah satu kelas untuk memfilter tampilan data secara instan.</p>
                </div>
                <span class="text-xs font-extrabold px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full">
                    {{ count($classList) }} Rombel Terdata
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($classList as $cls)
                    @php
                        $stat = $classStats[$cls] ?? ['total' => 0, 'avg' => 0, 'lulus' => 0, 'remedial' => 0];
                        $isActive = request('class_name') === $cls;
                    @endphp
                    <a 
                        href="{{ route('guru.dashboard', array_merge(request()->except('class_name'), $isActive ? [] : ['class_name' => $cls])) }}"
                        class="p-4 rounded-2xl border transition-all cursor-pointer {{ $isActive ? 'bg-indigo-50/80 border-indigo-400 shadow-sm ring-2 ring-indigo-200' : 'bg-slate-50/60 border-slate-200 hover:bg-slate-100 hover:border-slate-300' }}"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-extrabold text-sm text-slate-900 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full {{ $isActive ? 'bg-indigo-600' : 'bg-slate-400' }}"></span>
                                <span>Kelas {{ $cls }}</span>
                            </span>
                            <span class="text-xs font-extrabold px-2 py-0.5 rounded-full {{ $stat['avg'] >= 75 ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                Rerata: {{ $stat['avg'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-slate-600 mt-3 pt-2.5 border-t border-slate-200/60">
                            <span><b>{{ $stat['total'] }}</b> submission</span>
                            <span class="text-emerald-700 font-semibold">{{ $stat['lulus'] }} Tuntas</span>
                            <span class="text-amber-700 font-semibold">{{ $stat['remedial'] }} Remedial</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>

        <!-- TOOLBAR PENCARIAN & FILTER KELAS -->
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-slate-100 gap-4 no-print">
                <div>
                    <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <span>📑</span>
                        <span>Rekapitulasi Hasil Praktikum Dikelompokkan Per Kelas</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hasil pengerjaan flowchart siswa tersusun rapi berdasarkan rombongan belajar.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <form method="GET" action="{{ route('guru.dashboard') }}" class="flex flex-wrap items-center gap-2">
                        <!-- Filter Dropdown Kelas -->
                        <select 
                            name="class_name" 
                            onchange="this.form.submit()" 
                            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 outline-none"
                        >
                            <option value="">Semua Rombel / Kelas</option>
                            @foreach($classList as $cls)
                                <option value="{{ $cls }}" {{ request('class_name') === $cls ? 'selected' : '' }}>
                                    Kelas {{ $cls }}
                                </option>
                            @endforeach
                        </select>

                        <!-- Filter Status Nilai -->
                        <select 
                            name="status" 
                            onchange="this.form.submit()" 
                            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-indigo-500 outline-none"
                        >
                            <option value="">Semua Status Nilai</option>
                            <option value="lulus" {{ request('status') === 'lulus' ? 'selected' : '' }}>🟢 Tuntas (≥ 75)</option>
                            <option value="remedial" {{ request('status') === 'remedial' ? 'selected' : '' }}>🟡 Remedial (&lt; 75)</option>
                        </select>

                        <!-- Input Cari Nama -->
                        <div class="relative">
                            <input 
                                type="text" 
                                name="search" 
                                value="{{ request('search') }}" 
                                placeholder="Cari nama siswa..." 
                                class="px-3.5 py-1.5 pl-8 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:bg-white focus:border-indigo-500 outline-none w-44 transition-all"
                            >
                            <span class="absolute left-2.5 top-2 text-xs text-slate-400">🔍</span>
                        </div>

                        <button 
                            type="submit" 
                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition-all cursor-pointer"
                        >
                            Terapkan
                        </button>

                        @if(request('search') || request('status') || request('class_name'))
                            <a 
                                href="{{ route('guru.dashboard') }}" 
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition-all"
                            >
                                Reset
                            </a>
                        @endif
                    </form>

                    <button 
                        type="button" 
                        onclick="window.print()" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                        title="Cetak Rekap"
                    >
                        <span>🖨️</span>
                        <span>Cetak</span>
                    </button>
                </div>
            </div>

            <!-- TAMPILAN TABEL DIKELOMPOKKAN PER KELAS -->
            @if($groupedSubmissions->count() > 0)
                <div class="space-y-8">
                    @foreach($groupedSubmissions as $className => $classItems)
                        @php
                            $groupAvg = $classItems->count() > 0 ? round($classItems->avg('score'), 1) : 0;
                            $groupLulus = $classItems->where('score', '>=', 75)->count();
                            $groupRemedial = $classItems->where('score', '<', 75)->count();
                        @endphp

                        <div class="rounded-2xl border border-slate-200 overflow-hidden bg-white shadow-xs">
                            <!-- Header Grup Kelas -->
                            <div class="bg-gradient-to-r from-slate-50 to-indigo-50/40 px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-black text-xs shadow-xs">
                                        🏫
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-black text-slate-900 tracking-tight">
                                            Rombongan Belajar: Kelas {{ $className }}
                                        </h4>
                                        <p class="text-[11px] text-slate-500">
                                            {{ $classItems->count() }} submission dari kelas ini
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-xs">
                                    <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-bold text-slate-700 shadow-2xs">
                                        Rata-rata: <span class="text-indigo-600 font-black">{{ $groupAvg }}</span>
                                    </span>
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 font-bold text-emerald-800">
                                        ✓ {{ $groupLulus }} Tuntas
                                    </span>
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 font-bold text-amber-800">
                                        ! {{ $groupRemedial }} Remedial
                                    </span>
                                </div>
                            </div>

                            <!-- Tabel Siswa Dalam Kelas Ini -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-sm text-slate-600">
                                    <thead class="bg-slate-50/70 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                                        <tr>
                                            <th class="px-5 py-3">ID</th>
                                            <th class="px-5 py-3">Nama Siswa</th>
                                            <th class="px-5 py-3 text-center">Skor</th>
                                            <th class="px-5 py-3 text-center">Status</th>
                                            <th class="px-5 py-3">Struktur Simbol</th>
                                            <th class="px-5 py-3">Umpan Balik Evaluasi</th>
                                            <th class="px-5 py-3">Waktu Pengumpulan</th>
                                            <th class="px-5 py-3 text-right no-print">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach($classItems as $sub)
                                            @php
                                                $diagram = is_array($sub->diagram_data) ? $sub->diagram_data : json_decode($sub->diagram_data, true);
                                                $nodeCount = count($diagram['nodes'] ?? []);
                                                $edgeCount = count($diagram['edges'] ?? []);
                                                $feedbackArr = is_array($sub->feedback) ? $sub->feedback : json_decode($sub->feedback, true);
                                            @endphp
                                            <tr class="hover:bg-slate-50/80 transition-colors">
                                                <td class="px-5 py-3.5 font-mono text-xs font-semibold text-slate-400">
                                                    #{{ $sub->id }}
                                                </td>
                                                <td class="px-5 py-3.5">
                                                    <div class="font-bold text-slate-900">{{ $sub->student_name ?: 'Anonim' }}</div>
                                                    @if($sub->user)
                                                        <div class="text-[11px] text-slate-400">{{ $sub->user->email }}</div>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3.5 text-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-extrabold {{ $sub->score >= 75 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                                                        {{ $sub->score }}
                                                    </span>
                                                </td>
                                                <td class="px-5 py-3.5 text-center">
                                                    @if($sub->score >= 75)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700">
                                                            <span>✓</span> Tuntas
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700">
                                                            <span>!</span> Remedial
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3.5 text-xs text-slate-600">
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-semibold">{{ $nodeCount }} Simbol</span>
                                                        <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded font-semibold">{{ $edgeCount }} Panah</span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-3.5 text-xs text-slate-600 max-w-xs">
                                                    @if(is_array($feedbackArr) && count($feedbackArr) > 0)
                                                        <div class="truncate text-[11px] text-slate-500" title="{{ implode(' | ', $feedbackArr) }}">
                                                            • {{ $feedbackArr[0] }}
                                                            @if(count($feedbackArr) > 1)
                                                                <span class="text-indigo-600 font-bold ml-1">(+{{ count($feedbackArr)-1 }} lagi)</span>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="text-slate-400">-</span>
                                                    @endif
                                                </td>
                                                <td class="px-5 py-3.5 text-xs text-slate-400">
                                                    {{ $sub->created_at ? $sub->created_at->format('d M Y, H:i') : '-' }}
                                                </td>
                                                <td class="px-5 py-3.5 text-right no-print">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        <button 
                                                            type="button" 
                                                            onclick='openDetailModal(@json($sub))'
                                                            class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                            title="Lihat Detail Diagram"
                                                        >
                                                            👁️ Detail
                                                        </button>

                                                        <form action="{{ route('guru.submission.destroy', $sub->id) }}" method="POST" onsubmit="return confirm('Hapus hasil pengumpulan ini?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button 
                                                                type="submit" 
                                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all cursor-pointer"
                                                                title="Hapus Submission"
                                                            >
                                                                🗑️
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-14 bg-slate-50/70 rounded-2xl border-2 border-dashed border-slate-200">
                    <div class="text-4xl mb-2">📋</div>
                    <p class="text-slate-700 text-sm font-bold">Tidak Ada Data Pengumpulan untuk Kelas Ini</p>
                    <p class="text-xs text-slate-500 mt-1">Coba sesuaikan pilihan filter kelas atau pencarian nama siswa.</p>
                </div>
            @endif

        </section>

    </main>

    <!-- MODAL DETAIL DIAGRAM SISWA -->
    <div id="detailModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 animate-fadeIn max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <span>🔍</span>
                        <span id="modalStudentName">Detail Diagram Siswa</span>
                    </h3>
                    <p class="text-xs text-slate-500" id="modalMeta">Submission ID & Waktu</p>
                </div>
                <button type="button" onclick="closeDetailModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold flex items-center justify-center cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Skor, Kelas & Status -->
                <div class="p-4 bg-slate-50 rounded-2xl flex items-center justify-between border border-slate-200">
                    <div>
                        <span class="text-xs text-slate-500 font-bold uppercase tracking-wider block">Hasil Evaluasi:</span>
                        <span class="text-2xl font-black text-slate-900" id="modalScore">100 / 100</span>
                        <span id="modalClassBadge" class="inline-block mt-1 px-2.5 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-800">
                            Kelas 10 RPL 1
                        </span>
                    </div>
                    <div id="modalStatusBadge"></div>
                </div>

                <!-- Daftar Feedback Lengkap -->
                <div>
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Catatan Evaluasi Lengkap:</h4>
                    <div id="modalFeedbackList" class="space-y-1.5"></div>
                </div>

                <!-- Struktur Simbol -->
                <div>
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Simbol yang Disusun oleh Siswa:</h4>
                    <div id="modalNodeBadges" class="flex flex-wrap gap-2"></div>
                </div>

                <!-- JSON Data -->
                <div>
                    <details class="text-xs">
                        <summary class="font-bold text-slate-500 cursor-pointer hover:text-slate-700">Lihat Struktur Data Mentah (JSON)</summary>
                        <pre id="modalJsonData" class="mt-2 p-3 bg-slate-900 text-emerald-400 font-mono rounded-xl overflow-x-auto text-[11px]"></pre>
                    </details>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100 text-right">
                <button type="button" onclick="closeDetailModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <script>
        function openDetailModal(sub) {
            document.getElementById('modalStudentName').textContent = sub.student_name || 'Anonim';
            document.getElementById('modalMeta').textContent = `Submission #${sub.id} • ${new Date(sub.created_at).toLocaleString('id-ID')}`;
            document.getElementById('modalScore').textContent = `${sub.score} / 100`;
            document.getElementById('modalClassBadge').textContent = `Kelas: ${sub.class_name || 'Umum'}`;

            const badgeContainer = document.getElementById('modalStatusBadge');
            if (sub.score >= 75) {
                badgeContainer.innerHTML = '<span class="px-3 py-1 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full font-bold text-xs">✓ Tuntas</span>';
            } else {
                badgeContainer.innerHTML = '<span class="px-3 py-1 bg-amber-100 text-amber-800 border border-amber-300 rounded-full font-bold text-xs">! Perlu Remedial</span>';
            }

            const feedbackContainer = document.getElementById('modalFeedbackList');
            feedbackContainer.innerHTML = '';
            let feedbacks = sub.feedback;
            if (typeof feedbacks === 'string') {
                try { feedbacks = JSON.parse(feedbacks); } catch(e) { feedbacks = []; }
            }
            if (Array.isArray(feedbacks) && feedbacks.length > 0) {
                feedbacks.forEach(fb => {
                    const el = document.createElement('div');
                    el.className = 'p-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-700';
                    el.textContent = fb;
                    feedbackContainer.appendChild(el);
                });
            } else {
                feedbackContainer.innerHTML = '<p class="text-xs text-slate-400">Tidak ada catatan.</p>';
            }

            const nodesContainer = document.getElementById('modalNodeBadges');
            nodesContainer.innerHTML = '';
            let diagram = sub.diagram_data;
            if (typeof diagram === 'string') {
                try { diagram = JSON.parse(diagram); } catch(e) { diagram = {}; }
            }
            const nodes = diagram?.nodes || [];
            if (nodes.length > 0) {
                nodes.forEach((n, idx) => {
                    const badge = document.createElement('span');
                    badge.className = 'px-2.5 py-1 bg-slate-100 border border-slate-200 rounded-lg text-xs font-semibold text-slate-700';
                    badge.textContent = `${n.type?.toUpperCase()}: "${n.data?.label || '-'}"`;
                    nodesContainer.appendChild(badge);
                });
            } else {
                nodesContainer.innerHTML = '<p class="text-xs text-slate-400">Tidak ada node tersimpan.</p>';
            }

            document.getElementById('modalJsonData').textContent = JSON.stringify(diagram, null, 2);
            document.getElementById('detailModal').classList.remove('hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }
    </script>
</body>
</html>
