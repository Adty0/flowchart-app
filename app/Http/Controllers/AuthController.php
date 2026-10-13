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
     * Proses autentikasi login pengguna.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
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

