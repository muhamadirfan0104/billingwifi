<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PelangganRegistrasiTahunanExport implements WithMultipleSheets
{
    public function __construct(
        protected int $tahun
    ) {}

    public function sheets(): array
    {
        $sheets = [];

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            $sheets[] = new PelangganRegistrasiBulananSheet(
                tahun: $this->tahun,
                bulan: $bulan
            );
        }

        return $sheets;
    }
}
