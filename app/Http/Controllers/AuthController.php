<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return Auth::user()->isGuru()
                ? redirect()->route('guru.dashboard')
                : redirect()->route('flowchart.index');
        }

        return view('auth.login');
    }

    /**
     * Akses Cepat 1-Klik Masuk sebagai Siswa Demo (Kelas Demo).
     */
    public function loginDemo(Request $request)
    {
        $demoUser = User::firstOrCreate(
            ['email' => 'demo@sekolah.id'],
            [
                'name'       => 'Siswa Demo',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_name' => 'Kelas Demo',
            ]
        );

        Auth::login($demoUser);
        $request->session()->regenerate();

        return redirect()->route('flowchart.index')
            ->with('success', 'Berhasil masuk sebagai Siswa Demo (Kelas Demo). Silakan mencoba kanvas flowchart.');
    }

    /**
     * Akses Cepat 1-Klik Masuk sebagai Simulasi Siswa (NISN: 000000, Kelas Simulasi).
     */
    public function loginSimulasi(Request $request)
    {
        $simulasiUser = User::firstOrCreate(
            ['email' => '000000@sekolah.id'],
            [
                'name'       => 'Simulasi Siswa',
                'password'   => Hash::make('password123'),
                'role'       => 'siswa',
                'class_name' => 'Kelas Simulasi',
            ]
        );

        Auth::login($simulasiUser);
        $request->session()->regenerate();

        return redirect()->route('flowchart.index')
            ->with('success', 'Berhasil masuk sebagai Simulasi Siswa (NISN: 000000). Silakan mencoba kanvas flowchart.');
    }

    /**
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email atau NISN siswa wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $loginInput = trim($request->input('email'));
        if (!str_contains($loginInput, '@')) {
            $loginInput .= '@sekolah.id';
        }

        $credentials = [
            'email' => $loginInput,
            'password' => $request->input('password'),
        ];

        $remember = $request->boolean('remember');

        $loginSuccess = Auth::attempt($credentials, $remember);

        // Fleksibilitas kata sandi default seed ('password123' atau 'password')
        if (!$loginSuccess) {
            if ($credentials['password'] === 'password') {
                $loginSuccess = Auth::attempt(['email' => $credentials['email'], 'password' => 'password123'], $remember);
            } elseif ($credentials['password'] === 'password123') {
                $loginSuccess = Auth::attempt(['email' => $credentials['email'], 'password' => 'password'], $remember);
            }
        }

        if ($loginSuccess) {
            $request->session()->regenerate();

            $user = Auth::user();

            if ($user->isGuru()) {
                return redirect()->intended(route('guru.dashboard'))
                    ->with('success', 'Selamat datang kembali, ' . $user->name . '! (Portal Guru)');
            }

            return redirect()->intended(route('flowchart.index'))
                ->with('success', 'Selamat datang, ' . $user->name . '! Silakan mulai praktikum flowchart.');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->onlyInput('email');
    }

    /**
     * Tampilkan formulir pendaftaran untuk siswa.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return Auth::user()->isGuru()
                ? redirect()->route('guru.dashboard')
                : redirect()->route('flowchart.index');
        }

        return view('auth.register');
    }

    /**
     * Proses pendaftaran siswa baru.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'class_name' => ['required', 'string', 'max:50'],
            'email'      => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'name.required'       => 'Nama lengkap siswa wajib diisi.',
            'class_name.required' => 'Kelas siswa wajib diisi (Contoh: 10 RPL 1).',
            'email.required'      => 'Email siswa wajib diisi.',
            'email.unique'        => 'Email tersebut sudah terdaftar.',
            'password.required'   => 'Kata sandi wajib diisi.',
            'password.min'        => 'Kata sandi minimal 6 karakter.',
            'password.confirmed'  => 'Konfirmasi kata sandi tidak cocok.',
        ]);

        $user = User::create([
            'name'       => $validated['name'],
            'class_name' => $validated['class_name'],
            'email'      => $validated['email'],
            'role'       => 'siswa', // Otomatis terdaftar sebagai siswa
            'password'   => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('flowchart.index')
            ->with('success', 'Pendaftaran berhasil! Selamat datang, ' . $user->name);
    }

    /**
     * Logout pengguna.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar.');
    }
}

