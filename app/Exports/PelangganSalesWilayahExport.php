<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PelangganSalesWilayahExport implements WithMultipleSheets
{
    public function __construct(
        protected int $tahun,
        protected int $bulan,
        protected array $selectedUnits = []
    ) {}

    public function sheets(): array
    {
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
            ->get()
            ->map(function ($row) {
                $row->key = 'sales-' . $row->id_sales . '-area-' . $row->id_area;
                return $row;
            });

        if (!empty($this->selectedUnits)) {
            $assignments = $assignments->whereIn('key', $this->selectedUnits)->values();
        }

        $sheets = [];
        foreach ($assignments as $asg) {
            $sheets[] = new PelangganSalesWilayahSheet(
                idSales: (int) $asg->id_sales,
                idArea: (int) $asg->id_area,
                bulan: $this->bulan,
                tahun: $this->tahun,
                salesName: (string) $asg->nama_sales,
                areaName: (string) $asg->nama_area
            );
        }

        return $sheets;
    }
}
