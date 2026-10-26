<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @verbatim
    <!--[if gte mso 9]>
    <xml>
        <x:ExcelWorkbook>
            <x:ExcelWorksheets>
                <x:ExcelWorksheet>
                    <x:Name>Rekap Nilai Siswa</x:Name>
                    <x:WorksheetOptions>
                        <x:DisplayGridlines/>
                        <x:Print>
                            <x:ValidPrinterInfo/>
                            <x:PaperSizeIndex>9</x:PaperSizeIndex>
                            <x:HorizontalResolution>600</x:HorizontalResolution>
                            <x:VerticalResolution>600</x:VerticalResolution>
                        </x:Print>
                    </x:WorksheetOptions>
                </x:ExcelWorksheet>
            </x:ExcelWorksheets>
        </x:ExcelWorkbook>
    </xml>
    <![endif]-->
    @endverbatim
    <style>
        body, table {
            font-family: 'Calibri', 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            color: #0f172a;
        }
        .title-main {
            font-size: 15pt;
            font-weight: bold;
            color: #1e3a8a;
            text-align: center;
        }
        .title-sub {
            font-size: 12pt;
            font-weight: bold;
            color: #334155;
            text-align: center;
        }
        .title-date {
            font-size: 10pt;
            color: #64748b;
            text-align: center;
            font-style: italic;
        }
        .meta-label {
            font-weight: bold;
            color: #1e293b;
            background-color: #f1f5f9;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }
        .meta-value {
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }
        .th-header {
            background-color: #1e293b;
            color: #ffffff;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000000;
            padding: 8px 6px;
        }
        .td-data {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .text-nisn {
            mso-number-format: "\@";
            text-align: center;
        }
        .text-score {
            text-align: center;
            font-weight: bold;
            font-size: 11pt;
        }
        .badge-tuntas {
            background-color: #dcfce7;
            color: #15803d;
            font-weight: bold;
            text-align: center;
        }
        .badge-remidial {
            background-color: #fee2e2;
            color: #b91c1c;
            font-weight: bold;
            text-align: center;
        }
        .badge-buka-remidi {
            background-color: #dbeafe;
            color: #1d4ed8;
            font-weight: bold;
            text-align: center;
        }
        .summary-title {
            background-color: #e2e8f0;
            font-weight: bold;
            border: 1px solid #94a3b8;
            padding: 6px;
        }
        .summary-val {
            font-weight: bold;
            text-align: center;
            border: 1px solid #94a3b8;
            padding: 6px;
        }
    </style>
</head>
<body>
    <table>
        <!-- KOP LAPORAN PENILAIAN -->
        <tr>
            <td colspan="9" class="title-main">LABORATORIUM FLOWCHART & PEMROGRAMAN DASAR</td>
        </tr>
        <tr>
            <td colspan="9" class="title-sub">DAFTAR REKAPITULASI HASIL PENILAIAN PRAKTIKUM SISWA</td>
        </tr>
        <tr>
            <td colspan="9" class="title-date">Waktu Unduh: {{ date('d F Y, H:i') }} WIB</td>
        </tr>
        <tr><td colspan="9"></td></tr>

        <!-- INFORMASI / METADATA KELAS & GURU -->
        <tr>
            <td class="meta-label">Guru Pengampu</td>
            <td colspan="3" class="meta-value"><strong>{{ $teacherName }}</strong></td>
            <td class="meta-label">Materi / Kasus</td>
            <td colspan="4" class="meta-value">{{ $caseStudyTitle }}</td>
        </tr>
        <tr>
            <td class="meta-label">Rombel / Kelas</td>
            <td colspan="3" class="meta-value"><strong>{{ $selectedClass ?: 'Semua Kelas (X TO 3)' }}</strong></td>
            <td class="meta-label">Kriteria Kelulusan (KKM)</td>
            <td colspan="4" class="meta-value"><strong>75 / 100</strong> (Nilai Minimal Tuntas)</td>
        </tr>
        <tr><td colspan="9"></td></tr>

        <!-- RINGKASAN STATISTIK KETUNTASAN -->
        <tr>
            <td colspan="2" class="summary-title">Total Siswa</td>
            <td colspan="2" class="summary-title">Tuntas (&ge; 75)</td>
            <td colspan="2" class="summary-title">Belum Tuntas / Remidial</td>
            <td class="summary-title">Rata-rata Nilai</td>
            <td colspan="2" class="summary-title">Persentase Ketuntasan</td>
        </tr>
        <tr>
            <td colspan="2" class="summary-val">{{ $totalSubmissions }} Siswa</td>
            <td colspan="2" class="summary-val" style="color: #15803d; background-color: #f0fdf4;">{{ $tuntasCount }} Siswa</td>
            <td colspan="2" class="summary-val" style="color: #b91c1c; background-color: #fef2f2;">{{ $remedialCount }} Siswa</td>
            <td class="summary-val">{{ $averageScore }}</td>
            <td colspan="2" class="summary-val" style="color: #1e3a8a; background-color: #eff6ff;">{{ $percentageTuntas }}%</td>
        </tr>
        <tr><td colspan="9"></td></tr>

        <!-- HEADER TABEL DATA NILAI SISWA -->
        <thead>
            <tr>
                <th class="th-header" style="width: 40px;">NO</th>
                <th class="th-header" style="width: 130px;">NISN</th>
                <th class="th-header" style="width: 250px;">NAMA SISWA</th>
                <th class="th-header" style="width: 100px;">KELAS</th>
                <th class="th-header" style="width: 160px;">STUDI KASUS</th>
                <th class="th-header" style="width: 80px;">SKOR</th>
                <th class="th-header" style="width: 140px;">STATUS</th>
                <th class="th-header" style="width: 380px;">CATATAN EVALUASI & FEEDBACK</th>
                <th class="th-header" style="width: 120px;">WAKTU</th>
            </tr>
        </thead>
        <tbody>
            @forelse($submissions as $index => $sub)
                @php
                    $isTuntas = $sub->score >= 75;
                    $statusClass = $isTuntas ? 'badge-tuntas' : ($sub->is_remedial_open ? 'badge-buka-remidi' : 'badge-remidial');
                    $statusText = $isTuntas ? 'TUNTAS' : ($sub->is_remedial_open ? 'REMIDI (DIBUKA)' : 'REMEDIAL');

                    // Bersihkan feedback
                    $feedbackText = '-';
                    if (is_array($sub->feedback)) {
                        $feedbackText = implode('; ', array_map(function($f) {
                            return preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $f);
                        }, $sub->feedback));
                    } elseif ($sub->feedback) {
                        $feedbackText = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', (string)$sub->feedback);
                    }

                    // Waktu pengerjaan
                    $timeFormatted = $sub->checked_at 
                        ? $sub->checked_at->format('d/m/Y H:i') 
                        : ($sub->created_at ? $sub->created_at->format('d/m/Y H:i') : '-');
                @endphp
                <tr>
                    <td class="td-data text-center">{{ $index + 1 }}</td>
                    <td class="td-data text-nisn">{{ $sub->calculated_nisn ?? '-' }}</td>
                    <td class="td-data text-left"><strong>{{ strtoupper($sub->student_name) }}</strong></td>
                    <td class="td-data text-center">{{ $sub->class_name ?: 'X TO 3' }}</td>
                    <td class="td-data text-left">{{ $sub->caseStudy ? $sub->caseStudy->title : 'Luas Segitiga' }}</td>
                    <td class="td-data text-score" style="{{ $isTuntas ? 'color: #15803d;' : 'color: #b91c1c;' }}">
                        {{ $sub->score }}
                    </td>
                    <td class="td-data {{ $statusClass }}">
                        {{ $statusText }}
                    </td>
                    <td class="td-data text-left" style="font-size: 10pt;">
                        {{ $feedbackText }}
                    </td>
                    <td class="td-data text-center" style="font-size: 10pt;">
                        {{ $timeFormatted }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="td-data text-center" style="padding: 20px; color: #94a3b8;">
                        Tidak ada data penilaian yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br /><br />
    <!-- LEMBAR TANDA TANGAN -->
    <table style="width: 100%;">
        <tr>
            <td colspan="6"></td>
            <td colspan="3" class="text-center" style="font-size: 11pt;">
                Kota Madiun, {{ date('d F Y') }}<br />
                Guru Mata Pelajaran Informatika / Pemrograman Dasar
                <br /><br /><br /><br /><br />
                <strong><u>{{ $teacherName }}</u></strong><br />
                NIP. -
            </td>
        </tr>
    </table>
</body>
</html>
