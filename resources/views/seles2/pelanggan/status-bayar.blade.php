@extends('seles2.layout.master')
@section('title', 'Status Pembayaran')
@section('content')

    <div class="pelanggan-page">

{{-- HEADER (Tema Amber - Diperbesar) --}}
<div class="pelanggan-header px-3">
    <div class="d-flex align-items-center justify-content-between ">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('dashboard-sales') }}" class="back-btn">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h4 class="mb-0 fw-bold text-white">Status Pembayaran</h4>

            </div>
        </div>
    </div>
</div>

        @php
            $statusPage = $statusBayar ?? request('status_bayar', 'belum'); // 'belum' / 'lunas'
        @endphp
{{-- FILTER & SEARCH (Versi Rapih) --}}
<div class="filter-wrapper mt-3 px-2">
    <div class="row g-2 align-items-center">
        {{-- Search Input --}}
        <div class="col">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="search-input" class="form-control-custom" placeholder="Cari nama, HP, alamat...">
            </div>
        </div>
        {{-- Area Filter --}}
        <div class="col-auto">
            <div class="filter-box">
                <select id="area-filter" class="form-select-custom">
                    <option value="">Semua Wilayah</option>
                    @foreach ($dataArea ?? [] as $area)
                        <option value="{{ strtolower($area->nama_area) }}">
                            {{ strtoupper($area->nama_area) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@php
    $currentSort = request('sort', 'tunggakan_desc');
    $nextSort = ($currentSort === 'tunggakan_desc') ? 'tunggakan_asc' : 'tunggakan_desc';
@endphp

@if ($statusPage === 'belum')
    <div class="px-3 mt-2">
        <a href="{{ request()->fullUrlWithQuery(['sort' => $nextSort]) }}"
           class="btn btn-sm btn-outline-warning rounded-pill w-100 shadow-sm">
            <i class="bi bi-sort-down me-1"></i>
            Urutkan Tunggakan
            @if($currentSort === 'tunggakan_desc')
                (Terbesar → Terkecil)
            @else
                (Terkecil → Terbesar)
            @endif
        </a>
    </div>
@endif

        {{-- LIST PELANGGAN --}}
        <div class="pelanggan-list mt-3 pb-3">
            @forelse ($pelanggan as $item)
                @php
                    // === LOGIKA PHP ASLI (TIDAK DIUBAH) ===
                    $langgananAktif = $item->langganan->sortByDesc('tanggal_mulai')->first();
                    $areaName = $item->area->nama_area ?? '-';

                    // Semua tagihan pelanggan
                    $semuaTagihan = $item->langganan->flatMap(fn($l) => $l->tagihan);

                    // TUNGGAKAN
                    $tunggakan = $semuaTagihan
                        ->where('status_tagihan', 'belum lunas')
                        ->sortBy(fn($t) => $t->tahun * 100 + $t->bulan);

                    // TAGIHAN LUNAS TERAKHIR
                    $tagihanLunasTerakhir = $semuaTagihan
                        ->whereIn('status_tagihan', ['lunas', 'sudah lunas'])
                        ->sortByDesc(fn($t) => $t->tahun * 100 + $t->bulan)
                        ->first();

// Semua tagihan pelanggan
$semuaTagihan = $item->langganan->flatMap(fn($l) => $l->tagihan);

// TUNGGAKAN (semua belum lunas)
$tunggakan = $semuaTagihan
    ->where('status_tagihan', 'belum lunas')
    ->sortBy(fn($t) => $t->tahun * 100 + $t->bulan);

// TOTAL tunggakan (ini yang kamu mau!)
$totalTunggakan  = (int) $tunggakan->sum('total_tagihan');
$jumlahTunggakan = (int) $tunggakan->count();

// TAGIHAN LUNAS TERAKHIR
$tagihanLunasTerakhir = $semuaTagihan
    ->whereIn('status_tagihan', ['lunas', 'sudah lunas'])
    ->sortByDesc(fn($t) => $t->tahun * 100 + $t->bulan)
    ->first();

// TAGIHAN TERBARU (buat fallback)
$tagihanTerbaru = $semuaTagihan
    ->sortByDesc(fn($t) => $t->tahun * 100 + $t->bulan)
    ->first();

// ==== TAGIHAN DISPLAY + WARNA KARTU ====
if ($statusPage === 'belum') {
    // kalau belum bayar → ambil tagihan tunggakan paling tua buat jatuh tempo
    $tagihanDisplay = $tunggakan->first() ?? $tagihanTerbaru;
    $cardClass = 'card-belum';

    $nominalTampil = $jumlahTunggakan > 0 ? $totalTunggakan : null; // ✅ total tunggakan
    $labelNominal  = 'Total Tunggakan';
} else {
    // lunas → tetap ambil tagihan untuk keperluan jatuh tempo/info lain (kalau mau),
    // tapi nominal yang ditampilkan harus 0
    $tagihanDisplay = $tagihanLunasTerakhir ?? $tagihanTerbaru;
    $cardClass = 'card-lunas';

    $nominalTampil = 0;          // ✅ FIX: sudah bayar = 0
    $labelNominal  = 'Total Tagihan';
}


$nominalTagihanDisplay = $tagihanDisplay ? (int) $tagihanDisplay->total_tagihan : null;
$totalTunggakan = (int) $tunggakan->sum('total_tagihan'); // total semua belum lunas
$jumlahTunggakan = (int) $tunggakan->count();             // berapa bulan/tagihan

                    // ==== HITUNG JATUH TEMPO ====
                    $jatuhTempoDisplay = null;
                    if ($tagihanDisplay) {
                        if (!empty($tagihanDisplay->jatuh_tempo)) {
                            $jatuhTempoDisplay = \Carbon\Carbon::parse($tagihanDisplay->jatuh_tempo);
                        } else {
                            $tahun = (int) $tagihanDisplay->tahun;
                            $bulan = (int) $tagihanDisplay->bulan;

                            $refDate = $langgananAktif?->tanggal_mulai
                                ? \Carbon\Carbon::parse($langgananAktif->tanggal_mulai)
                                : \Carbon\Carbon::parse($item->tanggal_registrasi ?? now());

                            $dayAktif = $refDate->day;
                            $endOfMonthDay = \Carbon\Carbon::create($tahun, $bulan, 1)->endOfMonth()->day;
                            $dayJatuhTempo = min($dayAktif, $endOfMonthDay);

                            $jatuhTempoDisplay = \Carbon\Carbon::create($tahun, $bulan, $dayJatuhTempo);
                        }
                    }

                    $modalId = 'modalPembayaran-' . $item->id_pelanggan;
                @endphp

                {{-- KARTU PELANGGAN --}}
                <div class="pelanggan-card position-relative {{ $cardClass }} mb-3"
                    data-nama="{{ strtolower($item->nama ?? '') }}" data-hp="{{ strtolower($item->nomor_hp ?? '') }}"
                    data-area="{{ strtolower($areaName) }}" data-alamat="{{ strtolower($item->alamat ?? '') }}"
                    data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                    <div class="row g-0 align-items-center w-100 m-0">
                        {{-- KIRI --}}
                        <div class="col-7 pe-2">
                            {{-- Nama --}}
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold text-dark mb-0 text-truncate pelanggan-nama">
                                    {{ $item->nama ?? '-' }}
                                </h6>
                            </div>

                            {{-- Area --}}
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span
                                    class="badge bg-light text-secondary border border-light small px-2 py-1 rounded-pill fw-normal text-truncate"
                                    style="max-width: 100%;">
                                    <i class="bi bi-geo-alt-fill me-1 text-warning"></i>
                                    {{ strtoupper($areaName) }}
                                </span>
                            </div>

                            {{-- Alamat --}}
                            <div class="small text-muted text-truncate mb-2" style="font-size: 0.75rem;">
                                {{ $item->alamat ?? 'Alamat tidak tersedia' }}
                            </div>

                            {{-- Status Text --}}
                            <div class="mt-2 small">
                                @if ($statusPage === 'lunas')
                                    <span
                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2">
                                        <i class="bi bi-check-circle-fill me-1"></i> SUDAH BAYAR
                                    </span>
                                @else
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-2">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i> BELUM BAYAR
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- KANAN --}}
                        <div class="col-5 ps-3 harga-col d-flex flex-column justify-content-between h-100">
                            <div class="d-flex flex-column align-items-end w-100">
                                {{-- IP --}}
                                @if (!empty($item->ip_address))
                                    <div class="badge bg-light text-dark border mb-1 fw-normal font-monospace"
                                        style="font-size: 0.7rem;">
                                        {{ $item->ip_address }}
                                    </div>
                                @endif

                                {{-- Jatuh Tempo --}}
                                @if ($jatuhTempoDisplay)
                                    <div class="small text-muted text-end" style="font-size: 0.7rem; line-height: 1.2;">
                                        Jatuh Tempo:<br>
                                        <span class="fw-bold text-dark">{{ $jatuhTempoDisplay->format('d M Y') }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Nominal --}}
                            <div class="text-end w-100 mt-2 pt-2 border-top border-light">
<div class="small text-muted mb-0" style="font-size: 0.7rem;">
    {{ $labelNominal }}
    @if($statusPage === 'belum' && $jumlahTunggakan > 0)
        <span class="text-muted">({{ $jumlahTunggakan }} bln)</span>
    @endif
</div>

@if(!is_null($nominalTampil))
    <span class="fw-bold text-dark" style="font-size: 1.1rem;">
        <span class="text-secondary small me-1" style="font-size: 0.8rem;">Rp</span>
        {{ number_format($nominalTampil, 0, ',', '.') }}
    </span>
@else
    <span class="badge bg-light text-muted border rounded-pill px-2">Belum ada tagihan</span>
@endif


                            </div>

                        </div>
                    </div>
                </div>

                {{-- MODAL DETAIL PEMBAYARAN (Modern Style) --}}
                <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Info Pembayaran</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>

                            <div class="modal-body pt-2">
                                <h6 class="text-primary fw-bold mb-3">{{ $item->nama }}</h6>

                                @if ($statusPage === 'lunas')
                                    {{-- KONTEN SUDAH BAYAR --}}
                                    @if ($tagihanLunasTerakhir)
                                        @php
                                            $periode = \Carbon\Carbon::create(
                                                $tagihanLunasTerakhir->tahun,
                                                $tagihanLunasTerakhir->bulan,
                                                1,
                                            )
                                                ->locale('id')
                                                ->translatedFormat('F Y');
                                        @endphp

                                        <div
                                            class="alert alert-success bg-success bg-opacity-10 border-success border-opacity-25 rounded-3">
                                            <div class="small text-muted mb-1">Status Lunas Sampai:</div>
                                            <h4 class="fw-bold text-success mb-0">{{ $periode }}</h4>
                                        </div>
                                        <p class="text-muted small">
                                            Tagihan sampai bulan tersebut sudah beres. Cek detail di halaman pelanggan.
                                        </p>
                                    @else
                                        <p class="text-muted small">Data tagihan lunas terakhir tidak ditemukan.</p>
                                    @endif
                                @else
                                    {{-- KONTEN BELUM BAYAR (TUNGGAKAN) --}}
                                    @if ($tunggakan->isNotEmpty())
                                        <p class="mb-2 fw-bold text-danger">Daftar Tunggakan:</p>
                                        <div class="table-responsive border rounded-3">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="ps-3">Periode</th>
                                                        <th class="text-end pe-3">Nominal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($tunggakan as $tg)
                                                        @php
                                                            $periodeTg = \Carbon\Carbon::create(
                                                                $tg->tahun,
                                                                $tg->bulan,
                                                                1,
                                                            )
                                                                ->locale('id')
                                                                ->translatedFormat('F Y');
                                                        @endphp
                                                        <tr>
                                                            <td class="ps-3">{{ $periodeTg }}</td>
                                                            <td class="text-end pe-3 fw-semibold">
                                                                Rp {{ number_format($tg->total_tagihan, 0, ',', '.') }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        <p class="mt-2 mb-0 text-muted small fst-italic">
                                            Mohon segera ditindaklanjuti.
                                        </p>
                                    @else
                                        <p class="text-muted mb-0 small">
                                            Tidak ditemukan data tunggakan spesifik.
                                        </p>
                                    @endif
                                @endif
                            </div>


<div class="modal-footer border-0 pt-0 pb-3">
    @if ($statusPage === 'belum' && $tunggakan->isNotEmpty())
        @php
            // Nomor WA pelanggan
            $waNumber = $item->nomor_hp ?? null;

            // Normalisasi nomor
            $waLink = null;

            // Ambil periode tunggakan (contoh: September 2025, Oktober 2025, dst)
            $listPeriode = $tunggakan->map(function($tg){
                return \Carbon\Carbon::create($tg->tahun, $tg->bulan, 1)
                    ->locale('id')->translatedFormat('F Y');
            })->values()->toArray();

            $periodePertama = $listPeriode[0] ?? null;
            $periodeTerakhir = $listPeriode[count($listPeriode)-1] ?? null;

            // Buat teks periode ringkas
            if (count($listPeriode) === 1) {
                $periodeText = $periodePertama;
            } else {
                $periodeText = $periodePertama . ' s.d ' . $periodeTerakhir;
            }

            // Total tunggakan (sudah kamu hitung)
            $totalTunggakanText = 'Rp ' . number_format((int)$totalTunggakan, 0, ',', '.');

            // Batas bayar: pakai jatuh tempo display (tagihan paling tua), fallback 2 hari dari sekarang
            $batasBayar = $jatuhTempoDisplay
                ? $jatuhTempoDisplay->locale('id')->translatedFormat('d F Y')
                : now()->addDays(2)->locale('id')->translatedFormat('d F Y');

            // Link detail pelanggan (opsional)
            $detailUrl = route('seles2.pelanggan.show', $item->id_pelanggan);
    // Pesan WhatsApp tagihan BELUM LUNAS
    $msg =
"Assalamu’alaikum Bapak/Ibu/Sdr {$item->nama},

Kami informasikan bahwa tagihan layanan internet Anda saat ini masih *BELUM LUNAS*.

• Periode tunggakan: *{$periodeText}* (" . count($listPeriode) . " bulan)
• Total tagihan: *{$totalTunggakanText}*
• Batas pembayaran: *24 jam setelah pesan ini (jika tidak ada kejelasan)*

Mohon segera dilakukan pembayaran untuk menghindari isolir/putus layanan.
Terima kasih. 🙏";

    if (!empty($waNumber)) {
        $digits = preg_replace('/[^0-9]/', '', $waNumber);

        // normalisasi 08xxxx → 62xxxx
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        $waLink = "https://wa.me/" . $digits . "?text=" . urlencode($msg);
    }
@endphp

 @if ($waLink)
                <a target="_blank" href="{{ $waLink }}"
                   class="btn btn-outline-success rounded-pill fw-bold">
                    <i class="bi bi-whatsapp"></i> Kirim WA Tagihan
                </a>
            @else
                <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold" disabled>
                    <i class="bi bi-whatsapp"></i> Nomor WA tidak tersedia
                </button>
            @endif
        @endif

        <a href="{{ route('seles2.pelanggan.show', $item->id_pelanggan) }}"
           class="btn btn-primary rounded-pill fw-bold shadow-sm">
            Detail Pelanggan <i class="bi bi-arrow-right ms-1"></i>
        </a>

        <button type="button"
                class="btn btn-light rounded-pill fw-bold text-secondary"
                data-bs-dismiss="modal">
            Tutup
        </button>
</div>

                        </div>
                    </div>
                </div>

            @empty
                <div class="text-center mt-5 py-5 px-3">
                    <div class="mb-3">
                        <i class="bi bi-filter-circle text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                    <h6 class="text-muted fw-bold">Tidak ada data</h6>
                    <p class="text-muted small">Tidak ada pelanggan pada status pembayaran ini.</p>
                </div>
            @endforelse

            {{-- PAGINATION --}}
            @if (method_exists($pelanggan, 'links'))
                <div class="mt-4 px-2">
                    {{ $pelanggan->links() }}
                </div>
            @endif
        </div>

        <div class="hint-footer text-center mt-3 mb-2 mx-3 shadow-sm">
            <i class="bi bi-hand-index-thumb me-1"></i> Tap kartu untuk info pembayaran
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Global Page Style */
        .pelanggan-page {
            background: #f9fafb;
            min-height: 100vh;
            padding-bottom: 90px;
        }

        /* 1. HEADER (Gradient Amber) */
        .pelanggan-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 20px 20px 30px 20px;
            /* Padding bawah lebih besar untuk efek lengkung */
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 4px 20px rgba(245, 158, 11, 0.25);
            margin: -16px -16px 10px -16px;
            /* Negatif margin agar full width */
            gap: 12px;
            position: relative;
            z-index: 10;
        }

        .back-btn {
            color: white;
            text-decoration: none;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            justify-content: center;
            transition: 0.2s;
        }

        .back-btn:active {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(0.9);
        }

        /* 2. FILTER BAR */
        .filter-bar {
            position: relative;
            z-index: 11;
            margin-top: 10px !important;
            padding: 0 16px;
        }

        .filter-bar input,
        .filter-bar select,
        .input-group-text {
            border: 1px solid #f3f4f6;
            height: 40px;
            font-size: 0.85rem;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        /* 3. CARD PELANGGAN */
        .pelanggan-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #f3f4f6;
            transition: transform 0.2s;
            cursor: pointer;
        }

        .pelanggan-card:active {
            transform: scale(0.98);
            background-color: #fcfcfc;
        }

        /* Indikator Garis Tepi (Penting untuk Status) */
        .card-lunas {
            border-left: 5px solid #10b981 !important;
            /* Hijau */
        }

        .card-belum {
            border-left: 5px solid #ef4444 !important;
            /* Merah */
        }

        .harga-col {
            border-left: 1px dashed #e5e7eb;
        }

        /* 4. FOOTER HINT */
        .hint-footer {
            background: #fffbeb;
            color: #d97706;
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid #fcd34d;
        }


/* Styling Search & Filter Modern */
.filter-wrapper {
    position: relative;
    z-index: 100;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 14px;
    color: #94a3b8;
    font-size: 0.85rem;
}

.form-control-custom {
    width: 100%;
    padding: 9px 12px 9px 38px; /* Padding kiri lebih besar untuk ikon */
    font-size: 0.85rem;
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    color: #1e293b;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}

.form-control-custom:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

.form-select-custom {
    padding: 9px 30px 9px 12px;
    font-size: 0.85rem;
    background-color: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    appearance: none;
    /* Custom Arrow Icon */
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 10px;
    min-width: 130px;
}

.form-select-custom:focus {
    outline: none;
    border-color: #f59e0b;
    color: #1e293b;
}
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const areaFilter = document.getElementById('area-filter');
            const cards = document.querySelectorAll('.pelanggan-card');

            function applyFilter() {
                const q = (searchInput.value || '').toLowerCase();
                const area = (areaFilter.value || '').toLowerCase();

                cards.forEach(card => {
                    const nama = card.dataset.nama || '';
                    const hp = card.dataset.hp || '';
                    const alamat = card.dataset.alamat || '';
                    const areaCd = card.dataset.area || '';

                    const textMatch = !q ||
                        nama.includes(q) ||
                        hp.includes(q) ||
                        alamat.includes(q) ||
                        areaCd.includes(q);

                    const areaMatch = !area || areaCd === area;

                    card.style.display = (textMatch && areaMatch) ? '' : 'none';
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', applyFilter);
            }
            if (areaFilter) {
                areaFilter.addEventListener('change', applyFilter);
            }
        });
    </script>
@endpush
