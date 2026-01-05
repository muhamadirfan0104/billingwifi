@extends('seles2.layout.master')
@section('title', 'Data Pelanggan')
@section('content')
    <div class="pelanggan-page">
        {{-- HEADER (Tema Amber) --}}
        <div class="pelanggan-header d-flex align-items-center">
            <a href="{{ route('dashboard-sales') }}" class="back-btn">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h5 class="mb-0 fw-bold">Daftar Pelanggan</h5>
        </div>
{{-- FILTER & PENCARIAN (Clean Version) --}}
<div class="filter-wrapper mt-3 px-2">
    {{-- Bar 1: Search Box --}}
    <div class="mb-2">
        <input type="text" id="search-input" class="form-control-custom" 
               placeholder="Cari nama, HP, atau alamat...">
    </div>

    {{-- Bar 2: Area & Status --}}
    <div class="row g-2">
        <div class="col-6">
            <select id="area-filter" class="form-select-custom">
                <option value="">Semua Area</option>
                @foreach ($dataArea as $a)
                    <option value="{{ $a->nama_area }}">{{ strtoupper($a->nama_area) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6">
            <select id="status-filter" class="form-select-custom">
                <option value="">Semua Status</option>
                <option value="lunas">Sudah Bayar</option>
                <option value="belum">Belum Bayar</option>
            </select>
        </div>
    </div>
</div>
<div class="px-3 mt-3">
    <div class="count-badge">
        Menampilkan <span id="count-shown">{{ $pelanggan->count() }}</span> pelanggan
        dari {{ $pelanggan->total() }} total
    </div>
</div>


        {{-- LIST PELANGGAN --}}
        <div class="pelanggan-list mt-3">
            @forelse ($pelanggan as $item)
                @php
                    $langgananAktif = $item->langganan->sortByDesc('tanggal_mulai')->first();
                    $tagihanList = $langgananAktif?->tagihan ?? collect();
                    // === STATUS GLOBAL PELANGGAN (ada tagihan belum lunas apa tidak) ===
                    $hasUnpaid = $tagihanList->contains(fn($t) => $t->status_tagihan === 'belum lunas');
                    $isLunas = !$hasUnpaid;
                    // === TAGIHAN BELUM LUNAS PALING AWAL (kalau ada) ===
                    $tagihanBelumLunas = $tagihanList
                        ->where('status_tagihan', 'belum lunas')
                        ->sortBy(fn($t) => $t->tahun * 100 + $t->bulan);
                    // TAGIHAN TERAKHIR (apapun statusnya)
                    $tagihanTerakhir = $tagihanList->sortByDesc(fn($t) => $t->tahun * 100 + $t->bulan)->first();
                    // TAGIHAN YANG DITAMPILKAN DI KARTU (KANAN):
                    // prioritas: belum lunas paling awal, kalau tidak ada pakai terakhir
                    $tagihanDisplay = $tagihanBelumLunas->first() ?? $tagihanTerakhir;
                    // === HITUNG JATUH TEMPO (SINKRON DENGAN ensureTagihanBulanIni) ===
                    $jatuhTempo = null;
                    if ($tagihanDisplay) {
                        if (!empty($tagihanDisplay->jatuh_tempo)) {
                            // kalau DB sudah punya jatuh_tempo, pakai itu
                            $jatuhTempo = \Carbon\Carbon::parse($tagihanDisplay->jatuh_tempo);
                        } else {
                            // fallback: hitung manual
                            $tahun = (int) $tagihanDisplay->tahun;
                            $bulan = (int) $tagihanDisplay->bulan;
                            // referensi hari aktivasi: tanggal_mulai langganan, kalau nggak ada pakai tanggal_registrasi
                            $refDate = $langgananAktif?->tanggal_mulai
                                ? \Carbon\Carbon::parse($langgananAktif->tanggal_mulai)
                                : \Carbon\Carbon::parse($item->tanggal_registrasi ?? now());
                            $dayAktif = $refDate->day;
                            $endOfMonthDay = \Carbon\Carbon::create($tahun, $bulan, 1)->endOfMonth()->day;
                            $dayJatuhTempo = min($dayAktif, $endOfMonthDay);
                            $jatuhTempo = \Carbon\Carbon::create($tahun, $bulan, $dayJatuhTempo, 23, 59, 59);
                        }
                    }
                    $nominalTagihan = $tagihanDisplay?->total_tagihan ?? 0;
                @endphp
                <div class="pelanggan-card position-relative {{ $isLunas ? 'card-lunas' : 'card-belum' }} mb-3"
                    data-nama="{{ $item->nama }}" data-hp="{{ $item->nomor_hp }}"
                    data-area="{{ $item->area->nama_area ?? '' }}" data-alamat="{{ $item->alamat }}"
                    data-status-bayar="{{ $isLunas ? 'lunas' : 'belum' }}">
                    {{-- SELURUH CARD BISA DIKLIK --}}
                    <a href="{{ route('seles2.pelanggan.show', $item->id_pelanggan) }}" class="stretched-link"></a>

                    <div class="row g-0 align-items-center w-100 m-0">
                        {{-- KIRI --}}
                        <div class="col-7 pe-2">
                            {{-- Nama & Area --}}
                            <div class="d-flex align-items-center mb-1">
                                <h6 class="fw-bold text-dark mb-0 text-truncate pelanggan-nama">
                                    {{ $item->nama ?? '-' }}
                                </h6>
                            </div>

                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span
                                    class="badge bg-light text-secondary border border-light small px-2 py-1 rounded-pill fw-normal text-truncate"
                                    style="max-width: 100%;">
                                    <i class="bi bi-geo-alt-fill me-1 text-warning"></i>
                                    {{ strtoupper($item->area->nama_area ?? '-') }}
                                </span>
                            </div>

                            {{-- Alamat --}}
                            <div class="small text-muted text-truncate mb-2" style="font-size: 0.8rem;">
                                {{ $item->alamat ?? 'Alamat tidak tersedia' }}
                            </div>

                            {{-- Status Badge --}}
                            <div>
                                @if ($isLunas)
                                    <span
                                        class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3">
                                        <i class="bi bi-check-circle-fill me-1"></i> Lunas
                                    </span>
                                @else
                                    <span
                                        class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3">
                                        <i class="bi bi-exclamation-circle-fill me-1"></i> Belum Bayar
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- KANAN --}}
                        <div class="col-5 ps-3 harga-col d-flex flex-column justify-content-between h-100">
                            <div class="d-flex flex-column align-items-end w-100">
                                {{-- IP Address --}}
                                @if (!empty($item->ip_address))
                                    <div class="badge bg-light text-dark border mb-1 fw-normal font-monospace"
                                        style="font-size: 0.75rem;">
                                        {{ $item->ip_address }}
                                    </div>
                                @endif

                                {{-- Jatuh Tempo --}}
                                @if ($jatuhTempo)
                                    <div class="small text-muted text-end" style="font-size: 0.75rem; line-height: 1.2;">
                                        Tgl Tagihan:<br>
                                        <span class="fw-bold text-dark">{{ $jatuhTempo->format('d M') }}</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Nominal --}}
                            <div class="text-end w-100 mt-2 pt-2 border-top border-light">
                                <div class="small text-muted mb-0" style="font-size: 0.7rem;">Harga Paket</div>
                                <div class="fw-bold text-dark" style="font-size: 1.1rem;">
                                    <span class="text-secondary small me-1" style="font-size: 0.8rem;">Rp</span>
                                    {{ number_format($nominalTagihan, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center mt-5 py-5 px-3">
                    <div class="mb-3">
                        <i class="bi bi-people text-muted" style="font-size: 3rem; opacity: 0.3;"></i>
                    </div>
                    <h6 class="text-muted fw-bold">Belum ada data pelanggan</h6>
                    <p class="text-muted small">Data pelanggan yang Anda input akan muncul di sini.</p>
                </div>
            @endforelse

            {{-- Pagination --}}
            @if (method_exists($pelanggan, 'links'))
                <div class="mt-4 px-2">
                    {{ $pelanggan->links() }}
                </div>
            @endif
        </div>

        <div class="hint-footer text-center mt-3 mb-2 mx-3 shadow-sm">
            <i class="bi bi-info-circle me-1"></i> Tekan kartu untuk melihat detail lengkap
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* Global Page Style */
        .pelanggan-page {
            background: #f9fafb;
            /* Abu-abu sangat muda bersih */
            min-height: 100vh;
            padding-bottom: 20px;
        }

        /* 1. HEADER (Gradient Kuning Emas) */
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
/* Styling Filter Tanpa Ikon */
.filter-wrapper {
    margin-top: -20px !important; 
    position: relative;
    z-index: 20;
}

.form-control-custom, 
.form-select-custom {
    width: 100%;
    height: 45px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px; /* Sudut membulat halus */
    font-size: 0.9rem;
    color: #334155;
    padding: 0 15px; /* Padding merata kiri-kanan */
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease;
    -webkit-appearance: none;
    appearance: none;
}

/* Warna saat diklik */
.form-control-custom:focus, 
.form-select-custom:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
}

/* Khusus Select: Tambahkan tanda panah minimalis manual */
.form-select-custom {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 12px;
}

/* Placeholder color */
.form-control-custom::placeholder {
    color: #94a3b8;
    font-size: 0.85rem;
}
        /* 3. CARD PELANGGAN */
        .pelanggan-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s;
            border: 1px solid #f3f4f6;
            margin-left: 4px;
            /* Space untuk border kiri */
        }

        .pelanggan-card:active {
            transform: scale(0.98);
            background-color: #fdfdfd;
        }

        /* Indikator Status (Border Kiri Tebal) */
        .card-lunas {
            border-left: 5px solid #10b981 !important;
            /* Hijau Emerald */
        }

        .card-belum {
            border-left: 5px solid #ef4444 !important;
            /* Merah */
        }

        /* Kolom Harga (Kanan) */
        .harga-col {
            border-left: 1px dashed #e5e7eb;
        }

        /* 4. FOOTER HINT */
        .hint-footer {
            background: #fffbeb;
            /* Kuning sangat muda */
            color: #d97706;
            /* Text Kuning Emas Gelap */
            padding: 10px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #fcd34d;
        }

        .stretched-link {
            position: absolute;
            inset: 0;
            z-index: 5;
        }

     
/* Styling Filter & Search */
.filter-wrapper {
    margin-top: -20px !important; /* Menaikkan filter ke atas header melengkung */
    position: relative;
    z-index: 20;
}

.form-control-custom, .form-select-custom {
    width: 100%;
    height: 45px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.85rem;
    color: #475569;
    padding-left: 40px; /* Space untuk ikon */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.2s;
}

.form-control-custom:focus, .form-select-custom:focus {
    outline: none;
    border-color: #f59e0b;
    box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
}

/* Container untuk Ikon */
.search-container, .select-container {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon, .select-icon {
    position: absolute;
    left: 15px;
    color: #94a3b8;
    font-size: 1rem;
    z-index: 5;
}

/* Custom Dropdown Arrow */
.form-select-custom {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
}

.count-badge {
    background: #f1f5f9;
    color: #64748b;
    padding: 6px 14px;
    border-radius: 8px;
    display: inline-block;
    font-size: 0.75rem;
}
    </style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const statusFilter = document.getElementById('status-filter');
    const areaFilter = document.getElementById('area-filter');
    const cards = document.querySelectorAll('.pelanggan-card');
    const countShown = document.getElementById('count-shown');

    function applyFilter() {
        const q = (searchInput?.value || '').toLowerCase();
        const status = (statusFilter?.value || '').toLowerCase(); // '' | 'lunas' | 'belum'
        const areaSelected = (areaFilter?.value || '').toLowerCase();

        let shown = 0;

        cards.forEach(card => {
            const nama = (card.dataset.nama || '').toLowerCase();
            const hp = (card.dataset.hp || '').toLowerCase();
            const area = (card.dataset.area || '').toLowerCase();
            const alamat = (card.dataset.alamat || '').toLowerCase();
            const sBayar = (card.dataset.statusBayar || '').toLowerCase();

            const textMatch = !q ||
                nama.includes(q) ||
                hp.includes(q) ||
                area.includes(q) ||
                alamat.includes(q);

            const statusMatch = !status || sBayar === status;
            const areaMatch = !areaSelected || area === areaSelected;

            const ok = textMatch && statusMatch && areaMatch;
            card.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });

        if (countShown) countShown.textContent = shown;
    }

    searchInput?.addEventListener('input', applyFilter);
    statusFilter?.addEventListener('change', applyFilter);
    areaFilter?.addEventListener('change', applyFilter);
});
</script>

@endpush
