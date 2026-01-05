<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Pelanggan;
use Illuminate\Support\Facades\DB;

class DashboardSalesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now  = Carbon::now();

        $startDate = $now->copy()->startOfMonth();
        $endDate   = $now->copy()->endOfMonth();

        $salesId = optional($user->sales)->id_sales;

        $basePelanggan = Pelanggan::query();
        if ($salesId) {
            $basePelanggan->where('id_sales', $salesId);
        }

        // TOTAL PELANGGAN
        $totalPelanggan = (clone $basePelanggan)->count();

        // STATUS BARU berdasar tanggal_registrasi
        $totalBaru = (clone $basePelanggan)
            ->whereMonth('tanggal_registrasi', $now->month)
            ->whereYear('tanggal_registrasi', $now->year)
            ->count();

        // STATUS LAIN
        $totalAktif = (clone $basePelanggan)->where('status_pelanggan', 'aktif')->count();
        $totalBerhenti = (clone $basePelanggan)->where('status_pelanggan', 'berhenti')->count();
        $totalIsolir = (clone $basePelanggan)->where('status_pelanggan', 'isolir')->count();

        // =====================
        // STATUS BAYAR FINAL (2 KATEGORI)
        // =====================
        // BELUM BAYAR = ada tagihan belum lunas
        $totalBelumBayar = (clone $basePelanggan)
            ->whereHas('tagihan', function ($q) {
                $q->where('status_tagihan', 'belum lunas');
            })
            ->count();

        // SUDAH BAYAR = punya tagihan & tidak punya tagihan belum lunas
        $totalSudahBayar = (clone $basePelanggan)
            ->whereHas('tagihan')
            ->whereDoesntHave('tagihan', function ($q) {
                $q->where('status_tagihan', 'belum lunas');
            })
            ->count();
// =====================
// BULAN INI (NET)
// =====================
$pembayaranBulanIni = DB::table('pembayaran as p')
    ->when($salesId, fn($q) => $q->where('p.id_sales', $salesId))
    ->whereYear('p.tanggal_bayar', $now->year)
    ->whereMonth('p.tanggal_bayar', $now->month)
    ->sum('p.nominal');

$komisiBulanIni = DB::table('transaksi_komisi as tk')
    ->join('pembayaran as p', 'p.id_pembayaran', '=', 'tk.id_pembayaran')
    ->when($salesId, fn($q) => $q->where('tk.id_sales', $salesId))
    ->whereYear('p.tanggal_bayar', $now->year)
    ->whereMonth('p.tanggal_bayar', $now->month)
    ->sum('tk.nominal_komisi');

$pengeluaranBulanIni = DB::table('pengeluaran as pg')
    ->when($salesId, fn($q) => $q->where('pg.id_sales', $salesId))
    ->where('pg.status_approve', 'approved')
    ->whereYear('pg.tanggal_approve', $now->year)
    ->whereMonth('pg.tanggal_approve', $now->month)
    ->sum('pg.nominal');

$wajibSetorBulanIni = $pembayaranBulanIni - $komisiBulanIni - $pengeluaranBulanIni;

$sudahSetorBulanIni = DB::table('setoran as st')
    ->when($salesId, fn($q) => $q->where('st.id_sales', $salesId))
    ->where('st.tahun', $now->year)
    ->where('st.bulan', $now->month)
    ->sum('st.nominal');

// Status bulan ini: lebih / kurang / pas
$selisihBulanIni = $sudahSetorBulanIni - $wajibSetorBulanIni;
$statusBulanIni  = $selisihBulanIni > 0 ? 'lebih' : ($selisihBulanIni < 0 ? 'kurang' : 'pas');


// =====================
// SISA BULAN LALU (AKUMULASI S/D AKHIR BULAN LALU) - (SETORAN S/D AKHIR BULAN LALU)
// bisa + (kurang setor) atau - (lebih setor)
// =====================
$endBulanLalu = $now->copy()->subMonth()->endOfMonth();

$pembayaranSdBulanLalu = DB::table('pembayaran as p')
    ->when($salesId, fn($q) => $q->where('p.id_sales', $salesId))
    ->whereDate('p.tanggal_bayar', '<=', $endBulanLalu->toDateString())
    ->sum('p.nominal');

$komisiSdBulanLalu = DB::table('transaksi_komisi as tk')
    ->join('pembayaran as p', 'p.id_pembayaran', '=', 'tk.id_pembayaran')
    ->when($salesId, fn($q) => $q->where('tk.id_sales', $salesId))
    ->whereDate('p.tanggal_bayar', '<=', $endBulanLalu->toDateString())
    ->sum('tk.nominal_komisi');

$pengeluaranSdBulanLalu = DB::table('pengeluaran as pg')
    ->when($salesId, fn($q) => $q->where('pg.id_sales', $salesId))
    ->where('pg.status_approve', 'approved')
    ->whereDate('pg.tanggal_approve', '<=', $endBulanLalu->toDateString())
    ->sum('pg.nominal');

// total kewajiban netto s/d akhir bulan lalu
$kewajibanSdBulanLalu = $pembayaranSdBulanLalu - $komisiSdBulanLalu - $pengeluaranSdBulanLalu;

// total setoran s/d akhir bulan lalu (lintas tahun)
$setoranSdBulanLalu = DB::table('setoran as st')
    ->when($salesId, fn($q) => $q->where('st.id_sales', $salesId))
    ->where(function ($q) use ($now) {
        $q->where('st.tahun', '<', $now->year)
          ->orWhere(function ($qq) use ($now) {
              $qq->where('st.tahun', $now->year)
                 ->where('st.bulan', '<=', $now->month - 1);
          });
    })
    ->sum('st.nominal');

// ini yang ditampilkan di atas (bisa + / -)
$sisaBulanLalu = $kewajibanSdBulanLalu - $setoranSdBulanLalu;

        $selectedMonth = $now->month;
        $selectedYear  = $now->year;

        return view('seles2.dashboard.index', [

            'sudahSetorBulanIni'  => $sudahSetorBulanIni,

            'totalPelanggan'      => $totalPelanggan,
            'totalAktif'          => $totalAktif,
            'totalBaru'           => $totalBaru,
            'totalBerhenti'       => $totalBerhenti,
            'totalIsolir'         => $totalIsolir,

            'totalSudahBayar'     => $totalSudahBayar,
            'totalBelumBayar'     => $totalBelumBayar,


            'startDate'           => $startDate,
            'endDate'             => $endDate,

            'selectedMonth'       => $selectedMonth,
            'selectedYear'        => $selectedYear,
'sisaBulanLalu'    => $sisaBulanLalu,
'wajibSetorBulanIni' => $wajibSetorBulanIni,
'sudahSetorBulanIni' => $sudahSetorBulanIni,
'selisihBulanIni'    => $selisihBulanIni,
'statusBulanIni'     => $statusBulanIni,


        ]);
    }
}
