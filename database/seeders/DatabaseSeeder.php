<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FlowchartSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Guru
        $guru = User::updateOrCreate(
            ['email' => 'guru@sekolah.id'],
            [
                'name' => 'Bpk. Budi Santoso, M.Kom',
                'role' => 'guru',
                'class_name' => 'Guru Informatika',
                'password' => Hash::make('password123'),
            ]
        );

        // 2. Akun Siswa 1 - Kelas 10 RPL 1
        $siswa1 = User::updateOrCreate(
            ['email' => 'siswa@sekolah.id'],
            [
                'name' => 'Ahmad Pratama',
                'role' => 'siswa',
                'class_name' => '10 RPL 1',
                'password' => Hash::make('password123'),
            ]
        );

        // 3. Akun Siswa 2 - Kelas 10 RPL 1
        $siswa2 = User::updateOrCreate(
            ['email' => 'siti@sekolah.id'],
            [
                'name' => 'Siti Nurhaliza',
                'role' => 'siswa',
                'class_name' => '10 RPL 1',
                'password' => Hash::make('password123'),
            ]
        );

        // 4. Akun Siswa 3 - Kelas 10 RPL 2
        $siswa3 = User::updateOrCreate(
            ['email' => 'reza@sekolah.id'],
            [
                'name' => 'Reza Fahlevi',
                'role' => 'siswa',
                'class_name' => '10 RPL 2',
                'password' => Hash::make('password123'),
            ]
        );

        // 5. Akun Siswa 4 - Kelas 10 TKJ 1
        $siswa4 = User::updateOrCreate(
            ['email' => 'dina@sekolah.id'],
            [
                'name' => 'Dina Oktavia',
                'role' => 'siswa',
                'class_name' => '10 TKJ 1',
                'password' => Hash::make('password123'),
            ]
        );

        // Update atau buat submission yang sudah ada
        FlowchartSubmission::truncate();

        FlowchartSubmission::create([
            'user_id' => $siswa1->id,
            'student_name' => $siswa1->name,
            'class_name' => '10 RPL 1',
            'diagram_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai']],
                    ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input alas & tinggi']],
                    ['id' => '3', 'type' => 'process', 'data' => ['label' => 'luas = 0.5 * alas * tinggi']],
                    ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Tampilkan luas']],
                    ['id' => '5', 'type' => 'terminator', 'data' => ['label' => 'Selesai']],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => '1', 'target' => '2'],
                    ['id' => 'e2', 'source' => '2', 'target' => '3'],
                    ['id' => 'e3', 'source' => '3', 'target' => '4'],
                    ['id' => 'e4', 'source' => '4', 'target' => '5'],
                ],
            ],
            'score' => 100,
            'feedback' => [
                '✅ Simbol Mulai (Terminator) sudah tersedia.',
                '✅ Simbol Selesai (Terminator) sudah tersedia.',
                '✅ Simbol Proses / Input-Output sudah disertakan.',
                '✅ Garis alir (konektor) sudah menghubungkan simbol.',
                '✅ Semua konektor terhubung dengan valid antar simbol.',
            ],
            'checked_at' => now()->subHours(3),
        ]);

        FlowchartSubmission::create([
            'user_id' => $siswa2->id,
            'student_name' => $siswa2->name,
            'class_name' => '10 RPL 1',
            'diagram_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai']],
                    ['id' => '2', 'type' => 'process', 'data' => ['label' => 'Hitung luas']],
                    ['id' => '3', 'type' => 'terminator', 'data' => ['label' => 'Selesai']],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => '1', 'target' => '2'],
                    ['id' => 'e2', 'source' => '2', 'target' => '3'],
                ],
            ],
            'score' => 80,
            'feedback' => [
                '✅ Simbol Mulai (Terminator) sudah tersedia.',
                '✅ Simbol Selesai (Terminator) sudah tersedia.',
                '✅ Simbol Proses / Input-Output sudah disertakan.',
                '✅ Garis alir (konektor) sudah menghubungkan simbol.',
                '⚠️ Tambahkan juga simbol Input/Output data.',
            ],
            'checked_at' => now()->subHours(2),
        ]);

        FlowchartSubmission::create([
            'user_id' => $siswa3->id,
            'student_name' => $siswa3->name,
            'class_name' => '10 RPL 2',
            'diagram_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai']],
                    ['id' => '2', 'type' => 'process', 'data' => ['label' => 'Inisialisasi']],
                ],
                'edges' => [],
            ],
            'score' => 40,
            'feedback' => [
                '✅ Simbol Mulai (Terminator) sudah tersedia.',
                '⚠️ Tambahkan simbol Terminator dengan teks "Selesai".',
                '✅ Simbol Proses / Input-Output sudah disertakan.',
                '⚠️ Hubungkan simbol menggunakan garis panah penghubung.',
            ],
            'checked_at' => now()->subHours(1),
        ]);

        FlowchartSubmission::create([
            'user_id' => $siswa4->id,
            'student_name' => $siswa4->name,
            'class_name' => '10 TKJ 1',
            'diagram_data' => [
                'nodes' => [
                    ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai']],
                    ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input data']],
                    ['id' => '3', 'type' => 'process', 'data' => ['label' => 'Proses']],
                    ['id' => '4', 'type' => 'terminator', 'data' => ['label' => 'Selesai']],
                ],
                'edges' => [
                    ['id' => 'e1', 'source' => '1', 'target' => '2'],
                    ['id' => 'e2', 'source' => '2', 'target' => '3'],
                    ['id' => 'e3', 'source' => '3', 'target' => '4'],
                ],
            ],
            'score' => 100,
            'feedback' => [
                '✅ Simbol Mulai (Terminator) sudah tersedia.',
                '✅ Simbol Selesai (Terminator) sudah tersedia.',
                '✅ Simbol Proses / Input-Output sudah disertakan.',
                '✅ Garis alir (konektor) sudah menghubungkan simbol.',
                '✅ Semua konektor terhubung dengan valid antar simbol.',
            ],
            'checked_at' => now()->subMinutes(15),
        ]);
    }
}
