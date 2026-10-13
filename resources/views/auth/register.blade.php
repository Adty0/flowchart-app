<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pendaftaran Siswa Baru - Laboratorium Flowchart</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Tailwind CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 via-blue-50/40 to-indigo-50 min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 text-slate-800">

    <div class="sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="text-center">
            <div class="inline-flex w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 items-center justify-center text-white text-2xl font-extrabold shadow-lg shadow-blue-500/25 mb-3">
                🎓
            </div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Pendaftaran Siswa Baru</h2>
            <p class="text-xs text-slate-500 mt-1">Buat akun untuk memulai praktikum dan membuat flowchart</p>
        </div>

        <div class="mt-8 bg-white py-8 px-6 sm:px-10 shadow-xl shadow-slate-200/60 rounded-3xl border border-slate-200/80">

            @if ($errors->any())
                <div class="mb-5 p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register') }}" method="POST" autocomplete="off" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap Siswa</label>
                    <input 
                        type="text" 
                        name="name" 
                        id="name" 
                        value="{{ old('name') }}" 
                        required 
                        autocomplete="off"
                        placeholder="Contoh: Muhammad Fikri" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div>
                    <label for="class_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kelas / Rombel</label>
                    <input 
                        type="text" 
                        name="class_name" 
                        id="class_name" 
                        value="{{ old('class_name') }}" 
                        required 
                        autocomplete="off"
                        placeholder="Contoh: 10 RPL 1 atau 10 TKJ 2" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email Siswa</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        value="{{ old('email') }}" 
                        required 
                        autocomplete="off"
                        placeholder="Tuliskan email" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi (Minimal 6 Karakter)</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="••••••••" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div>
                    <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ulangi Kata Sandi</label>
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        id="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="••••••••" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none transition-all"
                    >
                </div>

                <div class="p-3 bg-blue-50/70 border border-blue-200 rounded-xl text-xs text-blue-800 flex items-start gap-2">
                    <span>💡</span>
                    <span>Akun yang didaftarkan akan otomatis mendapatkan hak akses sebagai <b>Siswa Praktikan</b>.</span>
                </div>

                <button 
                    type="submit" 
                    class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-bold text-sm rounded-xl shadow-md shadow-blue-600/20 transition-all cursor-pointer"
                >
                    Daftar & Mulai Praktik ➜
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-600">
                Sudah memiliki akun? 
                <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-700 hover:underline">
                    Masuk ke Akun Anda
                </a>
            </div>

        </div>
    </div>

    <script>
        // Mencegah browser autofill akun tersimpan ke formulir pendaftaran siswa baru
        window.addEventListener('DOMContentLoaded', function () {
            @if(!$errors->any())
                setTimeout(function () {
                    const emailInput = document.getElementById('email');
                    const passInput = document.getElementById('password');
                    const passConfirmInput = document.getElementById('password_confirmation');
                    if (emailInput) emailInput.value = '';
                    if (passInput) passInput.value = '';
                    if (passConfirmInput) passConfirmInput.value = '';
                }, 80);
            @endif
        });
    </script>
</body>
</html>

