@extends('seles2.layout.master')

@section('content')
@php
    // ====== SETTING STATIC ======
    $companyName = 'PT. NALENDRA GIGANTARA MEDIA';
    $companySub  = 'INTERNET SERVICE PROVIDER';
    $companyAddr = 'Dsn. Kedungwulut RT/RW 003/002 Kel/Desa Kedungwulut, Kec. Bandung Kab. Tulungagung, 66274';
    $companyTel  = 'Telp. 0355 5351137 / 0852 8181 3088';
    $companyEmail= 'email: gusma@ptnalendra.id';
    $companyWeb  = 'Website: www.ptnalendra.id';

    // ====== DATA DINAMIS ======
    $pel = $pembayaran->pelanggan;
    $items = $pembayaran->paymentItems ?? collect();

$subTotal = (int) $items->sum('nominal_bayar');
$total = $subTotal;


    if (!function_exists('terbilang_helper')) {
        function terbilang_helper($angka) {
            $angka = abs((int)$angka);
            $bilang = ["", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas"];
            if ($angka < 12) return $bilang[$angka];
            if ($angka < 20) return terbilang_helper($angka - 10) . " belas";
            if ($angka < 100) return terbilang_helper(intval($angka / 10)) . " puluh " . terbilang_helper($angka % 10);
            if ($angka < 200) return "seratus " . terbilang_helper($angka - 100);
            if ($angka < 1000) return terbilang_helper(intval($angka / 100)) . " ratus " . terbilang_helper($angka % 100);
            if ($angka < 2000) return "seribu " . terbilang_helper($angka - 1000);
            if ($angka < 1000000) return terbilang_helper(intval($angka / 1000)) . " ribu " . terbilang_helper($angka % 1000);
            if ($angka < 1000000000) return terbilang_helper(intval($angka / 1000000)) . " juta " . terbilang_helper($angka % 1000000);
            return "terlalu besar";
        }
    }

    $noInvoice = $pembayaran->no_pembayaran;
    $tgl = $pembayaran->tanggal_bayar?->format('d M Y') ?? now()->format('d M Y');
    $jatuhTempo = $pembayaran->tanggal_bayar?->copy()->addDays(20)->format('d M Y') ?? now()->addDays(20)->format('d M Y');

    $sales = $pembayaran->sales;
    $salesUser = optional($sales)->user;
    $namaSales = $salesUser->name ?? $sales->nama ?? 'Admin';

    // mode embed untuk iframe modal
    $embed = $embed ?? request()->boolean('embed');
@endphp

<div class="invoice-wrap">
    <div class="invoice-paper">

{{-- ===== HEADER KWITANSI (seperti contoh) ===== --}}
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 10kok px;">
    <tr>
        {{-- LOGO KIRI --}}
<td width="140" valign="middle" align="left" style="padding-right: 10px;">
<img
  src="{{ asset('img/logo.webp') }}"
  alt="Logo"
  style="width:120px;height:120px;object-fit:contain;display:block;"
>

</td>


        {{-- TEKS TENGAH --}}
        <td valign="middle" align="center">
            <div style="font-size: 24px; font-weight: 900; text-transform: uppercase;">
                PT. NALENDRA GIGANTARA MEDIA
            </div>
            <div style="font-size: 16px; font-weight: 800; text-transform: uppercase;">
                INTERNET SERVICE PROVIDER
            </div>

            <div style="font-size: 13px; line-height: 1.3;">
                Dsn. Kedungwulut RT/RW 003/002 Kel/Desa Kedungwulut<br>
                Kec. Bandung Kab. Tulungagung, 66274<br>
                Telp. 0355 5351137 / 0852 8181 3088<br>
                email: gusma@ptnalendra.id<br>
                Website:www.ptnalendra.id
            </div>
        </td>
    </tr>
</table>
        <div class="inv-line"></div>

<table width="100%" cellpadding="0" cellspacing="0" class="inv-info-table">
    {{-- BARIS 1 : HANYA JUDUL KIRI --}}
    <tr>
        <td width="55%" valign="top">
            <div class="label">Kepada Yth.</div>
        </td>
        <td width="45%"></td>
    </tr>

    {{-- BARIS 2 : ISI KIRI + KANAN --}}
    <tr>
        {{-- KIRI --}}
        <td width="55%" valign="top">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="60">Nama</td>
                    <td width="10">:</td>
                    <td><strong>{{ $pel->nama ?? '-' }}</strong></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $pel->alamat ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Contact</td>
                    <td>:</td>
                    <td>{{ $pel->nomor_hp ?? '-' }}</td>
                </tr>
            </table>
        </td>

        {{-- KANAN --}}
        <td width="45%" valign="top" align="right">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="110" align="left">No. Kwitansi</td>
                    <td width="10" align="center">:</td>
                    <td align="left"><strong>{{ $noInvoice }}</strong></td>
                </tr>
                <tr>
                    <td align="left">Tanggal</td>
                    <td align="center">:</td>
                    <td align="left">{{ $tgl }}</td>
                </tr>
                <tr>
                    <td align="left">Jatuh Tempo</td>
                    <td align="center">:</td>
                    <td align="left">{{ $jatuhTempo }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>


        <div class="inv-section-title">Kwintansi Pembayaran</div>

        <table class="inv-table">
            <thead>
                <tr>
                    <th style="width:5%;">No.</th>
                    <th style="width:65%;">Uraian Layanan</th>
                    <th style="width:30%;">Total (Rp)<br><small>(Termasuk PPN 11%)</small></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $i => $it)
                    @php
                        $t = $it->tagihan;
                        $periode = $t ? \Carbon\Carbon::create($t->tahun, $t->bulan, 1)->translatedFormat('F Y') : '-';
                        $paket = optional(optional($t)->langganan)->paket;
                        $namaPaket = $paket?->nama_paket ? ($paket->nama_paket.' '.$paket->kecepatan) : 'Layanan Internet';
                        $uraian = $namaPaket . ' <br><small class="text-muted">Periode: ' . $periode . '</small>';
                    @endphp
                    <tr>
                        <td class="center">{{ $i+1 }}</td>
                        <td>{!! $uraian !!}</td>
                        <td class="right">{{ number_format($it->nominal_bayar, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="center" style="padding:20px;">Tidak ada item pembayaran</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 5px;">
            <tr>
                <td width="50%">
                    <div class="terbilang-box">
                        <strong>Terbilang:</strong><br>
                        <i style="text-transform: capitalize;"># {{ ucfirst(trim(terbilang_helper($total))) }} rupiah #</i>
                    </div>
                </td>
                <td width="50%" valign="top">
                    <table width="100%" class="inv-total-table" cellspacing="0">

<tr class="grand-total">
    <td>TOTAL PEMBAYARAN</td>
    <td class="right">Rp {{ number_format($total, 0, ',', '.') }}</td>
</tr>

                    </table>
                </td>
            </tr>
        </table>
<table width="100%" cellpadding="0" cellspacing="0" style="margin-top:6px;">
    <tr>
        <td style="font-size:11px; font-style:italic; color:#333;">
            * Harga sudah termasuk Pajak Pertambahan Nilai (PPN) 11%
        </td>
    </tr>
</table>



    </div>

    <div class="no-print inv-actions">
        <button onclick="window.print()" class="btn-print">🖨️ Cetak / Simpan PDF</button>
        <a href="{{ route('seles2.tagihan.index') }}" class="btn-back">Kembali</a>
    </div>
</div>
@endsection

@push('styles')
<style>
/* ========== BASE ========== */
* { box-sizing: border-box; }
body { font-family: Arial, sans-serif; background: #eee; margin: 0; padding: 0; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 4px; vertical-align: top; }

/* ========== EMBED MODE (iframe modal) ========== */
@if($embed)
header, nav, footer,
.navbar, .bottom-nav, .mobile-nav,
.sidebar, .app-footer, .sticky-bottom,
.no-print {
    display: none !important;
}
body { background:#f2f2f2 !important; margin:0 !important; padding:0 !important; }
@endif

/* ========== LAYOUT KERTAS ========== */
.invoice-wrap { padding: 20px; width: 100%; display: flex; flex-direction: column; align-items: center; }
.invoice-paper {
    background: #fff;
    width: 210mm;            /* A4 */
    min-height: 297mm;       /* A4 */
    padding: 15mm 20mm;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    position: relative;
    margin: 16px auto;
}

/* HEADER */
.logo-placeholder {
    width: 70px; height: 70px; border-radius: 50%;
    border: 4px solid #f3c400; color: #f3c400;
    display: flex; align-items: center; justify-content: center;
    font-weight: bold; font-size: 12px;
}
.inv-title { font-size: 18px; font-weight: 800; color: #111; text-transform: uppercase; }
.inv-sub { font-size: 13px; font-weight: 700; margin-bottom: 5px; color: #444; letter-spacing: 1px; }
.inv-meta { font-size: 11px; line-height: 1.4; color: #333; }
.inv-line { border-bottom: 3px solid #f3c400; margin: 15px 0 20px 0; }

/* INFO */
.inv-info-table { font-size: 12px; margin-bottom: 20px; }
.inv-info-table .label { font-weight: bold; margin-bottom: 5px; text-decoration: underline; }

/* TABLE */
.inv-section-title {
    text-align: center; font-weight: 800; font-size: 16px;
    margin-bottom: 10px;
}
.inv-table { border: 1px solid #000; font-size: 12px; margin-bottom: 15px; }
.inv-table th { background: #fff6b8; border: 1px solid #000; padding: 8px; text-align: center; font-weight: bold; }
.inv-table td { border: 1px solid #000; padding: 8px; vertical-align: middle; }
.center { text-align: center; }
.right { text-align: right; }
.text-muted { color: #666; font-size: 11px; }

/* TOTAL */
.terbilang-box {
    border: 2px dashed #ccc;
    padding: 10px;
    font-size: 12px;
    background: #fafafa;
    margin-right: 20px;
    border-radius: 5px;
}
.inv-total-table td { padding: 6px 10px; border: 1px solid #000; font-size: 12px; }
.inv-total-table tr.grand-total td { background: #ffe85e; font-weight: bold; font-size: 13px; }

/* BUTTONS */
.inv-actions { margin-top: 20px; width: 210mm; display: flex; justify-content: flex-end; gap: 10px; }
.btn-print { background: #198754; color: #fff; border: none; padding: 10px 20px; border-radius: 50px; cursor: pointer; font-weight: bold; }
.btn-back { background: #f8f9fa; color: #000; border: 1px solid #ccc; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; }

/* ========== PRINT CLEAN ========== */
@media print {
    @page { size: A4; margin: 10mm; }

    header, nav, footer,
    .navbar, .bottom-nav, .mobile-nav,
    .sidebar, .app-footer, .sticky-bottom,
    .no-print {
        display: none !important;
    }

    body { background: #fff !important; margin: 0 !important; padding: 0 !important; }

    .invoice-wrap { padding: 0; width: 100%; display: block; }
    .invoice-paper {
        width: 100%;
        min-height: auto;
        margin: 0;
        padding: 0;
        box-shadow: none;
        border: none;
    }

    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
@endpush
