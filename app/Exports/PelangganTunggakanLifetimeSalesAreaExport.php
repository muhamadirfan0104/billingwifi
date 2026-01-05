<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PelangganTunggakanLifetimeSalesAreaExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        $assignments = DB::table('area_sales as asg')
            ->join('sales as s', 's.id_sales', '=', 'asg.id_sales')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->join('area as a', 'a.id_area', '=', 'asg.id_area')
            ->select('asg.id_area', 'a.nama_area', 's.id_sales', 'u.name as nama_sales')
            ->orderBy('u.name')->orderBy('a.nama_area')
            ->get();

        // pembayaran ALL TIME -> map bulan per pelanggan per tahun-bulan
        $paid = DB::table('pembayaran')
            ->selectRaw("id_pelanggan, DATE_FORMAT(tanggal_bayar, '%Y-%m') as ym")
            ->groupBy('id_pelanggan', DB::raw("DATE_FORMAT(tanggal_bayar, '%Y-%m')"))
            ->get();

        $paidMap = [];
        foreach ($paid as $p) {
            $paidMap[(int)$p->id_pelanggan][(string)$p->ym] = true;
        }

        $sheets = [];
        foreach ($assignments as $asg) {
            $sheets[] = new PelangganTunggakanLifetimeSalesAreaSheet(
                idSales: (int)$asg->id_sales,
                idArea: (int)$asg->id_area,
                salesName: (string)$asg->nama_sales,
                areaName: (string)$asg->nama_area,
                paidMap: $paidMap
            );
        }
        return $sheets;
    }
}
