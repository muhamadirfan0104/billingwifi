<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;


class PelangganSalesWilayahSheet extends DefaultValueBinder implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithTitle,
    WithColumnFormatting,
    WithStyles,
    WithEvents,
    WithCustomValueBinder

{
    protected int $row = 0;

    public function __construct(
        protected ?int $idSales,
        protected ?int $idArea,
        protected int $bulan,
        protected int $tahun,
        protected string $salesName,
        protected string $areaName
    ) {}

    public function collection()
    {
        return Pelanggan::query()
            ->with([
                'area:id_area,nama_area',
                'sales.user:id,name',
                'langgananAktifTerbaru.paket:id_paket,nama_paket,kecepatan,harga_dasar,ppn_nominal,harga_total',
            ])
            ->when($this->idSales, fn ($q) => $q->where('id_sales', $this->idSales))
            ->when($this->idArea, fn ($q) => $q->where('id_area', $this->idArea))
            ->orderBy('id_pelanggan')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NO',
            'No Buku',
            'Nama',
            'NIK',
            'Alamat',
            'Nomor HP',
            'IP Address',
            'Tanggal Registrasi',
            'Paket Layanan (Mbps)',
            'Harga DPP',
            'Harga PPN',
            'Harga Total',
            'Area',
            'Sales',
            'Status (aktif, berhenti, isolir)',
        ];
    }

    public function map($pelanggan): array
    {
        $this->row++;

        $noBuku = (string) ((int) ($pelanggan->nomor_buku ?: 0));

        $langganan = $pelanggan->langgananAktifTerbaru;
        $paket = $langganan?->paket;

return [
    $this->row,
    $pelanggan->nomor_buku ?: 0,
    $pelanggan->nama,
    $pelanggan->nik,          // STRING explicit oleh binder
    $pelanggan->alamat,
    $pelanggan->nomor_hp,
    $pelanggan->ip_address,
    $pelanggan->tanggal_registrasi
        ? Carbon::parse($pelanggan->tanggal_registrasi)->format('d-m-Y')
        : '',
    $paket ? ($paket->nama_paket . ' - ' . $paket->kecepatan) : '-',

    // 👇 BIARKAN ANGKA
    $paket?->harga_dasar ?? 0,
    $paket?->ppn_nominal ?? 0,
    $paket?->harga_total ?? 0,

    $pelanggan->area?->nama_area ?? '-',
    $pelanggan->sales?->user?->name ?? '-',
    $pelanggan->status_pelanggan ?? '-',
];

    }

public function columnFormats(): array
{
    $accountingRp = '_-"Rp"* #,##0_-;-"Rp"* #,##0_-;_-"Rp"* "-"_-;_-@_-';

    return [
        'A' => NumberFormat::FORMAT_TEXT,
        'B' => NumberFormat::FORMAT_TEXT,
        'C' => NumberFormat::FORMAT_TEXT,
        'D' => NumberFormat::FORMAT_TEXT,
        'E' => NumberFormat::FORMAT_TEXT,
        'F' => NumberFormat::FORMAT_TEXT,
        'G' => NumberFormat::FORMAT_TEXT,
        'H' => NumberFormat::FORMAT_TEXT,
        'I' => NumberFormat::FORMAT_TEXT,

        // ✅ Accounting
        'J' => $accountingRp,
        'K' => $accountingRp,
        'L' => $accountingRp,

        'M' => NumberFormat::FORMAT_TEXT,
        'N' => NumberFormat::FORMAT_TEXT,
        'O' => NumberFormat::FORMAT_TEXT,
    ];
}

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // =====================================================
            // 1) TAMBAH JUDUL DI ATAS (row 1), header jadi row 2
            // =====================================================
            $sheet->insertNewRowBefore(1, 1);

            $judul = 'DATA PELANGGAN – ' . trim($this->salesName) . ' – ' . trim($this->areaName);

            $sheet->mergeCells('A1:O1');
            $sheet->setCellValue('A1', $judul);

            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

            // =====================================================
            // 2) HEADER: bold + background abu soft
            // =====================================================
            $sheet->getStyle('A2:O2')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'],
                ],
            ]);

            // Freeze judul+header
            $sheet->freezePane('A3');

            // Filter/sort
            $sheet->setAutoFilter('A2:O2');

            // Autosize
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Align vertical rapi
            $sheet->getStyle('A:O')->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

            // =====================================================
            // 3) ZEBRA ROW (DATA) + BORDER
            // =====================================================
            $lastRow = $sheet->getHighestRow();

            if ($lastRow >= 3) {
                // zebra (baris genap)
                for ($r = 3; $r <= $lastRow; $r++) {
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:O{$r}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFF9E6'], // kuning soft
                            ],
                        ]);
                    }
                }

                // border semua sel (mulai dari header)
                $sheet->getStyle("A2:O{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
        },
    ];
}



    // styles() tetap boleh dipakai, tapi header bold sudah di event. Mau dipakai juga gapapa.
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        $title = trim($this->salesName) . '-' . trim($this->areaName);
        return Str::limit($title, 31, '');
    }
    public function bindValue(Cell $cell, $value)
{
    // Kolom yang HARUS string (anti scientific)
    $textColumns = ['A','B','C','D','E','F','G','H','I','M','N','O'];

    if (in_array($cell->getColumn(), $textColumns, true)) {
        $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
        return true;
    }

    return parent::bindValue($cell, $value);
}

}
