<?php

namespace App\Services;

use ZipArchive;

class XlsxGenerator
{
    /**
     * Menghasilkan file Microsoft Excel (.xlsx) murni berstandar OpenXML
     * Menggunakan PHP ZipArchive tanpa dependensi library eksternal.
     *
     * @param string $title Judul dokumen
     * @param array $meta Informasi metadata (Guru, Kelas, Waktu, Statistik)
     * @param array $headers Kolom header tabel
     * @param array $rows Baris-baris data siswa
     * @return string Konten biner file .xlsx
     */
    public static function generate(string $title, array $meta, array $headers, array $rows): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx_');
        $zip = new ZipArchive();
        $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 1. [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // 2. _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // 3. xl/_rels/workbook.xml.rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
            '</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);

        // 4. xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Rekap Nilai" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // 5. xl/styles.xml
        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="6">' .
            '<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>' .             // 0: Normal
            '<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>' .          // 1: Bold White
            '<font><b/><sz val="14"/><color rgb="FF065F46"/><name val="Calibri"/></font>' .          // 2: Title Emerald
            '<font><b/><sz val="11"/><color rgb="FF1E293B"/><name val="Calibri"/></font>' .          // 3: Subtitle
            '<font><b/><sz val="10"/><color rgb="FF047857"/><name val="Calibri"/></font>' .          // 4: Green status
            '<font><b/><sz val="10"/><color rgb="FFB91C1C"/><name val="Calibri"/></font>' .          // 5: Red status
            '</fonts>' .
            '<fills count="5">' .
            '<fill><patternFill patternType="none"/></fill>' .
            '<fill><patternFill patternType="gray125"/></fill>' .
            '<fill><patternFill patternType="solid"><fgColor rgb="FF059669"/></patternFill></fill>' . // 2: Header Emerald
            '<fill><patternFill patternType="solid"><fgColor rgb="FFF1F5F9"/></patternFill></fill>' . // 3: Slate light
            '<fill><patternFill patternType="solid"><fgColor rgb="FFECFDF5"/></patternFill></fill>' . // 4: Mint light
            '</fills>' .
            '<borders count="3">' .
            '<border><left/><right/><top/><bottom/></border>' .                                        // 0: None
            '<border>' .                                                                               // 1: Thin Gray
            '<left style="thin"><color rgb="FFCBD5E1"/></left>' .
            '<right style="thin"><color rgb="FFCBD5E1"/></right>' .
            '<top style="thin"><color rgb="FFCBD5E1"/></top>' .
            '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>' .
            '</border>' .
            '<border>' .                                                                               // 2: Header Border
            '<left style="thin"><color rgb="FF047857"/></left>' .
            '<right style="thin"><color rgb="FF047857"/></right>' .
            '<top style="thin"><color rgb="FF047857"/></top>' .
            '<bottom style="medium"><color rgb="FF065F46"/></bottom>' .
            '</border>' .
            '</borders>' .
            '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
            '<cellXfs count="9">' .
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>' .                          // 0: Normal
            '<xf numFmtId="0" fontId="1" fillId="2" borderId="2" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 1: Table Header
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>' .          // 2: Data Left
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 3: Data Center (No, NISN)
            '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>' .             // 4: Title
            '<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>' .             // 5: Subtitle
            '<xf numFmtId="0" fontId="3" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"/>' . // 6: Meta Card
            '<xf numFmtId="0" fontId="4" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 7: Status TUNTAS
            '<xf numFmtId="0" fontId="5" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>' . // 8: Status REMEDIAL
            '</cellXfs>' .
            '</styleSheet>';
        $zip->addFromString('xl/styles.xml', $styles);

        // 6. xl/worksheets/sheet1.xml
        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
            '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<cols>' .
            '<col min="1" max="1" width="6" customWidth="1"/>' .
            '<col min="2" max="2" width="16" customWidth="1"/>' .
            '<col min="3" max="3" width="36" customWidth="1"/>' .
            '<col min="4" max="4" width="14" customWidth="1"/>' .
            '<col min="5" max="5" width="28" customWidth="1"/>' .
            '<col min="6" max="6" width="10" customWidth="1"/>' .
            '<col min="7" max="7" width="18" customWidth="1"/>' .
            '<col min="8" max="8" width="65" customWidth="1"/>' .
            '<col min="9" max="9" width="18" customWidth="1"/>' .
            '</cols>' .
            '<sheetData>';

        $colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

        $rowNum = 1;

        // Title
        $sheetXml .= '<row r="' . $rowNum . '">' .
            '<c r="A' . $rowNum . '" s="4" t="inlineStr"><is><t>' . htmlspecialchars($title, ENT_XML1, 'UTF-8') . '</t></is></c>' .
            '</row>';
        $rowNum++;

        // Subtitle
        $sub = 'DAFTAR REKAPITULASI HASIL PENILAIAN PRAKTIKUM FLOWCHART';
        $sheetXml .= '<row r="' . $rowNum . '">' .
            '<c r="A' . $rowNum . '" s="5" t="inlineStr"><is><t>' . $sub . '</t></is></c>' .
            '</row>';
        $rowNum += 2;

        // Metadata Info
        foreach ($meta as $label => $val) {
            $sheetXml .= '<row r="' . $rowNum . '">' .
                '<c r="A' . $rowNum . '" s="6" t="inlineStr"><is><t>' . htmlspecialchars($label, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '<c r="B' . $rowNum . '" s="2" t="inlineStr"><is><t>' . htmlspecialchars((string)$val, ENT_XML1, 'UTF-8') . '</t></is></c>' .
                '</row>';
            $rowNum++;
        }
        $rowNum++;

        // Table Header
        $sheetXml .= '<row r="' . $rowNum . '">';
        foreach ($headers as $cIdx => $hText) {
            $colLetter = $colLetters[$cIdx] ?? 'A';
            $sheetXml .= '<c r="' . $colLetter . $rowNum . '" s="1" t="inlineStr"><is><t>' . htmlspecialchars($hText, ENT_XML1, 'UTF-8') . '</t></is></c>';
        }
        $sheetXml .= '</row>';
        $rowNum++;

        // Rows
        foreach ($rows as $row) {
            $sheetXml .= '<row r="' . $rowNum . '">';
            foreach ($row as $cIdx => $val) {
                $colLetter = $colLetters[$cIdx] ?? 'A';
                $strVal = (string)$val;
                $cleanVal = htmlspecialchars($strVal, ENT_XML1, 'UTF-8');

                // Style selection
                // Default: Data Left (2)
                $style = '2';
                if ($cIdx === 0 || $cIdx === 1 || $cIdx === 3 || $cIdx === 5 || $cIdx === 8) {
                    // No, NISN, Kelas, Skor, Waktu -> Center
                    $style = '3';
                } elseif ($cIdx === 6) {
                    // Status
                    if (str_contains($strVal, 'TUNTAS')) {
                        $style = '7'; // Green badge
                    } else {
                        $style = '8'; // Red badge
                    }
                }

                // Important: t="inlineStr" ensures leading zeroes in NISN are 100% preserved!
                $sheetXml .= '<c r="' . $colLetter . $rowNum . '" s="' . $style . '" t="inlineStr"><is><t>' . $cleanVal . '</t></is></c>';
            }
            $sheetXml .= '</row>';
            $rowNum++;
        }

        // Tanda tangan di bagian bawah
        $rowNum += 2;
        $sheetXml .= '<row r="' . $rowNum . '"><c r="G' . $rowNum . '" t="inlineStr"><is><t>Kota Madiun, ' . date('d F Y') . '</t></is></c></row>';
        $rowNum++;
        $sheetXml .= '<row r="' . $rowNum . '"><c r="G' . $rowNum . '" t="inlineStr"><is><t>Guru Mata Pelajaran Informatika</t></is></c></row>';
        $rowNum += 3;
        $teacher = $meta['Guru Pengampu'] ?? 'Nur Aditya Romadhon S.Kom';
        $sheetXml .= '<row r="' . $rowNum . '"><c r="G' . $rowNum . '" s="5" t="inlineStr"><is><t>' . htmlspecialchars($teacher, ENT_XML1, 'UTF-8') . '</t></is></c></row>';

        $sheetXml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);

        $zip->close();
        $content = file_get_contents($tempFile);
        @unlink($tempFile);
        return $content;
    }
}

