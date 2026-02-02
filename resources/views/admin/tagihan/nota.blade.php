<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: Arial, sans-serif;
    font-size: 11px;
    color: #000;
}
table {
    width: 100%;
    border-collapse: collapse;
}
td, th {
    padding: 6px;
    vertical-align: top;
}

/* HEADER */
.header-title {
    font-size: 22px;
    font-weight: bold;
    text-align: center;
}
.header-sub {
    font-size: 15px;
    font-weight: bold;
    text-align: center;
}
.header-info {
    font-size: 12px;
    text-align: center;
    line-height: 1.4;
}
.line {
    border-bottom: 3px solid #f3c400;
    margin: 10px 0 15px;
}

/* TABLE */
.inv-table th {
    background: #fff6b8;
    border: 1px solid #000;
    font-weight: bold;
    text-align: center;
}
.inv-table td {
    border: 1px solid #000;
}
.center { text-align: center; }
.right  { text-align: right; }

/* TOTAL */
.terbilang {
    border: 2px dashed #ccc;
    padding: 8px;
}
.total td {
    border: 1px solid #000;
    font-weight: bold;
    background: #ffe85e;
}

/* WATERMARK */
.watermark {
    position: fixed;
    top: 45%;
    left: 15%;
    font-size: 90px;
    color: #d10000;
    opacity: 0.12;
    transform: rotate(-30deg);
    z-index: -1;
    font-weight: bold;
}

/* TTD */
.signature {
    margin-top: 40px;
    text-align: right;
}
.signature img {
    height: 80px;
}
.signature .name {
    margin-top: 5px;
    font-weight: bold;
}
</style>
</head>
<body>
@php
if (!function_exists('terbilang')) {
    function terbilang($angka)
    {
        $angka = abs((int)$angka);
        $bilang = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima',
            'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas'
        ];

        if ($angka < 12) {
            return $bilang[$angka];
        } elseif ($angka < 20) {
            return terbilang($angka - 10) . ' belas';
        } elseif ($angka < 100) {
            return terbilang(intval($angka / 10)) . ' puluh ' . terbilang($angka % 10);
        } elseif ($angka < 200) {
            return 'seratus ' . terbilang($angka - 100);
        } elseif ($angka < 1000) {
            return terbilang(intval($angka / 100)) . ' ratus ' . terbilang($angka % 100);
        } elseif ($angka < 2000) {
            return 'seribu ' . terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            return terbilang(intval($angka / 1000)) . ' ribu ' . terbilang($angka % 1000);
        } elseif ($angka < 1000000000) {
            return terbilang(intval($angka / 1000000)) . ' juta ' . terbilang($angka % 1000000);
        }

        return 'terlalu besar';
    }
}
@endphp
         
{{-- WATERMARK --}}
<div class="watermark">LUNAS</div>

{{-- HEADER --}}
<div class="header-title">PT. NALENDRA GIGANTARA MEDIA</div>
<div class="header-sub">INTERNET SERVICE PROVIDER</div>
<div class="header-info">
    Dsn. Kedungwulut RT/RW 003/002 Kedungwulut, Bandung, Tulungagung 66274<br>
    Telp. 0355 5351137 / 0852 8181 3088<br>
    Email: gusma@ptnalendra.id | Website: www.ptnalendra.id
</div>

<div class="line"></div>

{{-- INFO PELANGGAN --}}
<table>
<tr>
<td width="60%">
<strong>Kepada Yth:</strong><br>
{{ $pembayaran->pelanggan->nama ?? '-' }}<br>
{{ $pembayaran->pelanggan->alamat ?? '-' }}<br>
{{ $pembayaran->pelanggan->nomor_hp ?? '-' }}
</td>
<td width="40%">
No Kwitansi : {{ $pembayaran->no_pembayaran }}<br>
Tanggal     : {{ optional($pembayaran->tanggal_bayar)->format('d-m-Y') }}<br>
Jatuh Tempo : {{ optional($pembayaran->jatuh_tempo)->format('d-m-Y') }}
</td>
</tr>
</table>

<br>

{{-- TABEL TAGIHAN --}}
<table class="inv-table">
<thead>
<tr>
<th width="5%">No</th>
<th width="65%">Uraian Layanan</th>
<th width="30%">Total (Rp)</th>
</tr>
</thead>
<tbody>
@php
    $items = $pembayaran->paymentItems ?? collect();
    $total = $items->sum('nominal_bayar');
@endphp

@forelse($items as $i => $it)
<tr>
<td class="center">{{ $i + 1 }}</td>
<td>
Layanan Internet<br>
<small>
Periode:
{{ $it->tagihan->bulan ?? '-' }}/{{ $it->tagihan->tahun ?? '-' }}
</small>
</td>
<td class="right">
{{ number_format($it->nominal_bayar, 0, ',', '.') }}
</td>
</tr>
@empty
<tr>
<td colspan="3" class="center">Tidak ada item pembayaran</td>
</tr>
@endforelse
</tbody>
</table>

<br>

{{-- TOTAL --}}
<table>
<tr>
<td width="60%">
<div class="terbilang">
<strong>Terbilang:</strong><br>
<i># {{ ucfirst(terbilang($total)) }} rupiah #</i>
</div>
</td>
<td width="40%">
<table class="total">
<tr>
<td>Total Pembayaran</td>
<td class="right">Rp {{ number_format($total,0,',','.') }}</td>
</tr>
</table>
</td>
</tr>
</table>

{{-- TANDA TANGAN --}}
<div class="signature">
    Tulungagung, {{ optional($pembayaran->tanggal_bayar)->format('d M Y') }}<br><br>

    {{-- TTD + CAP --}}
    <img src="{{ public_path('img/ttd.png') }}" alt="Tanda Tangan"><br>
    <img src="{{ public_path('img/cap.png') }}" alt="Cap Perusahaan"><br>

    <div class="name">PT. Nalendra Gigantara Media</div>
</div>

</body>
</html>
