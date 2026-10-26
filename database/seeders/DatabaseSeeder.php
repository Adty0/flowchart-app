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

        // 3. Template Diagram Flowchart
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

        $diagramKelulusan = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input nilai siswa'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'decision', 'data' => ['label' => 'nilai >= 75 ?'], 'position' => ['x' => 250, 'y' => 260]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak "LULUS"'], 'position' => ['x' => 120, 'y' => 380]],
                ['id' => '5', 'type' => 'io', 'data' => ['label' => 'Cetak "REMEDIAL"'], 'position' => ['x' => 380, 'y' => 380]],
                ['id' => '6', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 500]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4', 'label' => 'Ya'],
                ['id' => 'e3-5', 'source' => '3', 'target' => '5', 'label' => 'Tidak'],
                ['id' => 'e4-6', 'source' => '4', 'target' => '6'],
                ['id' => 'e5-6', 'source' => '5', 'target' => '6'],
            ],
        ];

        $diagramGanjilGenap = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input bilangan bulat'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'decision', 'data' => ['label' => 'bilangan % 2 == 0 ?'], 'position' => ['x' => 250, 'y' => 260]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak "Genap"'], 'position' => ['x' => 120, 'y' => 380]],
                ['id' => '5', 'type' => 'io', 'data' => ['label' => 'Cetak "Ganjil"'], 'position' => ['x' => 380, 'y' => 380]],
                ['id' => '6', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 500]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4', 'label' => 'Ya'],
                ['id' => 'e3-5', 'source' => '3', 'target' => '5', 'label' => 'Tidak'],
                ['id' => 'e4-6', 'source' => '4', 'target' => '6'],
                ['id' => 'e5-6', 'source' => '5', 'target' => '6'],
            ],
        ];

        $diagramDiskon = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input total belanja'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'decision', 'data' => ['label' => 'belanja >= 100000 ?'], 'position' => ['x' => 250, 'y' => 260]],
                ['id' => '4', 'type' => 'process', 'data' => ['label' => 'diskon = total * 0.10'], 'position' => ['x' => 120, 'y' => 380]],
                ['id' => '5', 'type' => 'process', 'data' => ['label' => 'diskon = 0'], 'position' => ['x' => 380, 'y' => 380]],
                ['id' => '6', 'type' => 'io', 'data' => ['label' => 'Cetak total bayar'], 'position' => ['x' => 250, 'y' => 480]],
                ['id' => '7', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 580]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4', 'label' => 'Ya'],
                ['id' => 'e3-5', 'source' => '3', 'target' => '5', 'label' => 'Tidak'],
                ['id' => 'e4-6', 'source' => '4', 'target' => '6'],
                ['id' => 'e5-6', 'source' => '5', 'target' => '6'],
                ['id' => 'e6-7', 'source' => '6', 'target' => '7'],
            ],
        ];

        $diagramSuhu = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input suhu Celcius'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'process', 'data' => ['label' => 'F = (9/5 * C) + 32'], 'position' => ['x' => 250, 'y' => 250]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak suhu Fahrenheit'], 'position' => ['x' => 250, 'y' => 350]],
                ['id' => '5', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 450]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4'],
                ['id' => 'e4-5', 'source' => '4', 'target' => '5'],
            ],
        ];

        // Diagram dengan kekurangan (Rangga Saputra: cabang Tidak buntu)
        $diagramPerbaikanKelulusan = [
            'nodes' => [
                ['id' => '1', 'type' => 'terminator', 'data' => ['label' => 'Mulai'], 'position' => ['x' => 250, 'y' => 50]],
                ['id' => '2', 'type' => 'io', 'data' => ['label' => 'Input nilai siswa'], 'position' => ['x' => 250, 'y' => 150]],
                ['id' => '3', 'type' => 'decision', 'data' => ['label' => 'apakah lulus ?'], 'position' => ['x' => 250, 'y' => 260]],
                ['id' => '4', 'type' => 'io', 'data' => ['label' => 'Cetak Lulus'], 'position' => ['x' => 120, 'y' => 380]],
                ['id' => '5', 'type' => 'io', 'data' => ['label' => 'Remedial'], 'position' => ['x' => 380, 'y' => 380]],
                ['id' => '6', 'type' => 'terminator', 'data' => ['label' => 'Selesai'], 'position' => ['x' => 250, 'y' => 500]],
            ],
            'edges' => [
                ['id' => 'e1-2', 'source' => '1', 'target' => '2'],
                ['id' => 'e2-3', 'source' => '2', 'target' => '3'],
                ['id' => 'e3-4', 'source' => '3', 'target' => '4', 'label' => 'Ya'],
                ['id' => 'e4-6', 'source' => '4', 'target' => '6'],
            ],
        ];

        // Diagram dengan kekurangan (Texas Putra: Selesai belum ada)
        $diagramPerbaikanSegitiga = [
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

        // 4. Data Konfigurasi Submission Seluruh 36 Siswa (34 Tuntas, 2 Perlu Perbaikan => 94.4% ~ 95% Tuntas)
        $submissionsConfig = [
            0 => [
                'case_key' => 'luas_segitiga',
                'score' => 100,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
                    '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
                    '✅ Rumus perhitungan matematika tepat (0.5 * alas * tinggi).',
                    '✅ Simbol cetak hasil output sesuai.',
                    '✅ Seluruh garis panah konektor terhubung runtut.',
                ],
                'minutes' => 15,
            ],
            1 => [
                'case_key' => 'kelulusan',
                'score' => 95,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai siswa terdeteksi.',
                    '✅ Logika percabangan kondisi kelulusan (>= 75) tepat.',
                    '✅ Simbol keputusan memiliki 2 cabang keluar (Ya / Tidak).',
                    '⚠️ Berikan teks label cabang lebih kontras pada konektor.',
                ],
                'minutes' => 25,
            ],
            2 => [
                'case_key' => 'luas_segitiga',
                'score' => 85,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Teks input alas dan tinggi terbaca.',
                    '✅ Rumus matematika luas segitiga sesuai.',
                    '⚠️ Lengkapi teks luaran dengan kata "Cetak" atau "Tampilkan".',
                    '✅ Alur konektor panah tersambung dengan baik.',
                ],
                'minutes' => 35,
            ],
            3 => [ // RANGGA SAPUTRA - PERLU PERBAIKAN (BELUM TUNTAS)
                'case_key' => 'kelulusan',
                'score' => 65,
                'diagram' => $diagramPerbaikanKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '⚠️ Syarat kondisi pada belah ketupat belum spesifik (belum ada nilai batas KKM 75).',
                    '⚠️ Garis alir cabang Remedial ("Tidak") belum terhubung ke Selesai.',
                    '❌ Alur belum tuntas, lengkapi sambungan cabang keputusan.',
                ],
                'minutes' => 42,
            ],
            4 => [
                'case_key' => 'konversi_suhu',
                'score' => 90,
                'diagram' => $diagramSuhu,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Variabel masukan suhu Celcius terdeteksi.',
                    '✅ Rumus konversi suhu Fahrenheit tepat.',
                    '✅ Teks luaran hasil konversi sesuai.',
                    '⚠️ Tata letak node proses perlu digeser sedikit ke tengah.',
                ],
                'minutes' => 48,
            ],
            5 => [
                'case_key' => 'ganjil_genap',
                'score' => 80,
                'diagram' => $diagramGanjilGenap,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan bilangan terdeteksi.',
                    '✅ Logika modulus (bilangan % 2 == 0) tepat.',
                    '⚠️ Tata letak node keluaran genap sedikit berdekatan dengan cabang.',
                    '✅ Alur terhubung ke Selesai.',
                ],
                'minutes' => 55,
            ],
            6 => [
                'case_key' => 'diskon_belanja',
                'score' => 95,
                'diagram' => $diagramDiskon,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan total belanja terdeteksi.',
                    '✅ Logika percabangan diskon (belanja >= 100000) tepat.',
                    '✅ Perhitungan diskon 10% dan total bayar akurat.',
                    '✅ Garis alir terhubung rapi.',
                ],
                'minutes' => 62,
            ],
            7 => [
                'case_key' => 'luas_segitiga',
                'score' => 78,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi ada.',
                    '⚠️ Penulisan rumus menggunakan tanda x bukan operator bintang (*).',
                    '✅ Alur flowchart tersambung dengan runtut.',
                ],
                'minutes' => 70,
            ],
            8 => [
                'case_key' => 'kelulusan',
                'score' => 100,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai sempurna.',
                    '✅ Teks input, keputusan cabang Ya dan Tidak valid.',
                    '✅ Kedua cabang terhubung kembali ke Selesai.',
                    '✅ Diagram rapi, proporsional, dan terstruktur sempurna.',
                ],
                'minutes' => 78,
            ],
            9 => [
                'case_key' => 'ganjil_genap',
                'score' => 88,
                'diagram' => $diagramGanjilGenap,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan angka terdeteksi.',
                    '✅ Percabangan ganjil/genap bekerja baik.',
                    '⚠️ Tambahkan teks label cabang "Ya" dan "Tidak" pada garis panah.',
                ],
                'minutes' => 85,
            ],
            10 => [
                'case_key' => 'luas_segitiga',
                'score' => 92,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan alas dan tinggi valid.',
                    '✅ Rumus perhitungan tepat.',
                    '✅ Hasil luaran tercetak dengan tepat.',
                    '⚠️ Simbol proses bisa diperlebar sedikit agar teks tidak mepet.',
                ],
                'minutes' => 92,
            ],
            11 => [
                'case_key' => 'diskon_belanja',
                'score' => 82,
                'diagram' => $diagramDiskon,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input total transaksi terdeteksi.',
                    '✅ Cabang diskon benar.',
                    '⚠️ Output tanpa diskon belum mencantumkan nominal harga akhir.',
                    '✅ Alur terhubung ke Selesai.',
                ],
                'minutes' => 98,
            ],
            12 => [
                'case_key' => 'konversi_suhu',
                'score' => 85,
                'diagram' => $diagramSuhu,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai terpasang.',
                    '✅ Masukan suhu valid.',
                    '✅ Rumus (9/5 * C + 32) sudah tepat.',
                    '⚠️ Rapikan garis konektor agar tidak bersilangan dengan kotak teks.',
                    '✅ Luaran tercetak jelas.',
                ],
                'minutes' => 105,
            ],
            13 => [
                'case_key' => 'luas_segitiga',
                'score' => 100,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol terminator tepat.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Perhitungan luas tepat dan rapi.',
                    '✅ Luaran tercetak jelas.',
                    '✅ Diagram simetris dan rapi.',
                ],
                'minutes' => 112,
            ],
            14 => [
                'case_key' => 'kelulusan',
                'score' => 76,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai terbaca.',
                    '⚠️ Keterangan kondisi pada belah ketupat terlalu singkat (hanya menulis "> 75").',
                    '✅ Dua cabang keputusan tersambung ke akhir alur.',
                ],
                'minutes' => 120,
            ],
            15 => [
                'case_key' => 'ganjil_genap',
                'score' => 90,
                'diagram' => $diagramGanjilGenap,
                'feedback' => [
                    '✅ Terminator Mulai dan Selesai lengkap.',
                    '✅ Masukan bilangan sesuai.',
                    '✅ Evaluasi sisa bagi modulus valid.',
                    '✅ Output Ganjil & Genap terpisah jelas.',
                    '⚠️ Beri jarak antar node output.',
                ],
                'minutes' => 128,
            ],
            16 => [ // TEXAS PUTRA WIJAYA - PERLU PERBAIKAN (BELUM TUNTAS)
                'case_key' => 'luas_segitiga',
                'score' => 60,
                'diagram' => $diagramPerbaikanSegitiga,
                'feedback' => [
                    '✅ Simbol Mulai terpasang.',
                    '⚠️ Masukan hanya alas, variabel tinggi belum dimasukkan.',
                    '⚠️ Rumus perhitungan tidak lengkap (belum dikalikan 0.5).',
                    '❌ Belum tuntas: belum menambahkan simbol Selesai (Terminator akhir).',
                ],
                'minutes' => 135,
            ],
            17 => [
                'case_key' => 'kelulusan',
                'score' => 98,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan nilai ujian lengkap.',
                    '✅ Logika percabangan kelulusan sangat presisi.',
                    '✅ Dua luaran cabang Ya dan Tidak terstruktur rapi.',
                    '✅ Diagram sangat rapi dan mudah dibaca.',
                ],
                'minutes' => 142,
            ],
            18 => [
                'case_key' => 'diskon_belanja',
                'score' => 84,
                'diagram' => $diagramDiskon,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan total bayar terdeteksi.',
                    '✅ Logika diskon tepat.',
                    '⚠️ Gunakan simbol jajar genjang untuk input dan output data.',
                    '✅ Alur tersambung ke Selesai.',
                ],
                'minutes' => 150,
            ],
            19 => [
                'case_key' => 'luas_segitiga',
                'score' => 92,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Variabel alas dan tinggi terbaca.',
                    '✅ Rumus 0.5 * a * t valid.',
                    '✅ Output luas tampil tepat.',
                    '⚠️ Gunakan huruf kapital pada nama variabel agar konsisten.',
                ],
                'minutes' => 158,
            ],
            20 => [
                'case_key' => 'konversi_suhu',
                'score' => 80,
                'diagram' => $diagramSuhu,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input suhu Celcius terdeteksi.',
                    '✅ Proses perhitungan matematika sesuai.',
                    '⚠️ Hubungkan langsung node keluaran ke simbol Selesai.',
                    '✅ Diagram runtut.',
                ],
                'minutes' => 165,
            ],
            21 => [
                'case_key' => 'luas_segitiga',
                'score' => 90,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Teks Input dan Proses valid.',
                    '✅ Teks Output valid.',
                    '✅ Alur terhubung runtut.',
                    '⚠️ Pastikan panah menunjuk tepat ke bagian atas simbol.',
                ],
                'minutes' => 172,
            ],
            22 => [
                'case_key' => 'kelulusan',
                'score' => 88,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai siswa masuk.',
                    '✅ Logika >= 75 tepat.',
                    '✅ Output Lulus dan Remedial tersambung.',
                    '⚠️ Tambahkan teks label cabang pada garis alur.',
                ],
                'minutes' => 180,
            ],
            23 => [
                'case_key' => 'ganjil_genap',
                'score' => 95,
                'diagram' => $diagramGanjilGenap,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Input bilangan terdeteksi.',
                    '✅ Percabangan modulus valid.',
                    '✅ Teks keluaran jelas dan sistematis.',
                    '✅ Alur diagram terstruktur baik.',
                ],
                'minutes' => 188,
            ],
            24 => [
                'case_key' => 'luas_segitiga',
                'score' => 82,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan alas & tinggi valid.',
                    '✅ Rumus perhitungan benar.',
                    '⚠️ Perjelas teks keluaran menjadi "Cetak Luas Segitiga".',
                    '✅ Panah konektor terhubung baik.',
                ],
                'minutes' => 195,
            ],
            25 => [
                'case_key' => 'diskon_belanja',
                'score' => 100,
                'diagram' => $diagramDiskon,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan total belanja terverifikasi.',
                    '✅ Percabangan diskon 10% tepat.',
                    '✅ Output potongan dan total bayar sangat jelas.',
                    '✅ Diagram simetris dan rapi.',
                ],
                'minutes' => 202,
            ],
            26 => [
                'case_key' => 'kelulusan',
                'score' => 78,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai siswa tersedia.',
                    '⚠️ Garis panah cabang Remedial agak berliku, rapikan posisi node.',
                    '✅ Logika percabangan memenuhi kriteria tuntas.',
                ],
                'minutes' => 210,
            ],
            27 => [
                'case_key' => 'konversi_suhu',
                'score' => 94,
                'diagram' => $diagramSuhu,
                'feedback' => [
                    '✅ Terminator Mulai dan Selesai terpasang rapi.',
                    '✅ Input Celcius terdeteksi.',
                    '✅ Rumus Fahrenheit tepat.',
                    '✅ Luaran tercetak jelas.',
                    '✅ Alur diagram sangat teratur.',
                ],
                'minutes' => 218,
            ],
            28 => [
                'case_key' => 'luas_segitiga',
                'score' => 86,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol terminator tepat.',
                    '✅ Masukan alas dan tinggi lengkap.',
                    '✅ Rumus luas segitiga sesuai.',
                    '✅ Garis alir terhubung runtut.',
                    '⚠️ Berikan spasi pada teks rumus matematika.',
                ],
                'minutes' => 225,
            ],
            29 => [
                'case_key' => 'ganjil_genap',
                'score' => 80,
                'diagram' => $diagramGanjilGenap,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai ada.',
                    '✅ Masukan bilangan terdeteksi.',
                    '✅ Logika penentuan ganjil genap tepat.',
                    '⚠️ Tambahkan teks label "Ya" dan "Tidak" pada cabang panah.',
                    '✅ Alur terhubung ke Selesai.',
                ],
                'minutes' => 232,
            ],
            30 => [
                'case_key' => 'kelulusan',
                'score' => 96,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai ujian tepat.',
                    '✅ Keputusan kelulusan sangat jelas.',
                    '✅ Output terhubung sempurna ke Selesai.',
                    '✅ Diagram terstruktur sangat baik.',
                ],
                'minutes' => 240,
            ],
            31 => [
                'case_key' => 'luas_segitiga',
                'score' => 82,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Masukan variabel terdeteksi.',
                    '✅ Rumus matematika luas benar.',
                    '⚠️ Posisi kotak proses perlu digeser sedikit ke tengah agar sejajar.',
                    '✅ Alur terhubung.',
                ],
                'minutes' => 248,
            ],
            32 => [
                'case_key' => 'diskon_belanja',
                'score' => 90,
                'diagram' => $diagramDiskon,
                'feedback' => [
                    '✅ Simbol terminator lengkap.',
                    '✅ Masukan total transaksi valid.',
                    '✅ Logika perhitungan diskon benar.',
                    '✅ Tampilan output potongan dan total bayar sesuai.',
                    '⚠️ Garis konektor sedikit melengkung.',
                ],
                'minutes' => 255,
            ],
            33 => [
                'case_key' => 'kelulusan',
                'score' => 88,
                'diagram' => $diagramKelulusan,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai lengkap.',
                    '✅ Input nilai siswa terdeteksi.',
                    '✅ Percabangan Lulus/Remedial tepat.',
                    '✅ Diagram runtut dan rapi.',
                    '⚠️ Beri jarak vertikal antar simbol lebih renggang.',
                ],
                'minutes' => 262,
            ],
            34 => [
                'case_key' => 'konversi_suhu',
                'score' => 85,
                'diagram' => $diagramSuhu,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai terpasang.',
                    '✅ Masukan suhu Celcius valid.',
                    '✅ Rumus konversi suhu tepat.',
                    '⚠️ Teks output bisa disederhanakan agar lebih ringkas.',
                    '✅ Garis alir terhubung baik.',
                ],
                'minutes' => 270,
            ],
            35 => [
                'case_key' => 'luas_segitiga',
                'score' => 100,
                'diagram' => $diagramSequential,
                'feedback' => [
                    '✅ Simbol Mulai dan Selesai (Terminator) lengkap.',
                    '✅ Variabel masukan (alas, tinggi) sesuai instruksi.',
                    '✅ Rumus perhitungan matematika tepat.',
                    '✅ Simbol cetak hasil output sesuai.',
                    '✅ Seluruh garis panah konektor terhubung runtut.',
                ],
                'minutes' => 278,
            ],
        ];

        foreach ($studentModels as $index => $studentUser) {
            if (isset($submissionsConfig[$index])) {
                $cfg = $submissionsConfig[$index];
                FlowchartSubmission::create([
                    'user_id' => $studentUser->id,
                    'student_name' => $studentUser->name,
                    'class_name' => 'X TO 3',
                    'case_key' => $cfg['case_key'],
                    'diagram_data' => $cfg['diagram'],
                    'score' => $cfg['score'],
                    'feedback' => $cfg['feedback'],
                    'checked_at' => now()->subMinutes($cfg['minutes']),
                    'created_at' => now()->subMinutes($cfg['minutes']),
                    'updated_at' => now()->subMinutes($cfg['minutes']),
                ]);
            }
        }
    }
}
