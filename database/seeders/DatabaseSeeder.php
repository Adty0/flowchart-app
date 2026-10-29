<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FlowchartSubmission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
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

        // Buat model user untuk 36 siswa
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

        // Akun Simulasi Siswa
        User::create([
            'name' => 'Simulasi Siswa',
            'email' => '000000@sekolah.id',
            'role' => 'siswa',
            'class_name' => 'Kelas Simulasi',
            'password' => Hash::make('password123'),
        ]);

        // 3. Template Diagram Flowchart Luas Segitiga
        $diagramSequential = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input alas & tinggi'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'process', 'data' => ['label' => 'luas = 0.5 * alas * tinggi'], 'position' => ['x' => 250, 'y' => 250]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak luas segitiga'], 'position' => ['x' => 250, 'y' => 350]],
                ['id' => '5', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 450]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4'],
                ['id' => 'e4-5', 'source' => '4', 'target' => '5'],
            ],
        ];

        // Diagram perbaikan Rangga Saputra (rumus kurang 0.5, panah tidak sampai selesai)
        $diagramRangga = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input alas & tinggi'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'process', 'data' => ['label' => 'luas = alas * tinggi'], 'position' => ['x' => 250, 'y' => 250]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'luas'], 'position' => ['x' => 250, 'y' => 350]],
                ['id' => '5', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 450]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4'],
            ],
        ];

        // Diagram perbaikan Texas Putra (hanya alas, rumus belum beres, belum ada Selesai)
        $diagramTexas = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input alas'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'process', 'data' => ['label' => 'luas = alas * tinggi'], 'position' => ['x' => 250, 'y' => 250]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'luas'], 'position' => ['x' => 250, 'y' => 350]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4'],
            ],
        ];

        // 4. Konfigurasi 36 Siswa (Semua Kasus Luas Segitiga, Jam 08.00 - 08.30)
        $submissionsConfig = [
            0 => [
                'score' => 100,
                'diagram' => $diagramSequential,
                'minute' => 2,
                'second' => 15,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
                    '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
                    '✅ Rumus perhitungan matematika tepat (luas = 0.5 * alas * tinggi).',
                    '✅ Simbol cetak hasil output sesuai.',
                    '✅ Seluruh garis panah konektor terhubung runtut.',
                ],
            ],
            1 => [
                'score' => 95,
                'diagram' => $diagramSequential,
                'minute' => 3,
                'second' => 40,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Variabel masukan alas dan tinggi terdeteksi.',
                    '✅ Rumus perhitungan luas segitiga tepat.',
                    '✅ Luaran luas tercetak dengan benar.',
                    '⚠️ Berikan spasi pada teks rumus matematika agar lebih mudah dibaca.',
                ],
            ],
            2 => [
                'score' => 85,
                'diagram' => $diagramSequential,
                'minute' => 4,
                'second' => 50,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Teks input alas dan tinggi terbaca.',
                    '✅ Rumus matematika luas segitiga sesuai.',
                    '⚠️ Lengkapi teks luaran dengan kata "Cetak" atau "Tampilkan".',
                    '✅ Alur konektor panah tersambung dengan baik.',
                ],
            ],
            3 => [ // RANGGA SAPUTRA - PERLU PERBAIKAN (BELUM TUNTAS)
                'score' => 65,
                'diagram' => $diagramRangga,
                'minute' => 5,
                'second' => 30,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai terpasang.',
                    '✅ Variabel masukan alas dan tinggi ada.',
                    '⚠️ Rumus perhitungan keliru (menulis alas * tinggi tanpa membagi 2).',
                    '❌ Garis alur dari proses belum terhubung ke simbol Selesai.',
                ],
            ],
            4 => [
                'score' => 90,
                'diagram' => $diagramSequential,
                'minute' => 6,
                'second' => 45,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Variabel masukan alas dan tinggi lengkap.',
                    '✅ Rumus luas = (alas * tinggi) / 2 tepat.',
                    '✅ Teks luaran hasil luas sesuai.',
                    '⚠️ Tata letak kotak proses perlu digeser sedikit ke tengah.',
                ],
            ],
            5 => [
                'score' => 80,
                'diagram' => $diagramSequential,
                'minute' => 7,
                'second' => 20,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas & tinggi terdeteksi.',
                    '✅ Rumus perhitungan luas tepat.',
                    '⚠️ Letak node luaran agak berdekatan dengan simbol proses.',
                    '✅ Alur panah terhubung ke Selesai.',
                ],
            ],
            6 => [
                'score' => 95,
                'diagram' => $diagramSequential,
                'minute' => 8,
                'second' => 35,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Rumus luas = 0.5 * alas * tinggi akurat.',
                    '✅ Simbol jajar genjang input dan output tepat.',
                    '✅ Garis alir terhubung sangat rapi.',
                ],
            ],
            7 => [
                'score' => 78,
                'diagram' => $diagramSequential,
                'minute' => 9,
                'second' => 50,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi ada.',
                    '⚠️ Penulisan rumus menggunakan tanda silang (x) bukan operator bintang (*).',
                    '✅ Alur flowchart tersambung dengan runtut.',
                ],
            ],
            8 => [
                'score' => 100,
                'diagram' => $diagramSequential,
                'minute' => 11,
                'second' => 10,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai sempurna.',
                    '✅ Teks input alas & tinggi terdeteksi presisi.',
                    '✅ Rumus luas segitiga sesuai standar.',
                    '✅ Output tercetak jelas.',
                    '✅ Diagram simetris dan terstruktur sempurna.',
                ],
            ],
            9 => [
                'score' => 88,
                'diagram' => $diagramSequential,
                'minute' => 12,
                'second' => 5,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Rumus 1/2 * alas * tinggi tepat.',
                    '⚠️ Gunakan tanda kurung pada pembagian (1/2).',
                    '✅ Alur konektor panah tersambung baik.',
                ],
            ],
            10 => [
                'score' => 92,
                'diagram' => $diagramSequential,
                'minute' => 13,
                'second' => 15,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Rumus perhitungan tepat.',
                    '✅ Hasil luaran tercetak dengan tepat.',
                    '⚠️ Simbol proses bisa diperlebar sedikit agar teks tidak mepet.',
                ],
            ],
            11 => [
                'score' => 82,
                'diagram' => $diagramSequential,
                'minute' => 14,
                'second' => 0,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi terdeteksi.',
                    '✅ Rumus luas valid.',
                    '⚠️ Teks luaran belum mencantumkan satuan luas.',
                    '✅ Alur terhubung ke Selesai.',
                ],
            ],
            12 => [
                'score' => 85,
                'diagram' => $diagramSequential,
                'minute' => 15,
                'second' => 10,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai terpasang.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Rumus luas = 0.5 * a * t tepat.',
                    '⚠️ Rapikan garis konektor agar tegak lurus dan tidak miring.',
                    '✅ Luaran tercetak jelas.',
                ],
            ],
            13 => [
                'score' => 100,
                'diagram' => $diagramSequential,
                'minute' => 16,
                'second' => 25,
                'feedback' => [
                    '✅ Simbol terminator tepat.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Perhitungan luas tepat dan rapi.',
                    '✅ Luaran tercetak jelas.',
                    '✅ Diagram simetris dan rapi.',
                ],
            ],
            14 => [
                'score' => 76,
                'diagram' => $diagramSequential,
                'minute' => 17,
                'second' => 30,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi terbaca.',
                    '⚠️ Teks proses terlalu ringkas, sertakan nama variabel (luas = ...).',
                    '✅ Seluruh simbol terhubung ke akhir alur.',
                ],
            ],
            15 => [
                'score' => 90,
                'diagram' => $diagramSequential,
                'minute' => 18,
                'second' => 40,
                'feedback' => [
                    '✅ Terminator Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi sesuai.',
                    '✅ Rumus luas segitiga valid.',
                    '✅ Output luas tampil terpisah jelas.',
                    '⚠️ Beri jarak vertikal antar node agar lebih proporsional.',
                ],
            ],
            16 => [ // TEXAS PUTRA WIJAYA - PERLU PERBAIKAN (BELUM TUNTAS)
                'score' => 60,
                'diagram' => $diagramTexas,
                'minute' => 19,
                'second' => 15,
                'feedback' => [
                    '✅ Simbol Mulai terpasang.',
                    '⚠️ Masukan hanya alas, variabel tinggi belum dimasukkan.',
                    '⚠️ Rumus perhitungan tidak lengkap (belum dikalikan 0.5).',
                    '❌ Belum tuntas: belum menambahkan simbol Selesai (Terminator akhir).',
                ],
            ],
            17 => [
                'score' => 98,
                'diagram' => $diagramSequential,
                'minute' => 20,
                'second' => 0,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Rumus matematika luas sangat presisi.',
                    '✅ Simbol output jajar genjang tepat.',
                    '✅ Diagram sangat rapi dan mudah dibaca.',
                ],
            ],
            18 => [
                'score' => 84,
                'diagram' => $diagramSequential,
                'minute' => 20,
                'second' => 50,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan alas dan tinggi terdeteksi.',
                    '✅ Logika perhitungan luas tepat.',
                    '⚠️ Gunakan simbol jajar genjang untuk input dan output data.',
                    '✅ Alur tersambung ke Selesai.',
                ],
            ],
            19 => [
                'score' => 92,
                'diagram' => $diagramSequential,
                'minute' => 21,
                'second' => 40,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Variabel alas dan tinggi terbaca.',
                    '✅ Rumus 0.5 * a * t valid.',
                    '✅ Output luas tampil tepat.',
                    '⚠️ Gunakan huruf kapital pada nama variabel agar konsisten.',
                ],
            ],
            20 => [
                'score' => 80,
                'diagram' => $diagramSequential,
                'minute' => 22,
                'second' => 25,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi terdeteksi.',
                    '✅ Proses perhitungan luas sesuai.',
                    '⚠️ Hubungkan langsung node keluaran ke simbol Selesai.',
                    '✅ Diagram runtut.',
                ],
            ],
            21 => [
                'score' => 90,
                'diagram' => $diagramSequential,
                'minute' => 23,
                'second' => 5,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Teks Input dan Proses valid.',
                    '✅ Teks Output valid.',
                    '✅ Alur terhubung runtut.',
                    '⚠️ Pastikan panah menunjuk tepat ke bagian atas simbol.',
                ],
            ],
            22 => [
                'score' => 88,
                'diagram' => $diagramSequential,
                'minute' => 23,
                'second' => 50,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi lengkap.',
                    '✅ Rumus luas = 0.5 * alas * tinggi tepat.',
                    '✅ Output tercetak jelas.',
                    '⚠️ Berikan jarak antar simbol agar tidak bertumpuk.',
                ],
            ],
            23 => [
                'score' => 95,
                'diagram' => $diagramSequential,
                'minute' => 24,
                'second' => 35,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Input alas dan tinggi terdeteksi.',
                    '✅ Rumus luas segitiga valid.',
                    '✅ Teks keluaran jelas dan sistematis.',
                    '✅ Alur diagram terstruktur baik.',
                ],
            ],
            24 => [
                'score' => 82,
                'diagram' => $diagramSequential,
                'minute' => 25,
                'second' => 15,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan alas & tinggi valid.',
                    '✅ Rumus perhitungan luas benar.',
                    '⚠️ Perjelas teks keluaran menjadi "Cetak Luas Segitiga".',
                    '✅ Panah konektor terhubung baik.',
                ],
            ],
            25 => [
                'score' => 100,
                'diagram' => $diagramSequential,
                'minute' => 25,
                'second' => 55,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan alas dan tinggi terverifikasi.',
                    '✅ Rumus perhitungan luas tepat dan akurat.',
                    '✅ Output luas segitiga sangat jelas.',
                    '✅ Diagram simetris dan rapi.',
                ],
            ],
            26 => [
                'score' => 78,
                'diagram' => $diagramSequential,
                'minute' => 26,
                'second' => 30,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi tersedia.',
                    '⚠️ Garis panah konektor agak berliku, rapikan posisi node.',
                    '✅ Logika perhitungan memenuhi kriteria tuntas.',
                ],
            ],
            27 => [
                'score' => 94,
                'diagram' => $diagramSequential,
                'minute' => 27,
                'second' => 0,
                'feedback' => [
                    '✅ Terminator Mulai dan Selesai terpasang rapi.',
                    '✅ Input alas & tinggi terdeteksi.',
                    '✅ Rumus luas segitiga tepat.',
                    '✅ Luaran tercetak jelas.',
                    '✅ Alur diagram sangat teratur.',
                ],
            ],
            28 => [
                'score' => 86,
                'diagram' => $diagramSequential,
                'minute' => 27,
                'second' => 35,
                'feedback' => [
                    '✅ Simbol terminator tepat.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Rumus luas segitiga sesuai.',
                    '✅ Garis alir terhubung runtut.',
                    '⚠️ Berikan spasi pada teks rumus matematika.',
                ],
            ],
            29 => [
                'score' => 80,
                'diagram' => $diagramSequential,
                'minute' => 28,
                'second' => 5,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan alas dan tinggi terdeteksi.',
                    '✅ Rumus luas segitiga tepat.',
                    '⚠️ Tambahkan label nama variabel luaran.',
                    '✅ Alur terhubung ke Selesai.',
                ],
            ],
            30 => [
                'score' => 96,
                'diagram' => $diagramSequential,
                'minute' => 28,
                'second' => 35,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi tepat.',
                    '✅ Perhitungan luas sangat jelas.',
                    '✅ Output terhubung sempurna ke Selesai.',
                    '✅ Diagram terstruktur sangat baik.',
                ],
            ],
            31 => [
                'score' => 82,
                'diagram' => $diagramSequential,
                'minute' => 29,
                'second' => 5,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan variabel alas & tinggi terdeteksi.',
                    '✅ Rumus matematika luas benar.',
                    '⚠️ Posisi kotak proses perlu digeser sedikit ke tengah agar sejajar.',
                    '✅ Alur terhubung.',
                ],
            ],
            32 => [
                'score' => 90,
                'diagram' => $diagramSequential,
                'minute' => 29,
                'second' => 25,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Logika perhitungan luas segitiga benar.',
                    '✅ Tampilan output luas sesuai.',
                    '⚠️ Garis konektor sedikit melengkung.',
                ],
            ],
            33 => [
                'score' => 88,
                'diagram' => $diagramSequential,
                'minute' => 29,
                'second' => 45,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input alas dan tinggi terdeteksi.',
                    '✅ Perhitungan luas tepat.',
                    '✅ Diagram runtut dan rapi.',
                    '⚠️ Beri jarak vertikal antar simbol lebih renggang.',
                ],
            ],
            34 => [
                'score' => 85,
                'diagram' => $diagramSequential,
                'minute' => 29,
                'second' => 55,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai terpasang.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Rumus luas segitiga tepat.',
                    '⚠️ Teks output bisa disederhanakan agar lebih ringkas.',
                    '✅ Garis alir terhubung baik.',
                ],
            ],
            35 => [
                'score' => 100,
                'diagram' => $diagramSequential,
                'minute' => 30,
                'second' => 0,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
                    '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
                    '✅ Rumus perhitungan matematika tepat.',
                    '✅ Simbol cetak hasil output sesuai.',
                    '✅ Seluruh garis panah konektor terhubung runtut.',
                ],
            ],
        ];

        $today = Carbon::today();

        foreach ($studentModels as $index => $studentUser) {
            if (isset($submissionsConfig[$index])) {
                $cfg = $submissionsConfig[$index];
                $time = $today->copy()->setTime(8, $cfg['minute'], $cfg['second']);

                FlowchartSubmission::create([
                    'user_id' => $studentUser->id,
                    'student_name' => $studentUser->name,
                    'class_name' => 'X TO 3',
                    'case_key' => 'luas_segitiga',
                    'diagram_data' => $cfg['diagram'],
                    'score' => $cfg['score'],
                    'feedback' => $cfg['feedback'],
                    'checked_at' => $time,
                    'created_at' => $time,
                    'updated_at' => $time,
                ]);
            }
        }
    }
}
