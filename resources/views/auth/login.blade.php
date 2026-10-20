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
            <div class="inline-flex w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 items-center justify-center text-white text-xl sm:text-2xl font-extrabold shadow-lg shadow-blue-500/25 mb-2.5 sm:mb-3">
                ⚡
            </div>
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
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}" 
                        required 
                        placeholder="Tuliskan email" 
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

            <!-- Akun Contoh untuk Guru & Siswa -->
            <div class="mt-4 p-3 rounded-xl bg-slate-50 border border-slate-200 text-[11px] text-slate-600">
                <span class="font-bold text-slate-700 block mb-1">🔑 Akun Default Tersedia:</span>
                <div class="flex justify-between items-center py-0.5 border-b border-slate-200/60">
                    <span><b>Guru:</b> guru@sekolah.id</span>
                    <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700">password123</span>
                </div>
                <div class="flex justify-between items-center py-0.5 mt-0.5">
                    <span><b>Siswa:</b> siswa@sekolah.id</span>
                    <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700">password123</span>
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

