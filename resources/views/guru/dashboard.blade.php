<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="app-url" content="{{ url('/') }}">
    <meta name="export-excel-url" content="{{ route('guru.export_excel') }}">
    <title>Portal Guru - Kelola Studi Kasus & Rekapitulasi Nilai</title>

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
                <img src="{{ asset('images/logo.png') }}" alt="Logo" class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl object-contain shadow-xs flex-shrink-0 bg-white border border-slate-200/80 p-0.5">
                <div>
                    <h1 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Portal Pendidik Flowchart</span>
                        <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">Guru</span>
                    </h1>
                    <p class="text-xs text-slate-500">
                        {{ Auth::user()->name }} ({{ Auth::user()->email }})
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 sm:gap-3">
                <a 
                    href="{{ route('flowchart.index') }}" 
                    class="inline-flex items-center px-3 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-all shadow-2xs"
                >
                    <span class="hidden sm:inline">Buka Kanvas Flowchart</span>
                    <span class="sm:hidden">Kanvas</span>
                </a>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button 
                        type="submit" 
                        class="px-2.5 sm:px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs hover:shadow-md cursor-pointer flex items-center gap-1.5 active:scale-95"
                        title="Keluar Akun"
                    >
                        <svg class="w-3.5 h-3.5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                            <line x1="12" y1="2" x2="12" y2="12"></line>
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-7">

        <!-- Notifikasi Flash -->
        @if(session('success'))
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-semibold flex items-center justify-between shadow-xs no-print">
                <span class="flex items-center gap-2">
                    <span class="text-lg">✅</span>
                    <span>{{ session('success') }}</span>
                </span>
            </div>
        @endif

        @if(session('error'))
            <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-sm font-semibold flex items-center justify-between shadow-xs no-print">
                <span class="flex items-center gap-2">
                    <span class="text-lg">⚠️</span>
                    <span>{{ session('error') }}</span>
                </span>
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-sm font-semibold shadow-xs no-print space-y-1">
                <div class="flex items-center gap-2 font-bold text-amber-950">
                    <span>⚠️</span>
                    <span>Terdapat kesalahan pada isian form:</span>
                </div>
                <ul class="list-disc pl-7 text-xs space-y-0.5 text-amber-800">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- TAB NAVIGASI PORTAL GURU -->
        @php
            $activeTab = request('tab', 'rekap');
        @endphp
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3 no-print">
            <div class="flex items-center gap-2 sm:gap-3">
                <a 
                    href="{{ route('guru.dashboard', array_merge(request()->except('tab'), ['tab' => 'rekap'])) }}" 
                    class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition-all {{ $activeTab === 'rekap' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                >
                    <span>📊</span>
                    <span>Rekap Nilai Siswa</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ $activeTab === 'rekap' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $totalSubmissions }}
                    </span>
                </a>

                <a 
                    href="{{ route('guru.dashboard', array_merge(request()->except('tab'), ['tab' => 'studi_kasus'])) }}" 
                    class="px-3.5 sm:px-4 py-2 sm:py-2.5 rounded-2xl text-xs sm:text-sm font-extrabold flex items-center gap-2 transition-all {{ $activeTab === 'studi_kasus' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}"
                >
                    <span>📚</span>
                    <span>Kelola Studi Kasus & Kunci Koreksi</span>
                    <span class="px-2 py-0.5 rounded-full text-[11px] {{ $activeTab === 'studi_kasus' ? 'bg-indigo-500 text-white' : 'bg-slate-100 text-slate-600' }}">
                        {{ $caseStudies->count() }}
                    </span>
                </a>
            </div>

            @if($activeTab === 'studi_kasus')
                <button 
                    type="button" 
                    onclick="openCreateCaseModal()" 
                    class="px-4 py-2 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl text-xs sm:text-sm font-extrabold shadow-md shadow-emerald-600/20 transition-all active:scale-95 cursor-pointer flex items-center gap-2"
                >
                    <span>➕</span>
                    <span>Tambah Studi Kasus Baru</span>
                </button>
            @endif
        </div>

        @if($activeTab === 'studi_kasus')
        <!-- ================================================================= -->
        <!-- TAB 2: KELOLA STUDI KASUS, ALUR ACUAN & KUNCI JAWABAN KOREKSI     -->
        <!-- ================================================================= -->
        <div class="space-y-6">

            <!-- Banner Pengantar Modul Studi Kasus -->
            <div class="bg-gradient-to-r from-indigo-700 via-purple-700 to-indigo-900 rounded-3xl p-6 sm:p-7 text-white shadow-xl relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 opacity-10 text-9xl pointer-events-none select-none">📐</div>
                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-md text-xs font-bold text-indigo-100 mb-2.5">
                            <span>⚙️</span> Modul Manajemen Soal & Mesin Penilaian Otomatis
                        </span>
                        <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                            Katalog Studi Kasus & Kunci Jawaban Acuan
                        </h2>
                        <p class="text-xs sm:text-sm text-indigo-100/90 mt-1.5 leading-relaxed">
                            Definisikan soal algoritma baru untuk siswa Anda. Masukkan kata kunci input, formula rumus, atau kondisi percabangan Ya/Tidak. Sistem akan mencocokkan diagram alir siswa secara otomatis dan memberikan skor dari 0 hingga 100 secara instan.
                        </p>
                    </div>

                    <button 
                        type="button" 
                        onclick="openCreateCaseModal()" 
                        class="px-5 py-3 bg-white hover:bg-indigo-50 text-indigo-950 rounded-2xl text-xs sm:text-sm font-extrabold shadow-lg transition-all active:scale-95 cursor-pointer flex items-center gap-2 self-start md:self-auto flex-shrink-0"
                    >
                        <span class="text-base">➕</span>
                        <span>Tambah Kasus Baru</span>
                    </button>
                </div>
            </div>

            <!-- Grid Kartu Studi Kasus -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($caseStudies as $case)
                    @php
                        $refFlow = is_array($case->reference_flow) ? $case->reference_flow : json_decode($case->reference_flow, true);
                        $refSteps = $refFlow['steps'] ?? [];
                        $refConnections = $refFlow['connections'] ?? [];
                    @endphp
                    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs hover:shadow-md transition-all p-5 sm:p-6 flex flex-col justify-between">
                        <div class="space-y-4">
                            <!-- Header Kartu -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-2xl flex-shrink-0 shadow-2xs">
                                        {{ $case->icon ?: '📐' }}
                                    </div>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="text-base font-black text-slate-900 leading-snug">{{ $case->title }}</h3>
                                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $case->flow_type === 'branching' ? 'bg-orange-100 text-orange-800 border border-orange-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                                                {{ $case->badge ?: ($case->flow_type === 'branching' ? 'Alur Percabangan' : 'Alur Sekuensial') }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[11px] font-mono text-slate-400">key: {{ $case->case_key }}</span>
                                            @if($case->is_default)
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold">
                                                    🔒 Bawaan Sistem
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 text-[10px] font-bold">
                                                    ✍️ Buatan Guru
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @if(!$case->is_default)
                                    <form action="{{ route('guru.case_study.destroy', $case->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button 
                                            type="button" 
                                            onclick="triggerCardConfirm({
                                                title: 'Hapus Studi Kasus?',
                                                message: 'Apakah Anda yakin ingin menghapus studi kasus &quot;{{ addslashes($case->title) }}&quot;? Data soal tidak akan lagi tersedia untuk siswa.',
                                                form: this.form,
                                                confirmText: 'Ya, Hapus Kasus',
                                                type: 'danger',
                                                icon: '🗑️'
                                            })"
                                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-all cursor-pointer"
                                            title="Hapus Studi Kasus Ini"
                                        >
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if($case->formula)
                                <div class="px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200/90 text-xs font-bold text-slate-700 flex items-center gap-2">
                                    <span>💡</span>
                                    <span>{{ $case->formula }}</span>
                                </div>
                            @endif

                            <p class="text-xs text-slate-600 leading-relaxed">
                                <b>Petunjuk Soal:</b> {{ $case->description }}
                            </p>

                            <!-- Parameter Kunci Koreksi Jawaban Siswa -->
                            <div class="bg-indigo-50/50 border border-indigo-100 rounded-2xl p-4 space-y-2.5">
                                <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wider flex items-center gap-1.5">
                                    <span>🎯</span> Kunci Koreksi & Parameter Penilaian
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Input yang Diperiksa:</span>
                                        <span class="font-bold text-sky-800">{{ $case->expected_inputs ?: 'Data Bebas' }}</span>
                                    </div>

                                    @if($case->flow_type === 'branching')
                                        <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Syarat Percabangan:</span>
                                            <span class="font-bold text-orange-700">{{ $case->expected_decision ?: 'Kondisi Logika' }}</span>
                                        </div>
                                        <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90">
                                            <span class="block text-[10px] font-bold text-emerald-600 uppercase tracking-wider">Output Cabang [Ya]:</span>
                                            <span class="font-bold text-emerald-800">{{ $case->expected_yes_output ?: '-' }}</span>
                                        </div>
                                        <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90">
                                            <span class="block text-[10px] font-bold text-rose-600 uppercase tracking-wider">Output Cabang [Tidak]:</span>
                                            <span class="font-bold text-rose-800">{{ $case->expected_no_output ?: '-' }}</span>
                                        </div>
                                    @else
                                        <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Rumus / Perhitungan:</span>
                                            <span class="font-bold text-emerald-800">{{ $case->expected_process ?: 'Perhitungan Bebas' }}</span>
                                        </div>
                                        <div class="p-2.5 bg-white rounded-xl border border-indigo-100/90 sm:col-span-2">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Output Luaran yang Dicetak:</span>
                                            <span class="font-bold text-blue-800">{{ $case->expected_outputs ?: 'Tampilkan Hasil' }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Alur Flowchart Standar Acuan (Visual Flow Steps) -->
                            <div class="space-y-2">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <span>🧭</span> Alur Flowchart Kunci Jawaban:
                                </span>
                                @if(count($refSteps) > 0)
                                    <div class="p-3 bg-slate-50/80 rounded-2xl border border-slate-200 space-y-2.5">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach($refSteps as $idx => $step)
                                                @php
                                                    $stepType = $step['type'] ?? 'terminator';
                                                    $stepBadgeClass = match($stepType) {
                                                        'terminator' => 'bg-amber-100 border-amber-300 text-amber-950',
                                                        'io' => 'bg-sky-100 border-sky-300 text-sky-950',
                                                        'process' => 'bg-emerald-100 border-emerald-300 text-emerald-950',
                                                        'decision' => 'bg-orange-100 border-orange-300 text-orange-950',
                                                        default => 'bg-slate-100 border-slate-300 text-slate-800'
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl border text-[11px] font-bold {{ $stepBadgeClass }}">
                                                    <span>{{ $step['label'] ?? '' }}</span>
                                                </span>
                                                @if($idx < count($refSteps) - 1)
                                                    <span class="text-slate-400 font-bold text-xs">➔</span>
                                                @endif
                                            @endforeach
                                        </div>

                                        @if(count($refConnections) > 0)
                                            <div class="text-[11px] text-slate-500 font-mono pt-2 border-t border-slate-200/70 space-y-0.5">
                                                @foreach($refConnections as $conn)
                                                    <div>• {{ $conn }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400 italic">Alur bebas sesuai arahan tatap muka guru.</p>
                                @endif
                            </div>
                        </div>

                        <!-- Footer Kartu -->
                        <div class="mt-5 pt-3.5 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                            <span>Partisipasi Pengerjaan:</span>
                            <span class="font-extrabold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                {{ $case->submissions_count }} diagram siswa terkumpul
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        @else
        <!-- ================================================================= -->
        <!-- TAB 1: REKAPITULASI PENILAIAN SISWA (PER ROMBEL / KELAS)          -->
        <!-- ================================================================= -->

        <!-- KARTU STATISTIK (SESUAI DATA ROMBEL AKTIF) -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1 flex items-center justify-between">
                    <span>Total Submission</span>
                    @if($selectedClass)
                        <span class="text-[10px] font-extrabold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded">{{ $selectedClass }}</span>
                    @endif
                </div>
                <div class="text-2xl font-black text-slate-900">{{ $totalSubmissions }}</div>
                <div class="text-[11px] text-slate-400 mt-1">{{ $selectedClass ? 'Rombel ' . $selectedClass : 'Seluruh kelas terdata' }}</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Siswa Praktikan</div>
                <div class="text-2xl font-black text-blue-600">{{ $siswaSubmit }} <span class="text-xs text-slate-400 font-normal">/ {{ $totalSiswa }} siswa</span></div>
                <div class="text-[11px] text-slate-400 mt-1">Partisipasi {{ $selectedClass ? 'Rombel ' . $selectedClass : 'kelas' }}</div>
            </div>

            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
                <div class="text-slate-500 text-xs font-bold uppercase tracking-wider mb-1">Rata-Rata Nilai</div>
                <div class="text-2xl font-black text-indigo-600">{{ $averageScore }} <span class="text-xs text-slate-400 font-normal">/ 100</span></div>
                <div class="text-[11px] text-indigo-500 font-semibold mt-1">Nilai {{ $selectedClass ? 'Rombel ' . $selectedClass : 'gabungan kelas' }}</div>
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
        <section class="bg-white rounded-3xl border border-slate-200 p-6 shadow-xs">
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
        <section class="bg-white rounded-3xl shadow-xs border border-slate-200 p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-slate-100 gap-4 no-print">
                <div>
                    <h3 class="text-lg font-black text-slate-900 flex items-center gap-2">
                        <span>📑</span>
                        <span>Rekapitulasi Hasil Praktikum Siswa</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Hasil pengerjaan flowchart siswa tersusun rapi berdasarkan rombongan belajar.</p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <form method="GET" action="{{ route('guru.dashboard') }}" class="flex flex-wrap items-center gap-2">
                        <input type="hidden" name="tab" value="rekap">

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

                        <!-- Filter Studi Kasus -->
                        <select 
                            name="case_key" 
                            onchange="this.form.submit()" 
                            class="px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 focus:bg-white focus:border-indigo-500 outline-none"
                        >
                            <option value="">Semua Studi Kasus</option>
                            @foreach($caseStudies as $cs)
                                <option value="{{ $cs->case_key }}" {{ request('case_key') === $cs->case_key ? 'selected' : '' }}>
                                    {{ $cs->title }}
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

                        @if(request('search') || request('status') || request('class_name') || request('case_key'))
                            <a 
                                href="{{ route('guru.dashboard', ['tab' => 'rekap']) }}" 
                                class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition-all"
                            >
                                Reset
                            </a>
                        @endif
                    </form>

                    <div class="flex items-center gap-2">
                        <button 
                            type="button" 
                            onclick="window.print()" 
                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1 cursor-pointer"
                            title="Cetak Dokumen / Print PDF"
                        >
                            <span>🖨️</span>
                            <span class="hidden sm:inline">Print</span>
                        </button>
                    </div>
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
                                            <th class="px-5 py-3">Kasus Soal</th>
                                            <th class="px-5 py-3 text-center">Skor</th>
                                            <th class="px-5 py-3 text-center">Status</th>
                                            <th class="px-5 py-3">Struktur Simbol</th>
                                            <th class="px-5 py-3">Umpan Balik Evaluasi</th>
                                            <th class="px-5 py-3">Waktu</th>
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
                                                $caseTitle = $caseStudies->firstWhere('case_key', $sub->case_key)?->title ?? ($sub->case_key ?: 'Umum');
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
                                                <td class="px-5 py-3.5 text-xs text-slate-700">
                                                    <span class="px-2.5 py-1 rounded-lg bg-indigo-50 border border-indigo-100 font-semibold text-indigo-900">
                                                        {{ $caseTitle }}
                                                    </span>
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
                                                    @elseif($sub->is_remedial_open)
                                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700 animate-pulse border border-blue-200">
                                                            <span>🔓</span> Remidi Dibuka
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
                                                    {{ $sub->created_at ? $sub->created_at->format('d M, H:i') : '-' }}
                                                </td>
                                                <td class="px-5 py-3.5 text-right no-print">
                                                    <div class="flex items-center justify-end gap-1.5">
                                                        @if($sub->score < 75)
                                                            <form action="{{ route('guru.submission.toggle_remedial', $sub->id) }}" method="POST">
                                                                @csrf
                                                                <button 
                                                                    type="submit" 
                                                                    class="px-2.5 py-1.5 {{ $sub->is_remedial_open ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-amber-500 hover:bg-amber-600 text-white' }} rounded-lg text-xs font-bold transition-all shadow-2xs cursor-pointer active:scale-95 flex items-center gap-1"
                                                                    title="{{ $sub->is_remedial_open ? 'Klik untuk menutup akses remedial' : 'Klik untuk membuka akses remedial' }}"
                                                                >
                                                                    <span>{{ $sub->is_remedial_open ? '✅ Remidi Terbuka' : '🔓 Buka Remidi' }}</span>
                                                                </button>
                                                            </form>
                                                        @endif

                                                        <button 
                                                            type="button" 
                                                            onclick='openDetailModal(@json($sub))'
                                                            class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                                            title="Lihat Detail Diagram"
                                                        >
                                                            👁️ Detail
                                                        </button>

                                                        <form action="{{ route('guru.submission.destroy', $sub->id) }}" method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button 
                                                                type="button" 
                                                                onclick="triggerCardConfirm({
                                                                    title: 'Hapus Rekap Penilaian?',
                                                                    message: 'Hapus rekaman pengumpulan dari siswa &quot;{{ addslashes($sub->student_name) }}&quot;? Data nilai dan diagram akan dihapus.',
                                                                    form: this.form,
                                                                    confirmText: 'Ya, Hapus Nilai',
                                                                    type: 'danger',
                                                                    icon: '🗑️'
                                                                })"
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

        @endif

    </main>

    <!-- ================================================================= -->
    <!-- MODAL 1: TAMBAH STUDI KASUS BARU BESERTA ALUR & KUNCI KOREKSI     -->
    <!-- ================================================================= -->
    <div id="createCaseModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-start justify-center p-3 sm:p-5 pt-4 sm:pt-8 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-7 shadow-2xl border border-slate-200 my-0 animate-fadeIn max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-200 text-indigo-700 flex items-center justify-center text-lg font-bold">
                        ➕
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900">
                            Tambah Studi Kasus & Kunci Alur Baru
                        </h3>
                        <p class="text-xs text-slate-500">Definisikan studi kasus dan parameter pemeriksaan otomatis.</p>
                    </div>
                </div>
                <button type="button" onclick="closeCreateCaseModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-bold flex items-center justify-center cursor-pointer">
                    ✕
                </button>
            </div>

            <form action="{{ route('guru.case_study.store') }}" method="POST" class="mt-5 space-y-5">
                @csrf

                <!-- 1. Judul Kasus & Emoji Icon -->
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Judul Studi Kasus / Soal <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="title" 
                            id="caseTitleInput"
                            required 
                            placeholder="Contoh: Menghitung Keliling Lingkaran" 
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none"
                            oninput="updatePreview()"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Ikon Emoji
                        </label>
                        <input 
                            type="text" 
                            name="icon" 
                            id="caseIconInput"
                            value="📐" 
                            placeholder="📐 / ⭕ / 🛒" 
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-900 text-center focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none"
                            oninput="updatePreview()"
                        >
                    </div>
                </div>

                <!-- 2. Pilihan Tipe Alur: Sekuensial vs Percabangan -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">
                        Tipe Alur Algoritma <span class="text-rose-500">*</span>
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="p-3 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-indigo-50/60 cursor-pointer flex items-start gap-3 transition-all has-[:checked]:bg-indigo-50 has-[:checked]:border-indigo-500 has-[:checked]:ring-2 has-[:checked]:ring-indigo-200">
                            <input 
                                type="radio" 
                                name="flow_type" 
                                value="sequential" 
                                checked 
                                class="mt-1 text-indigo-600 focus:ring-indigo-500" 
                                onchange="handleFlowTypeChange('sequential')"
                            >
                            <div>
                                <span class="block text-xs font-black text-slate-900">Alur Sekuensial (Lurus)</span>
                                <span class="text-[11px] text-slate-500 leading-tight">Perhitungan matematis: Mulai ➔ Input ➔ Proses Rumus ➔ Output ➔ Selesai.</span>
                            </div>
                        </label>

                        <label class="p-3 rounded-2xl border border-slate-200 bg-slate-50 hover:bg-orange-50/60 cursor-pointer flex items-start gap-3 transition-all has-[:checked]:bg-orange-50 has-[:checked]:border-orange-500 has-[:checked]:ring-2 has-[:checked]:ring-orange-200">
                            <input 
                                type="radio" 
                                name="flow_type" 
                                value="branching" 
                                class="mt-1 text-orange-600 focus:ring-orange-500" 
                                onchange="handleFlowTypeChange('branching')"
                            >
                            <div>
                                <span class="block text-xs font-black text-slate-900">Alur Percabangan (Decision)</span>
                                <span class="text-[11px] text-slate-500 leading-tight">Pengambilan keputusan: Mulai ➔ Input ➔ Keputusan Ya/Tidak ➔ Cetak ➔ Selesai.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 3. Rumus / Kriteria & Deskripsi Soal -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Rumus / Petunjuk Ringkas
                        </label>
                        <input 
                            type="text" 
                            name="formula" 
                            id="caseFormulaInput"
                            placeholder="Contoh: Rumus: Keliling = 4 × Sisi" 
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none"
                            oninput="updatePreview()"
                        >
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Sub-Judul Badge
                        </label>
                        <input 
                            type="text" 
                            name="badge" 
                            id="caseBadgeInput"
                            placeholder="Contoh: Alur Sekuensial" 
                            class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none"
                        >
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Deskripsi Soal untuk Siswa <span class="text-rose-500">*</span>
                    </label>
                    <textarea 
                        name="description" 
                        required 
                        rows="2" 
                        placeholder="Rancang flowchart yang membaca masukan..." 
                        class="w-full px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-900 focus:bg-white focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none leading-relaxed"
                    ></textarea>
                </div>

                <!-- 4. Bagian Kunci Jawaban & Parameter Penilaian Otomatis -->
                <div class="p-4 rounded-2xl bg-gradient-to-br from-indigo-50/70 to-purple-50/40 border border-indigo-200 space-y-4">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🎯</span>
                        <div>
                            <h4 class="text-xs font-black text-indigo-950 uppercase tracking-wider">
                                Kunci Jawaban & Parameter Koreksi Otomatis
                            </h4>
                            <p class="text-[11px] text-slate-500">Definisikan masukan, proses rumus matematika, atau kondisi percabangan yang harus ada pada diagram siswa.</p>
                        </div>
                    </div>

                    <!-- Input Utama & Opsi Tambah Input Tambahan -->
                    <div class="p-3 bg-white/80 rounded-xl border border-sky-200/80">
                        <label class="block text-xs font-bold text-sky-950 mb-1 flex items-center justify-between">
                            <span>Kata Kunci Input (Pisahkan dengan koma)</span>
                            <span class="text-[10px] text-sky-600 font-semibold">Simbol Jajar Genjang (I/O)</span>
                        </label>
                        <input 
                            type="text" 
                            name="expected_inputs" 
                            id="expectedInputs"
                            placeholder="Contoh: sisi, s, panjang" 
                            class="w-full px-3.5 py-2 bg-white border border-sky-300 rounded-xl text-xs font-bold text-sky-950 focus:border-sky-500 focus:ring-2 focus:ring-sky-100 outline-none"
                            oninput="updatePreview()"
                        >
                        <div id="extraInputsContainer" class="space-y-2 mt-2"></div>
                        <button 
                            type="button" 
                            onclick="addExtraInputRow()" 
                            class="mt-2 text-[11px] font-bold text-sky-700 hover:text-sky-800 bg-sky-50 hover:bg-sky-100 border border-sky-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer"
                        >
                            <span>➕</span>
                            <span>Tambah Input Tambahan (Input 2, Input 3, dst)</span>
                        </button>
                    </div>

                    <!-- Mode Sekuensial: Proses & Output -->
                    <div id="sequentialFields" class="space-y-3">
                        <!-- Rumus / Proses & Opsi Tambahan -->
                        <div class="p-3 bg-white/80 rounded-xl border border-emerald-200/80">
                            <label class="block text-xs font-bold text-emerald-950 mb-1 flex items-center justify-between">
                                <span>Kata Kunci Rumus / Proses</span>
                                <span class="text-[10px] text-emerald-600 font-semibold">Simbol Persegi Panjang (Proses)</span>
                            </label>
                            <input 
                                type="text" 
                                name="expected_process" 
                                id="expectedProcess"
                                placeholder="Contoh: 4 * sisi, keliling" 
                                class="w-full px-3.5 py-2 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-emerald-950 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 outline-none"
                                oninput="updatePreview()"
                            >
                            <div id="extraProcessContainer" class="space-y-2 mt-2"></div>
                            <button 
                                type="button" 
                                onclick="addExtraProcessRow()" 
                                class="mt-2 text-[11px] font-bold text-emerald-700 hover:text-emerald-800 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer"
                            >
                                <span>➕</span>
                                <span>Tambah Rumus / Proses Tambahan</span>
                            </button>
                        </div>

                        <!-- Luaran / Output & Opsi Tambahan -->
                        <div class="p-3 bg-white/80 rounded-xl border border-blue-200/80">
                            <label class="block text-xs font-bold text-blue-950 mb-1 flex items-center justify-between">
                                <span>Kata Kunci Output</span>
                                <span class="text-[10px] text-blue-600 font-semibold">Simbol Jajar Genjang (Cetak)</span>
                            </label>
                            <input 
                                type="text" 
                                name="expected_outputs" 
                                id="expectedOutputs"
                                placeholder="Contoh: cetak, hasil, keliling" 
                                class="w-full px-3.5 py-2 bg-white border border-blue-300 rounded-xl text-xs font-bold text-blue-950 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                                oninput="updatePreview()"
                            >
                            <div id="extraOutputContainer" class="space-y-2 mt-2"></div>
                            <button 
                                type="button" 
                                onclick="addExtraOutputRow()" 
                                class="mt-2 text-[11px] font-bold text-blue-700 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 border border-blue-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer"
                            >
                                <span>➕</span>
                                <span>Tambah Output Tambahan</span>
                            </button>
                        </div>
                    </div>

                    <!-- Mode Percabangan: Syarat Decision, Output Ya, Output Tidak -->
                    <div id="branchingFields" class="space-y-3 hidden">
                        <div class="p-3 bg-white/80 rounded-xl border border-orange-200/80">
                            <label class="block text-xs font-bold text-orange-950 mb-1 flex items-center justify-between">
                                <span>Syarat Keputusan / Kondisi Belah Ketupat</span>
                                <span class="text-[10px] text-orange-600 font-semibold">Simbol Decision</span>
                            </label>
                            <input 
                                type="text" 
                                name="expected_decision" 
                                id="expectedDecision"
                                placeholder="Contoh: nilai >= 75? atau belanja >= 100000?" 
                                class="w-full px-3.5 py-2 bg-white border border-orange-300 rounded-xl text-xs font-bold text-orange-950 focus:border-orange-500 focus:ring-2 focus:ring-orange-100 outline-none"
                                oninput="updatePreview()"
                            >
                            <div id="extraDecisionContainer" class="space-y-2 mt-2"></div>
                            <button 
                                type="button" 
                                onclick="addExtraDecisionRow()" 
                                class="mt-2 text-[11px] font-bold text-orange-700 hover:text-orange-800 bg-orange-50 hover:bg-orange-100 border border-orange-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5 transition-all cursor-pointer"
                            >
                                <span>➕</span>
                                <span>Tambah Keputusan / Syarat Tambahan</span>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-emerald-700 mb-1">
                                    Output Cabang [Ya] (Kondisi Terpenuhi)
                                </label>
                                <input 
                                    type="text" 
                                    name="expected_yes_output" 
                                    id="expectedYesOutput"
                                    placeholder="Contoh: LULUS atau Diskon 10%" 
                                    class="w-full px-3.5 py-2 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-emerald-950 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 outline-none"
                                    oninput="updatePreview()"
                                >
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-rose-700 mb-1">
                                    Output Cabang [Tidak] (Tidak Terpenuhi)
                                </label>
                                <input 
                                    type="text" 
                                    name="expected_no_output" 
                                    id="expectedNoOutput"
                                    placeholder="Contoh: REMEDIAL atau Bayar Normal" 
                                    class="w-full px-3.5 py-2 bg-white border border-rose-300 rounded-xl text-xs font-bold text-rose-950 focus:border-rose-500 focus:ring-2 focus:ring-rose-100 outline-none"
                                    oninput="updatePreview()"
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Live Preview Alur Flowchart Standar Acuan -->
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                        <span>👁️</span> Pratinjau Alur Flowchart Acuan yang Terbentuk:
                    </span>
                    <div id="livePreviewContainer" class="flex flex-wrap items-center gap-1.5 p-2.5 bg-white rounded-xl border border-slate-200/80">
                        <!-- Preview badges dynamically updated by JS -->
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button 
                        type="button" 
                        onclick="closeCreateCaseModal()" 
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all active:scale-95 cursor-pointer"
                    >
                        Simpan Studi Kasus & Aktifkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================================================================= -->
    <!-- MODAL 2: DETAIL DIAGRAM SISWA (VIEW JSON & FEEDBACK)              -->
    <!-- ================================================================= -->
    <div id="detailModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 hidden flex items-start justify-center p-3 sm:p-5 pt-6 sm:pt-10 overflow-y-auto">
        <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 animate-fadeIn my-0 max-h-[88vh] overflow-y-auto">
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
        // Tab & Modal State Management
        let currentFlowType = 'sequential';

        function openCreateCaseModal() {
            document.getElementById('createCaseModal').classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            updatePreview();
        }

        function closeCreateCaseModal() {
            document.getElementById('createCaseModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        function handleFlowTypeChange(type) {
            currentFlowType = type;
            const seqEl = document.getElementById('sequentialFields');
            const branchEl = document.getElementById('branchingFields');

            if (type === 'branching') {
                seqEl.classList.add('hidden');
                branchEl.classList.remove('hidden');
            } else {
                seqEl.classList.remove('hidden');
                branchEl.classList.add('hidden');
            }
            updatePreview();
        }

        function addExtraInputRow() {
            const container = document.getElementById('extraInputsContainer');
            if (!container) return;
            const count = container.children.length + 2;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input 
                    type="text" 
                    name="extra_inputs[]" 
                    placeholder="Input Tambahan ${count} (misal: tinggi, t, lebar)" 
                    class="w-full px-3.5 py-1.5 bg-white border border-sky-300 rounded-xl text-xs font-bold text-sky-950 focus:border-sky-500 outline-none"
                    oninput="updatePreview()"
                >
                <button type="button" onclick="this.parentElement.remove(); updatePreview();" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold cursor-pointer" title="Hapus">✕</button>
            `;
            container.appendChild(row);
            updatePreview();
        }

        function addExtraProcessRow() {
            const container = document.getElementById('extraProcessContainer');
            if (!container) return;
            const count = container.children.length + 2;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input 
                    type="text" 
                    name="extra_process[]" 
                    placeholder="Rumus/Proses Tambahan ${count} (misal: luas = panjang * lebar)" 
                    class="w-full px-3.5 py-1.5 bg-white border border-emerald-300 rounded-xl text-xs font-bold text-emerald-950 focus:border-emerald-500 outline-none"
                    oninput="updatePreview()"
                >
                <button type="button" onclick="this.parentElement.remove(); updatePreview();" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold cursor-pointer" title="Hapus">✕</button>
            `;
            container.appendChild(row);
            updatePreview();
        }

        function addExtraDecisionRow() {
            const container = document.getElementById('extraDecisionContainer');
            if (!container) return;
            const count = container.children.length + 2;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input 
                    type="text" 
                    name="extra_decisions[]" 
                    placeholder="Syarat Keputusan Tambahan ${count} (misal: kehadiran >= 80%?)" 
                    class="w-full px-3.5 py-1.5 bg-white border border-orange-300 rounded-xl text-xs font-bold text-orange-950 focus:border-orange-500 outline-none"
                    oninput="updatePreview()"
                >
                <button type="button" onclick="this.parentElement.remove(); updatePreview();" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold cursor-pointer" title="Hapus">✕</button>
            `;
            container.appendChild(row);
            updatePreview();
        }

        function addExtraOutputRow() {
            const container = document.getElementById('extraOutputContainer');
            if (!container) return;
            const count = container.children.length + 2;
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2';
            row.innerHTML = `
                <input 
                    type="text" 
                    name="extra_outputs[]" 
                    placeholder="Output Tambahan ${count} (misal: cetak keliling)" 
                    class="w-full px-3.5 py-1.5 bg-white border border-blue-300 rounded-xl text-xs font-bold text-blue-950 focus:border-blue-500 outline-none"
                    oninput="updatePreview()"
                >
                <button type="button" onclick="this.parentElement.remove(); updatePreview();" class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold cursor-pointer" title="Hapus">✕</button>
            `;
            container.appendChild(row);
            updatePreview();
        }

        function updatePreview() {
            const container = document.getElementById('livePreviewContainer');
            if (!container) return;

            // Kumpulkan semua input
            const mainInput = document.getElementById('expectedInputs')?.value?.trim();
            const extraInputEls = document.querySelectorAll('input[name="extra_inputs[]"]');
            const allInputs = [];
            if (mainInput) allInputs.push(mainInput);
            extraInputEls.forEach(el => {
                if (el.value.trim()) allInputs.push(el.value.trim());
            });
            const inputLabel = allInputs.length > 0 ? allInputs.join(', ') : 'Data Masukan';

            const badges = [];

            // 1. Terminator Mulai
            badges.push({ label: 'Mulai', color: 'bg-amber-100 border-amber-300 text-amber-950' });

            // 2. I/O Input
            badges.push({ label: 'Input (' + inputLabel + ')', color: 'bg-sky-100 border-sky-300 text-sky-950' });

            if (currentFlowType === 'branching') {
                const mainDec = document.getElementById('expectedDecision')?.value?.trim() || 'Kondisi ?';
                const extraDecEls = document.querySelectorAll('input[name="extra_decisions[]"]');
                badges.push({ label: mainDec, color: 'bg-orange-100 border-orange-300 text-orange-950' });
                extraDecEls.forEach(el => {
                    if (el.value.trim()) badges.push({ label: el.value.trim(), color: 'bg-orange-100 border-orange-300 text-orange-950' });
                });

                const yesOut = document.getElementById('expectedYesOutput')?.value?.trim() || 'Output Ya';
                const noOut = document.getElementById('expectedNoOutput')?.value?.trim() || 'Output Tidak';

                badges.push({ label: '[Ya] ' + yesOut, color: 'bg-emerald-100 border-emerald-300 text-emerald-950' });
                badges.push({ label: '[Tidak] ' + noOut, color: 'bg-rose-100 border-rose-300 text-rose-950' });
            } else {
                const mainProc = document.getElementById('expectedProcess')?.value?.trim();
                const extraProcEls = document.querySelectorAll('input[name="extra_process[]"]');
                const allProc = [];
                if (mainProc) allProc.push(mainProc);
                extraProcEls.forEach(el => {
                    if (el.value.trim()) allProc.push(el.value.trim());
                });
                if (allProc.length > 0) {
                    allProc.forEach(p => {
                        badges.push({ label: p, color: 'bg-emerald-100 border-emerald-300 text-emerald-950' });
                    });
                } else {
                    badges.push({ label: 'Rumus / Proses', color: 'bg-emerald-100 border-emerald-300 text-emerald-950' });
                }

                const mainOut = document.getElementById('expectedOutputs')?.value?.trim();
                const extraOutEls = document.querySelectorAll('input[name="extra_outputs[]"]');
                const allOut = [];
                if (mainOut) allOut.push(mainOut);
                extraOutEls.forEach(el => {
                    if (el.value.trim()) allOut.push(el.value.trim());
                });
                const outLabel = allOut.length > 0 ? allOut.join(', ') : 'Hasil';
                badges.push({ label: 'Cetak ' + outLabel, color: 'bg-sky-100 border-sky-300 text-sky-950' });
            }

            // Terminator Selesai
            badges.push({ label: 'Selesai', color: 'bg-amber-100 border-amber-300 text-amber-950' });

            container.innerHTML = badges.map((b, i) => {
                const arrow = i < badges.length - 1 ? '<span class="text-slate-400 font-bold text-xs">➔</span>' : '';
                return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl border text-[11px] font-bold ${b.color}"><span>${b.label}</span></span> ${arrow}`;
            }).join(' ');
        }

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
            document.body.classList.add('overflow-hidden');
        }

        function closeDetailModal() {
            document.getElementById('detailModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }

        // Initialize preview on DOM load
        document.addEventListener('DOMContentLoaded', () => {
            updatePreview();
        });
    </script>

    <!-- Card Modal Konfirmasi Interaktif (Modern Web Dialog Standards) -->
    <dialog id="cardConfirmDialog" class="p-0 bg-transparent border-0 rounded-3xl backdrop:bg-slate-900/60 backdrop:backdrop-blur-xs w-full max-w-md fixed top-8 sm:top-14 inset-x-0 mx-auto z-50">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-2xl p-6 sm:p-7 text-center relative overflow-hidden animate-scaleUp">
            <div id="cardConfirmBar" class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-500 to-red-600"></div>
            
            <div id="cardConfirmIconBg" class="w-14 h-14 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-3.5 shadow-xs">
                <span id="cardConfirmIcon">🗑️</span>
            </div>

            <h3 id="cardConfirmTitle" class="text-base sm:text-lg font-black text-slate-900 tracking-tight mb-2">
                Hapus Data?
            </h3>
            <p id="cardConfirmMessage" class="text-xs sm:text-sm text-slate-600 leading-relaxed mb-6">
                Apakah Anda yakin ingin melakukan tindakan ini?
            </p>

            <div class="flex items-center justify-center gap-2.5 w-full">
                <button 
                    type="button" 
                    onclick="closeCardConfirm()" 
                    class="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 border border-slate-200/80 transition-all cursor-pointer active:scale-95"
                >
                    Batal
                </button>
                <button 
                    type="button" 
                    id="cardConfirmBtn" 
                    class="flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-600/25 transition-all cursor-pointer active:scale-95"
                >
                    Ya, Hapus
                </button>
            </div>
        </div>
    </dialog>

    <script>
        let pendingFormToSubmit = null;

        function triggerCardConfirm({ title, message, form, confirmText = 'Ya, Hapus', type = 'danger', icon = '🗑️' }) {
            pendingFormToSubmit = form;
            const dialog = document.getElementById('cardConfirmDialog');
            if (!dialog) return;

            document.getElementById('cardConfirmTitle').textContent = title;
            document.getElementById('cardConfirmMessage').textContent = message;
            document.getElementById('cardConfirmIcon').textContent = icon;
            
            const confirmBtn = document.getElementById('cardConfirmBtn');
            confirmBtn.textContent = confirmText;

            const bar = document.getElementById('cardConfirmBar');
            const iconBg = document.getElementById('cardConfirmIconBg');

            if (type === 'danger') {
                bar.className = 'absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-rose-500 to-red-600';
                iconBg.className = 'w-14 h-14 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-3.5 shadow-xs';
                confirmBtn.className = 'flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-md shadow-rose-600/25 transition-all cursor-pointer active:scale-95';
            } else {
                bar.className = 'absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-blue-500 to-indigo-600';
                iconBg.className = 'w-14 h-14 rounded-2xl bg-blue-50 border border-blue-200 text-blue-600 flex items-center justify-center text-2xl mx-auto mb-3.5 shadow-xs';
                confirmBtn.className = 'flex-1 py-2.5 px-4 rounded-xl text-xs sm:text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/25 transition-all cursor-pointer active:scale-95';
            }

            confirmBtn.onclick = () => {
                if (pendingFormToSubmit) {
                    pendingFormToSubmit.submit();
                }
                closeCardConfirm();
            };

            document.body.classList.add('overflow-hidden');
            if (typeof dialog.showModal === 'function') {
                dialog.showModal();
            } else {
                dialog.setAttribute('open', '');
            }
        }

        function closeCardConfirm() {
            const dialog = document.getElementById('cardConfirmDialog');
            if (!dialog) return;
            pendingFormToSubmit = null;
            document.body.classList.remove('overflow-hidden');
            if (typeof dialog.close === 'function') {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
            }
        }
    </script>
    <script>
        function exportDashboardTableToXLSX(e) {
            // Biarkan browser langsung mengunduh file .xlsx asli yang digenerate oleh server
            return true;
        }
    </script>
</body>
</html>