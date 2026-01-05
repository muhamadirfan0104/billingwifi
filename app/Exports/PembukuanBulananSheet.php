<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PembukuanBulananSheet implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    protected Collection $rows;
    protected float $totalPemasukan = 0;
    protected float $totalPengeluaran = 0;

    public function __construct(
        protected int $tahun,
        protected int $bulan
    ) {}

    public function collection()
    {
        // =========================
        // PEMASUKAN: PEMBAYARAN (Internet)
        // =========================
        $pembayaran = DB::table('pembayaran as p')
            ->leftJoin('pelanggan as pl', 'pl.id_pelanggan', '=', 'p.id_pelanggan')
            ->leftJoin('payment_item as pi', 'pi.id_pembayaran', '=', 'p.id_pembayaran')
            ->leftJoin('tagihan as t', 't.id_tagihan', '=', 'pi.id_tagihan')
            ->selectRaw("
                DATE(p.tanggal_bayar) as tanggal,

                CONCAT(
                    'Internet ',
                    COALESCE(pl.nama,'-'),
                    ' (',
                    COALESCE(
                        CONCAT(
                            CONCAT(
                                ELT(MIN(t.bulan),
                                    'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                                    'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
                                ),
                                ' ',
                                MIN(t.tahun)
                            ),
                            CASE
                                WHEN MIN(t.tahun)=MAX(t.tahun) AND MIN(t.bulan)=MAX(t.bulan)
                                    THEN ''
                                ELSE CONCAT(
                                    ' - ',
                                    ELT(MAX(t.bulan),
                                        'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                                        'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
                                    ),
                                    ' ',
                                    MAX(t.tahun)
                                )
                            END
                        ),
                        CONCAT(
                            ELT(MONTH(p.tanggal_bayar),
                                'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                                'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
                            ),
                            ' ',
                            YEAR(p.tanggal_bayar)
                        )
                    ),
                    ')'
                ) as ket,

                p.nominal as nominal
            ")
            ->whereYear('p.tanggal_bayar', $this->tahun)
            ->whereMonth('p.tanggal_bayar', $this->bulan)
            ->groupBy('p.id_pembayaran', 'tanggal', 'pl.nama', 'p.nominal', 'p.tanggal_bayar')
            ->orderBy('tanggal')
            ->get();

        // =========================
        // PEMASUKAN: SETORAN
        // =========================
        $setoran = DB::table('setoran as st')
            ->leftJoin('sales as s', 's.id_sales', '=', 'st.id_sales')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('area as a', 'a.id_area', '=', 'st.id_area')
            ->selectRaw("
                DATE(st.tanggal_setoran) as tanggal,
                CONCAT(
                    'Setoran ',
                    COALESCE(u.name,'-'),
                    ' - ',
                    COALESCE(a.nama_area,'-'),
                    ' (',
                    ELT(st.bulan,
                        'JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI',
                        'JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'
                    ),
                    ' ',
                    st.tahun,
                    ')'
                ) as ket,
                st.nominal as nominal
            ")
            ->where('st.tahun', $this->tahun)
            ->where('st.bulan', $this->bulan)
            ->orderBy('tanggal')
            ->get();

        $pemasukanAll = collect($pembayaran)->concat($setoran)->sortBy('tanggal')->values();
        $this->totalPemasukan = (float) $pemasukanAll->sum('nominal');

        // =========================
        // PENGELUARAN (approved)
        // =========================
        $pengeluaran = DB::table('pengeluaran as pg')
            ->leftJoin('sales as s', 's.id_sales', '=', 'pg.id_sales')
            ->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('area as a', 'a.id_area', '=', 'pg.id_area')
            ->selectRaw("
                DATE(pg.tanggal_approve) as tanggal,
                CONCAT(
                    pg.nama_pengeluaran,
                    ' - ',
                    COALESCE(u.name,'-'),
                    ' - ',
                    COALESCE(a.nama_area,'-')
                ) as ket,
                pg.nominal as nominal
            ")
            ->where('pg.status_approve', 'approved')
            ->whereYear('pg.tanggal_approve', $this->tahun)
            ->whereMonth('pg.tanggal_approve', $this->bulan)
            ->orderBy('tanggal')
            ->get();

        $this->totalPengeluaran = (float) collect($pengeluaran)->sum('nominal');

        // =========================
        // GABUNG PER TANGGAL (format seperti contoh)
        // =========================
        $byDateIncome  = $pemasukanAll->groupBy('tanggal');
        $byDateExpense = collect($pengeluaran)->groupBy('tanggal');

        $allDates = collect($byDateIncome->keys())
            ->merge($byDateExpense->keys())
            ->unique()
            ->sort()
            ->values();

        $rows = collect();

        foreach ($allDates as $tanggal) {
            $inList  = ($byDateIncome[$tanggal]  ?? collect())->values();
            $outList = ($byDateExpense[$tanggal] ?? collect())->values();

            $max = max($inList->count(), $outList->count());

            for ($i = 0; $i < $max; $i++) {
                $in  = $inList[$i]  ?? null;
                $out = $outList[$i] ?? null;

                $rows->push([
                    $i === 0 ? Carbon::parse($tanggal)->translatedFormat('d F Y') : '',
                    $in?->ket ?? '',
                    $in?->nominal ?? '',
                    $out?->ket ?? '',
                    $out?->nominal ?? '',
                ]);
            }
        }

        $this->rows = $rows;
        return $rows;
    }

    public function headings(): array
    {
        return ['TANGGAL', 'PEMASUKAN', 'NOMINAL', 'PENGELUARAN', 'NOMINAL'];
    }

    public function title(): string
    {
        // butuh Carbon locale id (sudah kamu set di AppServiceProvider)
        return Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // =====================
                // JUDUL
                // =====================
                $sheet->insertNewRowBefore(1, 1);
                $namaBulan = Carbon::create($this->tahun, $this->bulan, 1)->translatedFormat('F Y');
                $judul = 'PEMBUKUAN – ' . Str::upper($namaBulan);

                $sheet->mergeCells('A1:E1');
                $sheet->setCellValue('A1', $judul);

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // =====================
                // HEADER (row 2)
                // =====================
                $sheet->getStyle('A2:E2')->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'E9ECEF'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ]
                ]);

                // Freeze (tanpa filter/sort)
                $sheet->freezePane('A3');

                // Autosize
                foreach (range('A','E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // =====================
                // FORMAT NOMINAL ACCOUNTING (SEMUA)
                // =====================
                $accountingRp = '_-"Rp"* #,##0_-;-"Rp"* #,##0_-;_-"Rp"* "-"_-;_-@_-';

                $lastRowData = $sheet->getHighestRow(); // terakhir data (sebelum total)

                // format kolom nominal data
                if ($lastRowData >= 3) {
                    $sheet->getStyle("C3:C{$lastRowData}")->getNumberFormat()->setFormatCode($accountingRp);
                    $sheet->getStyle("E3:E{$lastRowData}")->getNumberFormat()->setFormatCode($accountingRp);
                }

                // =====================
                // TOTAL (1 baris, tanpa selisih)
                // =====================
                $rowTotal = $lastRowData + 1;

                $sheet->mergeCells("A{$rowTotal}:B{$rowTotal}");
                $sheet->setCellValue("A{$rowTotal}", "TOTAL");
                $sheet->setCellValue("C{$rowTotal}", $this->totalPemasukan);
                $sheet->setCellValue("E{$rowTotal}", $this->totalPengeluaran);

                $sheet->getStyle("A{$rowTotal}:E{$rowTotal}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFF3CD'], // soft kuning
                    ],
                ]);

                $sheet->getStyle("A{$rowTotal}:B{$rowTotal}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getStyle("C{$rowTotal}")->getNumberFormat()->setFormatCode($accountingRp);
                $sheet->getStyle("E{$rowTotal}")->getNumberFormat()->setFormatCode($accountingRp);

                // =====================
                // BORDER SEMUA (HEADER + DATA + TOTAL)
                // =====================
                $finalRow = $rowTotal;
                $sheet->getStyle("A2:E{$finalRow}")
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }
}
