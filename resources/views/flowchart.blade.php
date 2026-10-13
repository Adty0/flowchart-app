<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laboratorium Alur Logika: Flowchart App</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <meta name="user-name" content="{{ Auth::user() ? Auth::user()->name : '' }}">
    <meta name="user-class" content="{{ Auth::user() ? (Auth::user()->class_name ?: '10 RPL 1') : '10 RPL 1' }}">
    <meta name="user-role" content="{{ Auth::user() ? Auth::user()->role : '' }}">
    <!-- Tailwind CSS Script & Vite Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/flowchart.jsx'])
</head>
<body class="bg-[#F8FAFC] text-slate-800 font-sans min-h-screen antialiased selection:bg-blue-100 selection:text-blue-800">

    <!-- Top Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 sticky top-0 z-50 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center text-white font-extrabold text-lg shadow-md shadow-blue-500/20">
                    ⚡
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Laboratorium Algoritma & Flowchart</span>
                        @if(Auth::user() && Auth::user()->isGuru())
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">Mode Guru</span>
                        @else
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">Modul Siswa</span>
                        @endif
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{ Auth::user() ? Auth::user()->name : 'Siswa Praktikan' }} 
                        @if(Auth::user() && Auth::user()->class_name)
                            • <span class="font-bold text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded">Kelas: {{ Auth::user()->class_name }}</span>
                        @endif
                        • DB: <span class="font-mono text-blue-600 font-semibold">flowchartapp (MySQL)</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if(Auth::user() && Auth::user()->isGuru())
                    <a 
                        href="{{ route('guru.dashboard') }}" 
                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-xs font-bold transition-all flex items-center gap-1"
                    >
                        <span>👨‍🏫</span>
                        <span>Dashboard Rekap Guru</span>
                    </a>
                @endif

                @auth
                    <!-- Tombol Logout -->
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
                @endauth
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        <!-- Kartu Sambutan Siswa yang Nyaman di Mata -->
        <div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold uppercase tracking-wider mb-2.5">
                    <span>✨</span>
                    <span>Ruang Praktik Mandiri Siswa</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 mb-1.5">
                    Susun Flowchart, Uji Logika Algoritma, dan Raih Skor Maksimal!
                </h2>
                <p class="text-slate-500 text-xs sm:text-sm leading-relaxed">
                    Pilih studi kasus yang ingin dikerjakan di bawah. Kanvas disediakan bersih agar Anda dapat melatih kemampuan merangkai simbol dan panah alir secara mandiri.
                </p>
            </div>
            <div class="flex items-center gap-3 self-start md:self-auto">
                <div class="hidden lg:flex items-center gap-2 px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-2xl text-xs font-bold text-slate-600">
                    <span>💡</span>
                    <span>Sistem memeriksa kesesuaian alur secara otomatis</span>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-semibold flex items-center justify-between shadow-sm">
                <span class="flex items-center gap-2">
                    <span>🎉</span>
                    <span>{{ session('success') }}</span>
                </span>
            </div>
        @endif

        <!-- Card Flowchart Editor React -->
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-4 sm:p-6 transition-all">
            <!-- Mount point untuk React FlowchartEditor -->
            <div id="flowchart-app">
                <div class="h-[600px] flex flex-col items-center justify-center text-slate-400 bg-slate-50 rounded-2xl border-2 border-dashed border-slate-200 gap-3">
                    <div class="w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                    <p class="text-sm font-semibold text-slate-600">Menyiapkan kanvas flowchart interaktif...</p>
                </div>
            </div>
        </section>

        <!-- Daftar Riwayat Pengerjaan Siswa -->
        <section class="bg-white rounded-3xl shadow-sm border border-slate-200 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-2 mb-4">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                        <span>📋</span>
                        <span>Riwayat Nilai & Submission Praktikan</span>
                        <span class="text-xs font-extrabold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-700">
                            {{ $submissions->count() }} tersimpan
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Semua diagram yang diperiksa tersimpan otomatis di database MySQL.</p>
                </div>

                <div>
                    <button 
                        type="button" 
                        onclick="window.location.reload()" 
                        class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
                    >
                        <span>🔄</span>
                        <span>Segarkan Data</span>
                    </button>
                </div>
            </div>

            @if(isset($submissions) && $submissions->count() > 0)
                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-50/80 text-slate-700 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-5 py-3.5">ID</th>
                                <th class="px-5 py-3.5">Nama Siswa</th>
                                <th class="px-5 py-3.5 text-center">Skor Evaluasi</th>
                                <th class="px-5 py-3.5">Detail Komponen</th>
                                <th class="px-5 py-3.5">Catatan Koreksi</th>
                                <th class="px-5 py-3.5">Waktu Selesai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($submissions as $item)
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="px-5 py-4 font-mono text-xs font-semibold text-slate-400">
                                        #{{ $item->id }}
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="font-bold text-slate-900">{{ $item->student_name ?: 'Anonim' }}</div>
                                        <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            {{ $item->class_name ?: '10 RPL 1' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold {{ $item->score >= 80 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : ($item->score >= 60 ? 'bg-amber-100 text-amber-800 border border-amber-300' : 'bg-rose-100 text-rose-800 border border-rose-300') }}">
                                            {{ $item->score }} / 100
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-600">
                                        @php
                                            $diagram = is_array($item->diagram_data) ? $item->diagram_data : json_decode($item->diagram_data, true);
                                            $nodeCount = count($diagram['nodes'] ?? []);
                                            $edgeCount = count($diagram['edges'] ?? []);
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 bg-slate-100 rounded text-slate-700 font-medium">{{ $nodeCount }} Simbol</span>
                                            <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded font-medium">{{ $edgeCount }} Garis Panah</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-500 max-w-xs truncate">
                                        @php
                                            $feedbackArr = is_array($item->feedback) ? $item->feedback : json_decode($item->feedback, true);
                                        @endphp
                                        @if(is_array($feedbackArr) && count($feedbackArr) > 0)
                                            <span title="{{ implode(' | ', $feedbackArr) }}">
                                                {{ count($feedbackArr) }} catatan evaluasi
                                            </span>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-xs text-slate-400 font-medium">
                                        {{ $item->created_at ? $item->created_at->format('d M Y, H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 bg-slate-50/70 rounded-2xl border-2 border-dashed border-slate-200">
                    <div class="text-4xl mb-2">💡</div>
                    <p class="text-slate-700 text-sm font-bold">Belum Ada Hasil Praktik</p>
                    <p class="text-xs text-slate-500 mt-1">Coba susun flowchart pada kanvas di atas, lalu tekan tombol <b>"Periksa & Simpan ke DB"</b>.</p>
                </div>
            @endif
        </section>

    </main>

    <!-- Footer -->
    <footer class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-xs text-slate-400 border-t border-slate-200/60 mt-10">
        <p>Aplikasi Praktik Flowchart Siswa • Menggunakan Laravel, React (@xyflow/react), Tailwind CSS, & MySQL XAMPP.</p>
    </footer>

</body>
</html>
