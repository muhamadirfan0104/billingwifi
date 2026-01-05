<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PelangganPembayaranTahunanSalesAreaExport implements WithMultipleSheets
{
    public function __construct(
        protected int $tahun
    ) {}

    public function sheets(): array
    {
        // 1) Ambil assignments (sales-area)
        $assignments = DB::table('area_sales as asg')
            ->join('sales as s', 's.id_sales', '=', 'asg.id_sales')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('area as a', 'a.id_area', '=', 'asg.id_area')
            ->select(
                'asg.id_area',
                'a.nama_area',
                's.id_sales',
                'u.name as nama_sales'
            )
            ->orderBy('u.name')
            ->orderBy('a.nama_area')
            ->get();

        // 2) Map pembayaran per pelanggan per bulan (ambil tanggal bayar terakhir pada bulan tsb)
        $rows = DB::table('pembayaran')
            ->selectRaw('id_pelanggan, MONTH(tanggal_bayar) as bulan, MAX(tanggal_bayar) as tanggal_bayar')
            ->whereYear('tanggal_bayar', $this->tahun)
            ->groupBy('id_pelanggan', DB::raw('MONTH(tanggal_bayar)'))
            ->get();

        $paidDates = [];
        foreach ($rows as $r) {
            $paidDates[(int)$r->id_pelanggan][(int)$r->bulan] = $r->tanggal_bayar;
        }

        // 3) Buat sheet per assignment
        $sheets = [];
        foreach ($assignments as $asg) {
            $sheets[] = new PelangganPembayaranSalesAreaSheet(
                tahun: $this->tahun,
                idSales: (int) $asg->id_sales,
                idArea: (int) $asg->id_area,
                salesName: (string) $asg->nama_sales,
                areaName: (string) $asg->nama_area,
                paidDates: $paidDates
            );
        }

        return $sheets;
    }
}
