<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk - Laboratorium Flowchart</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CDN & Assets -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50 min-h-screen flex flex-col justify-center py-6 sm:py-12 px-3 sm:px-6 lg:px-8 text-slate-800">

    <div class="w-full sm:max-w-md mx-auto">
        <div class="text-center">
            <img src="{{ asset('images/logo.png') }}" alt="Laboratorium Flowchart" class="w-24 h-24 sm:w-28 sm:h-28 mx-auto object-contain drop-shadow-md mb-2 rounded-2xl">
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Laboratorium Flowchart</h2>
            <p class="text-xs text-slate-500 mt-1">Platform Belajar Logika Algoritma & Penilaian Otomatis</p>
        </div>

        <div class="mt-6 sm:mt-8 bg-white py-6 px-5 sm:py-8 sm:px-10 shadow-xl shadow-slate-200/60 rounded-2xl sm:rounded-3xl border border-slate-200/80">

            @if(session('success'))
                <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Guru / NISN Siswa</label>
                    <input 
                        type="text" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}" 
                        required 
                        placeholder="Contoh: 000000 atau 0106572131 atau guru@sekolah.id" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Kata Sandi</label>
                    </div>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        placeholder="••••••••" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div class="flex items-center justify-between py-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                        <span class="text-xs text-slate-600 font-medium">Ingat saya</span>
                    </label>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-md shadow-blue-600/20 transition-all cursor-pointer"
                >
                    Masuk ke Sistem ➜
                </button>
            </form>

            <!-- Akses Cepat Uji Coba & Simulasi Siswa -->
            <div class="mt-4 pt-3 border-t border-slate-100 space-y-2">
                <span class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 text-center">
                    Akses Cepat Pengujian:
                </span>
                <div class="grid grid-cols-1 gap-2">
                    <a 
                        href="{{ route('login.simulasi') }}" 
                        class="w-full py-2.5 px-3 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 text-indigo-900 rounded-xl text-xs font-bold transition-all flex items-center justify-between cursor-pointer active:scale-95 shadow-2xs"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-lg">🎓</span>
                            <div class="text-left">
                                <span class="block font-black text-xs text-indigo-950">Masuk Simulasi Siswa</span>
                                <span class="block text-[10px] text-indigo-700 font-medium">NISN: 000000 (Kelas Simulasi)</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-indigo-600 bg-indigo-100/80 px-2 py-0.5 rounded-full font-mono font-bold">1-Klik ➜</span>
                    </a>

                    <a 
                        href="{{ route('login.demo') }}" 
                        class="w-full py-2.5 px-3 bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-800 rounded-xl text-xs font-bold transition-all flex items-center justify-between cursor-pointer active:scale-95 shadow-2xs"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-lg">👤</span>
                            <div class="text-left">
                                <span class="block font-black text-xs text-slate-900">Masuk Akun Demo Siswa</span>
                                <span class="block text-[10px] text-slate-500 font-medium">Record pengerjaan masuk ke Kelas Demo</span>
                            </div>
                        </div>
                        <span class="text-[10px] text-slate-600 bg-slate-200/80 px-2 py-0.5 rounded-full font-mono font-bold">1-Klik ➜</span>
                    </a>
                </div>
            </div>

            <!-- Link Pendaftaran Siswa -->
            <div class="mt-6 pt-5 border-t border-slate-100 text-center text-xs text-slate-600">
                Belum punya akun siswa? 
                <a href="{{ route('register') }}" class="font-bold text-blue-600 hover:text-blue-700 hover:underline">
                    Daftar sebagai Siswa Baru
                </a>
            </div>

        </div>
    </div>
</body>
</html>

