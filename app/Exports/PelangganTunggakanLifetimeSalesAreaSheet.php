<?php

namespace App\Exports;

use App\Models\Pelanggan;
use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PelangganTunggakanLifetimeSalesAreaSheet extends DefaultValueBinder implements
    FromCollection, WithHeadings, WithMapping, WithTitle, WithEvents, WithCustomValueBinder
{
    public function __construct(
        protected int $idSales,
        protected int $idArea,
        protected string $salesName,
        protected string $areaName,
        protected array $paidMap // [id_pelanggan][YYYY-mm] => true
    ) {}

    public function bindValue(Cell $cell, $value)
    {
        // teks: A,C,D. Nominal B numeric
        if (in_array($cell->getColumn(), ['A','C','D'], true)) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function collection()
    {
        return Pelanggan::query()
            ->with(['langgananAktifTerbaru.paket:id_paket,harga_total'])
            ->where('id_sales', $this->idSales)
            ->where('id_area', $this->idArea)
            ->orderBy('nama')
            ->get();
    }

    public function headings(): array
    {
        return [
            'NAMA PELANGGAN',
            'NOMINAL',
            'PERIODE TUNGGAKAN',
            'JML BULAN',
        ];
    }

    private function fmtPeriode(Carbon $start, Carbon $end): string
    {
        $bulanStart = Str::upper($start->translatedFormat('F Y'));
        $bulanEnd   = Str::upper($end->translatedFormat('F Y'));

        if ($start->format('Y-m') === $end->format('Y-m')) {
            return $bulanStart;
        }
        return $bulanStart . ' - ' . $bulanEnd;
    }

    public function map($pelanggan): array
    {
        $nominal = (float) ($pelanggan->langgananAktifTerbaru?->paket?->harga_total ?? 0);

        // mulai kewajiban = bulan registrasi (kalau kosong, fallback ke bulan sekarang)
        $start = $pelanggan->tanggal_registrasi
            ? Carbon::parse($pelanggan->tanggal_registrasi)->startOfMonth()
            : now()->startOfMonth();

        $end = now()->startOfMonth();

        // cari bulan pertama yg BELUM bayar dari start..end
        $firstUnpaid = null;
        $cursor = $start->copy();
        while ($cursor <= $end) {
            $ym = $cursor->format('Y-m');
            $paid = !empty($this->paidMap[(int)$pelanggan->id_pelanggan][$ym]);

            if (!$paid) {
                $firstUnpaid = $cursor->copy();
                break;
            }
            $cursor->addMonth();
        }

        if (!$firstUnpaid) {
            $periode = '-';
            $jmlBulan = 0;
        } else {
            // total bulan tunggakan = dari firstUnpaid sampai end
            $jmlBulan = $firstUnpaid->diffInMonths($end) + 1;
            $periode  = $this->fmtPeriode($firstUnpaid, $end);
        }

        return [
            $pelanggan->nama ?? '',
            $nominal,
            $periode,
            $jmlBulan,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // judul
                $sheet->insertNewRowBefore(1, 1);
                $judul = 'DATA TUNGGAKAN – ' . trim($this->salesName) . ' – ' . trim($this->areaName);
                $sheet->mergeCells('A1:D1');
                $sheet->setCellValue('A1', $judul);
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

                // header style
                $sheet->getStyle('A2:D2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF'],
                    ],
                ]);

                $sheet->freezePane('A3');
                $sheet->setAutoFilter('A2:D2');

                foreach (range('A','D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                $lastRow = $sheet->getHighestRow();

                // nominal rupiah
                $sheet->getStyle("B3:B{$lastRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp"#,##0');

                // warna berdasarkan JML BULAN (kolom D)
                for ($r = 3; $r <= $lastRow; $r++) {
                    $bulan = (int) $sheet->getCell("D{$r}")->getCalculatedValue();

                    if ($bulan === 0)      $rgb = 'D1E7DD'; // hijau
                    elseif ($bulan === 1)  $rgb = 'E9ECEF'; // abu
                    elseif ($bulan === 2)  $rgb = 'FFF3CD'; // kuning
                    else                   $rgb = 'F8D7DA'; // merah

                    $sheet->getStyle("A{$r}:D{$r}")->applyFromArray([
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $rgb],
                        ],
                    ]);
                }

                // border semua sel
                $sheet->getStyle("A2:D{$lastRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            }
        ];
    }

    public function title(): string
    {
        return Str::limit(trim($this->salesName) . '-' . trim($this->areaName), 31, '');
    }
}
