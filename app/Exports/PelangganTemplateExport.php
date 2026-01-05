<?php

namespace App\Exports;

use App\Models\Area;
use App\Models\Paket;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\NamedRange;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class PelangganTemplateExport implements FromArray, WithHeadings, WithEvents
{
    public function headings(): array
    {
        return [
            'nomor_buku',          // ✅ BARU (sebelum nama)
            'nama',
            'nomor_hp',
            'nik',
            'alamat',
            'ip_address',
            'tanggal_registrasi',
            'paket_layanan',
            'area',
            'sales',
            'status_pelanggan',
        ];
    }

    public function array(): array
    {
        return [[
            'BK-001',              // ✅ BARU contoh nomor buku (boleh kosong kalau mau)
            'Moh Bahlul',
            '081234567890',
            '1234567890123445',
            'Bedali Rt.36',
            '1.1.1.1',
            date('Y-m-d'),
            '', // paket_layanan (dropdown)
            '', // area (dropdown)
            '', // sales (dependent dropdown by area)
            'aktif', // status (dropdown)
        ]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate(); /** @var Worksheet $sheet */
                $spreadsheet = $sheet->getParent();

                // ===== Ambil master data =====
                $pakets = Paket::orderBy('nama_paket')->pluck('nama_paket')->values()->toArray();
                $areas  = Area::orderBy('nama_area')->get(['id_area', 'nama_area']);

                $salesByAreaId = $this->getSalesByAreaId(); // [id_area => [email1,email2...]]
                $status = ['aktif', 'isolir', 'berhenti'];

                // ===== Buat / ambil sheet "master" =====
                $master = $spreadsheet->getSheetByName('master');
                if (!$master) {
                    $master = new Worksheet($spreadsheet, 'master');
                    $spreadsheet->addSheet($master);
                }
                $master->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                // ===== Isi master: kolom A = area name =====
                $master->setCellValue('A1', 'AREA_LIST');
                $r = 2;
                foreach ($areas as $a) {
                    $master->setCellValue("A{$r}", $a->nama_area);
                    $r++;
                }
                $areaCount = max(1, $areas->count());
                $areaListRange = "master!\$A\$2:\$A\$" . (1 + $areaCount);

                // ===== Isi master paket list: kolom B =====
                $master->setCellValue('B1', 'PAKET_LIST');
                $r = 2;
                foreach ($pakets as $p) {
                    $master->setCellValue("B{$r}", $p);
                    $r++;
                }
                $paketCount = max(1, count($pakets));
                $paketListRange = "master!\$B\$2:\$B\$" . (1 + $paketCount);

                // ===== Isi master status list: kolom C =====
                $master->setCellValue('C1', 'STATUS_LIST');
                $r = 2;
                foreach ($status as $s) {
                    $master->setCellValue("C{$r}", $s);
                    $r++;
                }
                $statusListRange = "master!\$C\$2:\$C\$" . (1 + count($status));

                // ===== Styling & format Excel =====
                $maxRows = 500;
                $lastCol = 'K'; // ✅ sebelumnya J
                $headerRange = "A1:{$lastCol}1";
                $bodyRange   = "A1:{$lastCol}{$maxRows}";

                $sheet->freezePane('A2');

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '212529'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3CD'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getStyle($bodyRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D0D4D9'],
                        ],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(22);

                // ✅ nomor_hp & nik geser kolom
                // nomor_hp sekarang di kolom C
                $sheet->getStyle("C2:C{$maxRows}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

                // nik sekarang di kolom D
                $sheet->getStyle("D2:D{$maxRows}")
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);

                // Alignment beberapa kolom
                $sheet->getStyle("A2:A{$maxRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // nomor_buku
                $sheet->getStyle("C2:D{$maxRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // hp, nik
                $sheet->getStyle("F2:F{$maxRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // ip
                $sheet->getStyle("G2:G{$maxRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // tgl
                $sheet->getStyle("K2:K{$maxRows}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // status

                // ✅ Set lebar kolom
                $widths = [
                    'A' => 14, // nomor_buku
                    'B' => 22, // nama
                    'C' => 16, // nomor_hp
                    'D' => 20, // nik
                    'E' => 30, // alamat
                    'F' => 16, // ip
                    'G' => 18, // tanggal
                    'H' => 24, // paket
                    'I' => 18, // area
                    'J' => 24, // sales
                    'K' => 18, // status
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                /**
                 * ===== Dependent Dropdown Sales =====
                 */
                $startColIndex = 4; // kolom D di master untuk sales lists
                $colIndex = $startColIndex;

                foreach ($areas as $a) {
                    $areaName = $a->nama_area;
                    $rangeName = $this->sanitizeRangeName($areaName);

                    $salesList = $salesByAreaId[$a->id_area] ?? [];

                    $colLetter = $this->colLetter($colIndex);
                    $master->setCellValue("{$colLetter}1", $rangeName);

                    $r = 2;
                    foreach ($salesList as $email) {
                        $master->setCellValue("{$colLetter}{$r}", $email);
                        $r++;
                    }

                    if (count($salesList) === 0) {
                        $master->setCellValue("{$colLetter}2", '');
                        $r = 3;
                    }

                    $endRow = $r - 1;

                    $named = new NamedRange($rangeName, $master, "\${$colLetter}\$2:\${$colLetter}\${$endRow}");
                    $spreadsheet->addNamedRange($named);

                    $colIndex++;
                }

                // ===== Terapkan dropdown di sheet utama =====
                // H: paket_layanan (sebelumnya G)
                $this->applyListValidation($sheet, "H2:H{$maxRows}", $paketListRange);

                // I: area (sebelumnya H)
                $this->applyListValidation($sheet, "I2:I{$maxRows}", $areaListRange);

                // K: status_pelanggan (sebelumnya J)
                $this->applyListValidation($sheet, "K2:K{$maxRows}", $statusListRange);

                // J: sales dependent (sebelumnya I)
                $this->applyDependentValidationSales($sheet, $maxRows);

                // Autosize (opsional)
                foreach (range('A', 'K') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    private function getSalesByAreaId(): array
    {
        $rows = DB::table('area_sales')
            ->join('sales', 'area_sales.id_sales', '=', 'sales.id_sales')
            ->join('users', 'sales.user_id', '=', 'users.id')
            ->select('area_sales.id_area', 'users.email')
            ->orderBy('area_sales.id_area')
            ->orderBy('users.email')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->id_area][] = $row->email;
        }

        return $map;
    }

    private function applyListValidation(Worksheet $sheet, string $cellRange, string $formulaRange): void
    {
        [$start, $end] = explode(':', $cellRange);
        [$startCol, $startRow] = $this->splitCell($start);
        [$endCol, $endRow]     = $this->splitCell($end);

        for ($r = (int)$startRow; $r <= (int)$endRow; $r++) {
            $cell = $startCol . $r;

            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input tidak valid');
            $validation->setError('Pilih nilai dari dropdown.');
            $validation->setFormula1("={$formulaRange}");
        }
    }

    private function applyDependentValidationSales(Worksheet $sheet, int $maxRows): void
    {
        for ($r = 2; $r <= $maxRows; $r++) {
            $cell = "J{$r}"; // ✅ sales sekarang kolom J

            // ✅ area sekarang kolom I (sebelumnya H)
            $formula = '=INDIRECT(SUBSTITUTE(SUBSTITUTE(SUBSTITUTE($I' . $r . '," ","_"),"-","_"),".","_"))';

            $validation = $sheet->getCell($cell)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Input tidak valid');
            $validation->setError('Pilih sales yang sesuai area.');
            $validation->setFormula1($formula);
        }
    }

    private function sanitizeRangeName(string $name): string
    {
        $name = str_replace([' ', '-', '.'], '_', $name);

        if (preg_match('/^\d/', $name)) {
            $name = '_' . $name;
        }

        $name = preg_replace('/[^A-Za-z0-9_]/', '_', $name);

        return $name ?: '_AREA';
    }

    private function colLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = (int)(($index - $mod) / 26);
        }
        return $letter;
    }

    private function splitCell(string $cell): array
    {
        preg_match('/^([A-Z]+)(\d+)$/', $cell, $m);
        return [$m[1], $m[2]];
    }
}
