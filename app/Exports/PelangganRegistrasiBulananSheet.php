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

class PelangganRegistrasiBulananSheet extends DefaultValueBinder implements
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
        protected int $bulan
    ) {}

    // ✅ Anti scientific: kolom tertentu dipaksa STRING
    public function bindValue(Cell $cell, $value)
    {
        // A..I, M..O = text
        $textColumns = ['A','B','C','D','E','F','G','H','I','M','N','O'];

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
                'area:id_area,nama_area',
                'sales.user:id,name',
                'langgananAktifTerbaru.paket:id_paket,nama_paket,kecepatan,harga_dasar,ppn_nominal,harga_total',
            ])
            ->whereYear('tanggal_registrasi', $this->tahun)
            ->whereMonth('tanggal_registrasi', $this->bulan)
            ->orderBy('tanggal_registrasi')
            ->orderBy('id_pelanggan')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NO',                    // A (text)
            'No Buku',               // B (text)
            'Nama',                  // C (text)
            'NIK',                   // D (text)
            'Alamat',                // E (text)
            'Nomor HP',              // F (text)
            'IP Address',            // G (text)
            'Tanggal Registrasi',    // H (text)
            'Paket Layanan (Mbps)',  // I (text)
            'Harga DPP',             // J (Rp number)
            'Harga PPN',             // K (Rp number)
            'Harga Total',           // L (Rp number)
            'Area',                  // M (text)
            'Sales',                 // N (text)
            'Status',                // O (text)
        ];
    }

    public function map($pelanggan): array
    {
        $this->row++;

        $paket = $pelanggan->langgananAktifTerbaru?->paket;

        return [
            $this->row,
            $pelanggan->nomor_buku ?: 0,
            $pelanggan->nama ?? '',
            $pelanggan->nik ?? '',
            $pelanggan->alamat ?? '',
            $pelanggan->nomor_hp ?? '',
            $pelanggan->ip_address ?? '',
            $pelanggan->tanggal_registrasi
                ? Carbon::parse($pelanggan->tanggal_registrasi)->format('d-m-Y')
                : '',
            $paket ? ($paket->nama_paket . ' - ' . $paket->kecepatan) : '-',

            // numeric untuk rupiah
            (float) ($paket?->harga_dasar ?? 0),
            (float) ($paket?->ppn_nominal ?? 0),
            (float) ($paket?->harga_total ?? 0),

            $pelanggan->area?->nama_area ?? '-',
            $pelanggan->sales?->user?->name ?? '-',
            $pelanggan->status_pelanggan ?? '-',
        ];
    }

public function columnFormats(): array
{
    $accountingRp = '_-"Rp"* #,##0_-;-"Rp"* #,##0_-;_-"Rp"* "-"_-;_-@_-';

    return [
        'J' => $accountingRp,
        'K' => $accountingRp,
        'L' => $accountingRp,
    ];
}

public function registerEvents(): array
{
    return [
        AfterSheet::class => function (AfterSheet $event) {
            $sheet = $event->sheet->getDelegate();

            // judul di atas
            $sheet->insertNewRowBefore(1, 1);

            $namaBulan = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
            $judul = 'DATA REGISTRASI PELANGGAN – ' . Str::upper($namaBulan);

            $sheet->mergeCells('A1:O1');
            $sheet->setCellValue('A1', $judul);

            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

            // HEADER row 2: bold + background soft
            $sheet->getStyle('A2:O2')->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9ECEF'], // abu soft
                ],
            ]);

            // freeze + filter/sort
            $sheet->freezePane('A3');
            $sheet->setAutoFilter('A2:O2');

            // autosize
            foreach (range('A', 'O') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // ==========================
            // ZEBRA ROW (DATA) soft
            // ==========================
            $lastRow = $sheet->getHighestRow(); // termasuk data
            if ($lastRow >= 3) {
                for ($r = 3; $r <= $lastRow; $r++) {
                    // selang-seling: baris genap dikasih background soft
                    if ($r % 2 === 0) {
                        $sheet->getStyle("A{$r}:O{$r}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'FFF9E6'], // kuning soft
                            ],
                        ]);
                    }
                }

                // ==========================
                // BORDER seluruh tabel
                // ==========================
                $sheet->getStyle("A2:O{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }

            // Optional: vertical center biar rapi
            $sheet->getStyle("A:O")->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        },
    ];
}

    public function title(): string
    {
        // nama sheet: "Jan", "Feb", dst (<=31 char aman)
        return Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('M');
    }
}
