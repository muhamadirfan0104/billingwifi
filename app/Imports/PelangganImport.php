<?php

namespace App\Imports;

use App\Models\Area;
use App\Models\Langganan;
use App\Models\Paket;
use App\Models\Pelanggan;
use App\Models\Sales;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PelangganImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public function headingRow(): int
    {
        return 1;
    }

    private function clean($v)
    {
        if (is_string($v)) {
            $v = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\x{00A0}]/u', '', $v);
            $v = trim($v);
            return $v === '' ? null : $v;
        }
        return $v;
    }

    private function normalizeKey(string $k): string
    {
        $k = strtolower(trim($k));
        $k = preg_replace('/\s+/', '_', $k);

        // typo yang sering kejadian
        if ($k === 'ip_addres') $k = 'ip_address';

        // ✅ dukung variasi header nomor buku
        if (in_array($k, ['no_buku', 'nomor_buku_pelanggan', 'nomor_buku'])) {
            $k = 'nomor_buku';
        }

        return $k;
    }

    private function parseTanggal($value): string
    {
        if ($value === null || $value === '') {
            throw new \Exception("tanggal_registrasi kosong");
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->toDateString();
        }

        $v = trim((string) $value);

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) {
            return Carbon::createFromFormat('d/m/Y', $v)->toDateString();
        }

        return Carbon::parse($v)->toDateString();
    }

    public function model(array $row)
    {
        // Normalisasi header + bersihin value
        $row = collect($row)->mapWithKeys(function ($v, $k) {
            $key = is_string($k) ? $this->normalizeKey($k) : $k;
            return [$key => $this->clean($v)];
        })->toArray();
        // ✅ Paksa beberapa kolom jadi string (Excel sering kirim numeric)
foreach (['nomor_buku','nomor_hp','nik'] as $k) {
    if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
        $row[$k] = (string) $row[$k];
    }
}


        // Skip baris hantu
        if (empty($row['nama']) && empty($row['nik']) && empty($row['ip_address'])) {
            return null;
        }

        // ✅ Validasi kolom wajib (+ nomor_buku optional)
        Validator::make($row, [
            'nomor_buku'         => ['nullable', 'string', 'max:50'], // ✅ BARU (boleh kosong & boleh double)
            'nama'               => ['required','string','max:100'],
            'nomor_hp'           => ['required','string'],
            'nik'                => ['required','string','max:30'],
            'alamat'             => ['required','string'],
            'ip_address'         => ['required','string'],
            'tanggal_registrasi' => ['required'],
            'paket_layanan'      => ['required','string'],
            'area'               => ['required','string'],
            'sales'              => ['required','string'],
            'status_pelanggan'   => ['required', Rule::in(['aktif','isolir','berhenti'])],
        ])->validate();

        // Parse tanggal
        $tanggal = $this->parseTanggal($row['tanggal_registrasi']);

        // Lookup paket
        $paket = Paket::where('nama_paket', $row['paket_layanan'])->first();
        if (!$paket) {
            throw new \Exception("Paket tidak ditemukan: {$row['paket_layanan']}");
        }

        // Lookup area
        $area = Area::where('nama_area', $row['area'])->first();
        if (!$area) {
            throw new \Exception("Area tidak ditemukan: {$row['area']}");
        }

        // Lookup sales user (email/nama) -> sales table
        $userSales = User::query()
            ->where('role', 'sales')
            ->where(function ($q) use ($row) {
                $q->where('email', $row['sales'])
                  ->orWhere('name', $row['sales']);
            })
            ->first();

        if (!$userSales) {
            throw new \Exception("User sales tidak ditemukan (email/nama): {$row['sales']}");
        }

        $sales = Sales::where('user_id', $userSales->id)->first();
        if (!$sales) {
            throw new \Exception("Data sales (tabel sales) tidak ditemukan untuk: {$row['sales']}");
        }

        // Validasi sales harus termasuk area itu
        $allowed = DB::table('area_sales')
            ->where('id_area', $area->id_area)
            ->where('id_sales', $sales->id_sales)
            ->exists();

        if (!$allowed) {
            throw new \Exception("Sales {$row['sales']} tidak terdaftar untuk area {$row['area']}");
        }

        // ✅ SIMPAN pelanggan: kunci unik tetap ip_address
        $pelanggan = Pelanggan::updateOrCreate(
            ['ip_address' => $row['ip_address']],
            [
                'id_area'            => $area->id_area,
                'id_sales'           => $sales->id_sales,
                'nomor_buku'         => $row['nomor_buku'] ?? null, // ✅ BARU
                'nama'               => $row['nama'],
                'nik'                => $row['nik'],
                'nomor_hp'           => $row['nomor_hp'],
                'alamat'             => $row['alamat'],
                'status_pelanggan'   => $row['status_pelanggan'],
                'tanggal_registrasi' => $tanggal,
            ]
        );

        // Simpan langganan (1 pelanggan = 1 langganan)
        $statusLangganan = Langganan::statusLanggananOptions($row['status_pelanggan']);

        Langganan::updateOrCreate(
            ['id_pelanggan' => $pelanggan->id_pelanggan],
            [
                'id_paket'         => $paket->id_paket,
                'tanggal_mulai'    => $tanggal,
                'status_langganan' => $statusLangganan,
                'tanggal_isolir'   => $row['status_pelanggan'] === 'isolir' ? now()->toDateString() : null,
                'tanggal_berhenti' => $row['status_pelanggan'] === 'berhenti' ? now()->toDateString() : null,
            ]
        );

        return null;
    }
}