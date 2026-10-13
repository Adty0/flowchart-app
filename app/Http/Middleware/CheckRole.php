<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if ($request->user()->role !== $role) {
            // Jika guru mencoba akses halaman siswa atau sebaliknya
            if ($request->user()->isGuru()) {
                return redirect()->route('guru.dashboard')->with('error', 'Halaman tersebut khusus untuk siswa. Anda dialihkan ke Dashboard Guru.');
            }

            return redirect()->route('flowchart.index')->with('error', 'Anda tidak memiliki hak akses sebagai Guru.');
        }

        return $next($request);
    }
}

