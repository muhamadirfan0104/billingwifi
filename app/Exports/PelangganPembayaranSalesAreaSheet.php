<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PelangganPembayaranSalesAreaSheet extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithColumnFormatting,
    WithEvents,
    WithCustomValueBinder
{
    protected int $row = 0;

    public function __construct(
        protected int $tahun,
        protected int $idSales,
        protected int $idArea,
        protected string $salesName,
        protected string $areaName,
        protected array $paidDates // [id_pelanggan][bulan] => tanggal_bayar
    ) {}

    // Paksa kolom tertentu jadi TEXT (biar tanggal & “BELUM” aman, dan tidak jadi scientific)
    public function bindValue(Cell $cell, $value)
    {
        // A: NO, B: NAMA, C: ALAMAT, D: IP, F..Q: bulan-bulan => text
        // A: NO, B: NAMA, C: NO BUKU, D: ALAMAT, E: IP, G..R: bulan-bulan
$textColumns = array_merge(['A','B','C','D','E'], range('G', 'R'));


        if (in_array($cell->getColumn(), $textColumns, true)) {
            $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return Pelanggan::query()
            ->with([
                'langgananAktifTerbaru.paket:id_paket,harga_total',
            ])
            ->where('id_sales', $this->idSales)
            ->where('id_area', $this->idArea)
            ->orderBy('nama')
            ->get();
    }

    public function headings(): array
    {
return [
    'NO',
    'NAMA PELANGGAN',
    'NO BUKU',
    'ALAMAT',
    'IP',
    'NOMINAL',
    'JANUARI',
    'FEBRUARI',
    'MARET',
    'APRIL',
    'MEI',
    'JUNI',
    'JULI',
    'AGUSTUS',
    'SEPTEMBER',
    'OKTOBER',
    'NOVEMBER',
    'DESEMBER',
];

    }

    public function map($pelanggan): array
    {
        $this->row++;

        $id = (int) $pelanggan->id_pelanggan;
        $harga = (float) ($pelanggan->langgananAktifTerbaru?->paket?->harga_total ?? 0);

        $bulanCells = [];
        for ($m = 1; $m <= 12; $m++) {
            $tgl = $this->paidDates[$id][$m] ?? null;

            if ($tgl) {
                // ✅ format yang kamu minta: 07/01/2025
                $bulanCells[] = Carbon::parse($tgl)->format('d/m/Y');
            } else {
                $bulanCells[] = 'BELUM';
            }
        }

return array_merge([
    $this->row,
    $pelanggan->nama ?? '',
    (string) ($pelanggan->nomor_buku ?: 0), // ✅ NO BUKU
    $pelanggan->alamat ?? '',
    $pelanggan->ip_address ?? '',
    $harga, // numeric rupiah
], $bulanCells);

    }

public function columnFormats(): array
{
    $accountingRp = '_-"Rp"* #,##0_-;-"Rp"* #,##0_-;_-"Rp"* "-"_-;_-@_-';

    return [
        'F' => $accountingRp, // ✅ NOMINAL jadi Accounting
    ];
}

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // =======================
            // JUDUL
            // =======================
            $sheet->insertNewRowBefore(1, 1);

            $judul = 'DATA PEMBAYARAN – ' . trim($this->salesName)
                   . ' – ' . trim($this->areaName)
                   . ' – ' . $this->tahun;

            $sheet->mergeCells('A1:R1');
            $sheet->setCellValue('A1', $judul);
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // =======================
            // HEADER
            // =======================
            $sheet->getStyle('A2:R2')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'],
                ],
            ]);

            $sheet->freezePane('A3');
            $sheet->setAutoFilter('A2:R2');

            // =======================
            // AUTOSIZE + ALIGN
            // =======================
            foreach (range('A', 'R') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $sheet->getStyle('G:R')->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            // =======================
            // WARNA STATUS BAYAR (DATA)
            // =======================
            $lastRowData = $sheet->getHighestRow();

            for ($row = 3; $row <= $lastRowData; $row++) {
                foreach (range('G', 'R') as $col) {
                    $cell = $col . $row;
                    $value = (string) $sheet->getCell($cell)->getValue();

                    if ($value === 'BELUM') {
                        $sheet->getStyle($cell)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'F8D7DA'],
                            ],
                            'font' => ['color' => ['rgb' => '842029']],
                        ]);
                    } else {
                        $sheet->getStyle($cell)->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'D1E7DD'],
                            ],
                            'font' => ['color' => ['rgb' => '0F5132']],
                        ]);
                    }
                }
            }

            // =======================
            // TOTAL (4 BARIS)
            // =======================
            $startTotalRow = $lastRowData + 1;

            $labels = [
                'Total Pelanggan Sudah Bayar',
                'Total Nominal Sudah Bayar',
                'Total Pelanggan Belum Bayar',
                'Total Nominal Belum Bayar',
            ];

            $labelColors = [
                'CCE5FF', // biru muda
                '99CCFF', // biru
                'FFE5B4', // oranye muda
                'FFD28A', // oranye
            ];

            for ($i = 0; $i < 4; $i++) {
                $r = $startTotalRow + $i;

                $sheet->mergeCells("A{$r}:F{$r}");
                $sheet->setCellValue("A{$r}", $labels[$i]);

                $sheet->getStyle("A{$r}:R{$r}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $labelColors[$i]],
                    ],
                ]);
            }

            // =======================
            // HITUNG TOTAL PER BULAN
            // =======================
            foreach (range('G', 'R') as $col) {
                $paidCount = 0;
                $paidNominal = 0;
                $unpaidCount = 0;
                $unpaidNominal = 0;

                for ($row = 3; $row <= $lastRowData; $row++) {
                    $status = (string) $sheet->getCell($col . $row)->getValue();
                    $nominal = (float) $sheet->getCell('F' . $row)->getCalculatedValue();

                    if ($status === 'BELUM') {
                        $unpaidCount++;
                        $unpaidNominal += $nominal;
                    } else {
                        $paidCount++;
                        $paidNominal += $nominal;
                    }
                }

// Total Pelanggan (TETAP NUMBER, JANGAN FORMAT Rp)
$sheet->setCellValue($col . ($startTotalRow + 0), $paidCount);
$sheet->setCellValue($col . ($startTotalRow + 2), $unpaidCount);

// Total Nominal (ACCOUNTING Rp)
$sheet->getCell($col . ($startTotalRow + 1))
      ->setValueExplicit((float) $paidNominal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

$sheet->getCell($col . ($startTotalRow + 3))
      ->setValueExplicit((float) $unpaidNominal, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);

// Accounting format (Excel style)
$accountingRp = '_-"Rp"* #,##0_-;-"Rp"* #,##0_-;_-"Rp"* "-"_-;_-@_-';

$sheet->getStyle($col . ($startTotalRow + 1))
    ->getNumberFormat()
    ->setFormatCode($accountingRp);

$sheet->getStyle($col . ($startTotalRow + 3))
    ->getNumberFormat()
    ->setFormatCode($accountingRp);

            }

            // =======================
            // GARIS (BORDER) SEMUA SEL
            // =======================
            $finalRow = $startTotalRow + 3;

            $sheet->getStyle("A2:R{$finalRow}")
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
        },
    ];
}

    public function title(): string
    {
        // Nama sheet: "Sales-Area" (max 31 char)
        return Str::limit(trim($this->salesName) . '-' . trim($this->areaName), 31, '');
    }
}
 