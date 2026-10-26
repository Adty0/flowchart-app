<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FlowchartSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Nonaktifkan foreign key checks sementara untuk membersihkan data
        Schema::disableForeignKeyConstraints();
        FlowchartSubmission::truncate();
        User::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Akun Guru Utama
        $guru = User::create([
            'name' => 'Nur Aditya Romadhon S.Kom',
            'email' => 'guru@sekolah.id',
            'role' => 'guru',
            'class_name' => 'Guru Pengampu',
            'password' => Hash::make('password123'),
        ]);

        // Akun Guru sekunder untuk kemudahan login dengan nama
        User::create([
            'name' => 'Nur Aditya Romadhon S.Kom',
            'email' => 'nuraditya@sekolah.id',
            'role' => 'guru',
            'class_name' => 'Guru Pengampu',
            'password' => Hash::make('password123'),
        ]);

        // 2. Data 36 Siswa Kelas X TO 3
        $students = [
            ['nisn' => '0106572131', 'name' => 'RAFIANSYAH CIPTA PRANATA'],
            ['nisn' => '0109053073', 'name' => 'RAFIQI DWI SAPUTRA'],
            ['nisn' => '0102160972', 'name' => 'RAIHAN NUR RAHMAN'],
            ['nisn' => '0107837151', 'name' => 'RANGGA SAPUTRA'],
            ['nisn' => '0103998108', 'name' => 'RAVAEL DEWA FAHLEVI'],
            ['nisn' => '0107376882', 'name' => 'REHAN DWI OKTAVIAN'],
            ['nisn' => '0118540361', 'name' => 'RENO ADIAN SAPUTRA'],
            ['nisn' => '0103670451', 'name' => 'RIDWAN KURNIAWAN'],
            ['nisn' => '0106392063', 'name' => 'RIZKY EDY HIKMAWAN'],
            ['nisn' => '0106399619', 'name' => "SA'I FADLI"],
            ['nisn' => '0111405486', 'name' => 'SATRIA BAGUS NURROHMAN'],
            ['nisn' => '0107669333', 'name' => 'SATRIA GUTAMA'],
            ['nisn' => '0107397653', 'name' => 'SATYA NURIL TAQIYAN'],
            ['nisn' => '0101682196', 'name' => 'SHAH SULTAN ARIFIN'],
            ['nisn' => '0101223593', 'name' => 'TEGAR ABADY'],
            ['nisn' => '0101037491', 'name' => 'TEGAR GUSTI WARDANA'],
            ['nisn' => '0104999663', 'name' => 'TEXAS PUTRA WIJAYA'],
            ['nisn' => '0114072927', 'name' => 'WISNU BUDI SANTOSO'],
            ['nisn' => '0101294104', 'name' => 'YARDAN ATSAQIF'],
            ['nisn' => '0104490586', 'name' => 'YUSUF BINTANG PRATAMA'],
            ['nisn' => '0103213869', 'name' => 'YUSUF THORIQ EL FAROBI'],
            ['nisn' => '0116085711', 'name' => 'ZUAN ADITYA FIRMANSYAH'],
            ['nisn' => '0105535891', 'name' => 'ADDIN NOOR MAULANA'],
            ['nisn' => '0102018742', 'name' => 'ARYA BAGUS NUGROHO'],
            ['nisn' => '0108465352', 'name' => 'ARYA FAHREZA'],
            ['nisn' => '0101394467', 'name' => 'CAKRAWALA LINTANG SABAKHALILLAH'],
            ['nisn' => '0102727958', 'name' => 'EGIT MAHARDIKA'],
            ['nisn' => '0103349017', 'name' => 'ENGGAR WIBOWO MUKTI'],
            ['nisn' => '0104382559', 'name' => 'ERLINO DWI SAPUTRA'],
            ['nisn' => '0107878508', 'name' => 'FAHRI RAHMADANI'],
            ['nisn' => '0105540344', 'name' => 'HAFIZH MAULANA AHMAD'],
            ['nisn' => '0112054807', 'name' => 'ILHAM NUR YUWANA'],
            ['nisn' => '0108497094', 'name' => 'IQBAL RAFA SAPUTRA'],
            ['nisn' => '0107988726', 'name' => 'IRFANO DIKA PRATAMA'],
            ['nisn' => '0101764559', 'name' => 'LANJAR REZKY BAKTI AJI SAPUTRA'],
            ['nisn' => '0108854779', 'name' => 'NAUFAL LATIF CHAIRUL HAKAN'],
        ];

        $studentModels = [];
        foreach ($students as $st) {
            $studentModels[] = User::create([
                'name' => $st['name'],
                'email' => $st['nisn'] . '@sekolah.id',
                'role' => 'siswa',
                'class_name' => 'X TO 3',
                'password' => Hash::make('password123'),
            ]);
        }

        // Akun siswa alias umum untuk pengujian cepat
        User::create([
            'name' => 'RAFIANSYAH CIPTA PRANATA',
            'email' => 'siswa@sekolah.id',
            'role' => 'siswa',
            'class_name' => 'X TO 3',
            'password' => Hash::make('password123'),
        ]);

        // Contoh submission awal untuk siswa pertama kelas X TO 3
        if (!empty($studentModels[0])) {
            FlowchartSubmission::create([
                'user_id' => $studentModels[0]->id,
                'student_name' => $studentModels[0]->name,
                'class_name' => 'X TO 3',
                'case_key' => 'luas_segitiga',
                'diagram_data' => [
                    'nodes' => [
                        ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai']],
                        ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input alas & tinggi']],
                        ['id' => '3', 'type' => 'process', 'data' => ['label' => 'luas = 0.5 * alas * tinggi']],
                        ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak hasil luas']],
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
                    '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
                    '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
                    '✅ Rumus perhitungan matematika tepat.',
                    '✅ Simbol cetak hasil output sesuai.',
                    '✅ Seluruh garis panah konektor terhubung runtut.',
                ],
                'checked_at' => now()->subMinutes(10),
            ]);
        }
    }
}
