@extends('seles2.layout.master')

@section('content')
<div class="pelanggan-page">

    {{-- HEADER --}}
    <div class="pelanggan-header d-flex align-items-center">
        <a href="{{ route('dashboard-sales') }}" class="back-btn me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">Status Pelanggan</h5>
    </div>

    @php
        $statusPage    = $status ?? request('status', 'aktif');
        $bulanSelected = (int) request('bulan', $bulan ?? now()->month);
        $tahunSelected = (int) request('tahun', $tahun ?? now()->year);
    @endphp

    {{-- TAB STATUS --}}
    <div class="status-tabs-container px-1">
        <div class="status-tabs d-flex gap-2 overflow-auto pb-2 px-2">
            @foreach(['baru', 'aktif', 'isolir', 'berhenti'] as $st)
                <a href="{{ route('seles2.pelanggan.status', array_filter([
                        'status' => $st,
                        // kalau klik tab "baru", bawa bulan/tahun biar tetap
                        'bulan'  => ($st === 'baru') ? $bulanSelected : null,
                        'tahun'  => ($st === 'baru') ? $tahunSelected : null,
                    ])) }}"
                    class="status-tab {{ $statusPage === $st ? 'active' : '' }}">
                    {{ ucfirst($st) }}
                    <span class="badge-count">{{ $statusCounts[$st] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="filters mt-3 px-2">
        {{-- Row 1: Search + Area --}}
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="search-input" class="form-control-custom" placeholder="Cari nama / alamat / hp / ip...">
                </div>
            </div>

            <div class="col-auto">
                <select id="area-filter" class="form-select-custom">
                    <option value="">Semua Wilayah</option>
                    @foreach ($dataArea ?? [] as $a)
                        <option value="{{ strtolower($a->nama_area) }}">{{ strtoupper($a->nama_area) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Row 2: Bulan + Tahun (khusus tab baru) --}}
        @if ($statusPage === 'baru')
            <div class="row g-2 align-items-center mt-2">
                <div class="col">
                    <select id="bulan-filter" class="form-select-custom">
                        @for ($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}" {{ $bulanSelected === $m ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div class="col">
                    <select id="tahun-filter" class="form-select-custom">
                        @for ($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" {{ $tahunSelected === $y ? 'selected' : '' }}>
                                {{ $y }}
                            </option>
                        @endfor
                    </select>
                </div>
            </div>
        @endif
    </div>

    {{-- LIST --}}
    <div class="pelanggan-list mt-3 px-2">
        @forelse ($pelanggan as $p)
            @php
                $rawStatus      = $p->status_pelanggan;
                $statusBadge    = $p->status_pelanggan_efektif ?? $rawStatus;
                $langgananAktif = $p->langganan->sortByDesc('tanggal_mulai')->first();

                $themeColor = match ($statusBadge) {
                    'baru'     => ['class' => 'border-baru',     'bg' => 'bg-warning'],
                    'aktif'    => ['class' => 'border-aktif',    'bg' => 'bg-success'],
                    'isolir'   => ['class' => 'border-isolir',   'bg' => 'bg-secondary'],
                    'berhenti' => ['class' => 'border-berhenti', 'bg' => 'bg-danger'],
                    default    => ['class' => '',               'bg' => 'bg-light'],
                };

                $tanggalKolom = match ($rawStatus) {
                    'baru'     => $p->tanggal_registrasi,
                    'aktif'    => $langgananAktif?->tanggal_mulai,
                    'isolir'   => $langgananAktif?->tanggal_isolir,
                    'berhenti' => $langgananAktif?->tanggal_berhenti,
                    default    => null
                };

                $labelTanggal = match ($rawStatus) {
                    'baru'     => 'Registrasi',
                    'aktif'    => 'Aktif',
                    'isolir'   => 'Isolir',
                    'berhenti' => 'Berhenti',
                    default    => 'Tanggal',
                };
            @endphp

            <div class="pelanggan-card position-relative {{ $themeColor['class'] }} mb-3"
                data-nama="{{ strtolower($p->nama ?? '') }}"
                data-hp="{{ strtolower($p->nomor_hp ?? '') }}"
                data-alamat="{{ strtolower($p->alamat ?? '') }}"
                data-area="{{ strtolower($p->area->nama_area ?? '') }}"
                data-ip="{{ strtolower($p->ip_address ?? '') }}"
                data-status="{{ strtolower($statusBadge ?? '') }}"
            >
                <a href="{{ route('seles2.pelanggan.show', $p->id_pelanggan) }}" class="stretched-link"></a>

                <div class="row g-0 align-items-center w-100 m-0">
                    {{-- KIRI --}}
                    <div class="col-7 pe-2">
                        <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $p->nama ?? '-' }}</h6>

                        <div class="d-flex align-items-center gap-1 mb-1">
                            <span class="badge-area">
                                <i class="bi bi-geo-alt-fill me-1 text-warning"></i>
                                {{ strtoupper($p->area->nama_area ?? '-') }}
                            </span>
                        </div>

                        <div class="mb-2">
                            <span class="badge {{ $themeColor['bg'] }} bg-opacity-10 {{ str_replace('bg-', 'text-', $themeColor['bg']) }} border rounded-pill px-2 py-1"
                                  style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                {{ strtoupper($statusBadge) }}
                            </span>
                        </div>

                        <div class="small text-muted text-truncate mb-0" style="font-size: 0.75rem;">
                            {{ $p->alamat ?? 'Alamat tidak tersedia' }}
                        </div>
                    </div>

                    {{-- KANAN --}}
                    <div class="col-5 ps-3 detail-col d-flex flex-column justify-content-between h-100">
                        <div class="d-flex flex-column align-items-end w-100">
                            @if (!empty($p->ip_address))
                                <div class="ip-badge mb-1">{{ $p->ip_address }}</div>
                            @endif

                            @if ($tanggalKolom)
                                <div class="date-info text-end text-dark">
                                    {{ $labelTanggal }}:<br>
                                    <span class="fw-bold text-dark">
                                        {{ \Carbon\Carbon::parse($tanggalKolom)->translatedFormat('d M Y') }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="price-section text-end w-100 mt-2 pt-2 border-top">
                            <div class="price-label text-dark">Harga Paket</div>
                            <div class="price-value">
                                <span class="currency">Rp</span>{{ number_format($langgananAktif->paket->harga_total ?? 0, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        @empty
            <div class="text-center mt-5 py-5 text-muted">
                <i class="bi bi-person-x" style="font-size: 3rem; opacity: 0.2;"></i>
                <p class="mt-2">Tidak ada data untuk status ini.</p>
            </div>
        @endforelse
    </div>

</div>
@endsection

@push('styles')
<style>
    .pelanggan-page { background: #f9fafb; min-height: 100vh; padding-bottom: 20px; }

    .pelanggan-header {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white; padding: 20px 20px 35px 20px;
        border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;
        margin: -16px -16px 0 -16px;
    }

    .back-btn {
        background: rgba(255,255,255,0.2); width: 35px; height: 35px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; color: white; text-decoration: none;
    }

    /* Tabs */
    .status-tabs-container { margin-top: -20px; position: relative; z-index: 11; }
    .status-tabs::-webkit-scrollbar { display: none; }
    .status-tab {
        white-space: nowrap;
        padding: 9px 14px;
        border-radius: 22px;
        font-size: 0.85rem;
        background: white;
        color: #6b7280;
        text-decoration: none;
        border: 1px solid #f3f4f6;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 38px;
    }
    .status-tab.active { background: #d97706; color: white; border-color: #d97706; }
    .badge-count {
        background: rgba(0,0,0,0.06);
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1;
    }
    .status-tab.active .badge-count { background: rgba(255,255,255,0.25); }

    /* Cards */
    .pelanggan-card {
        background: white;
        border-radius: 16px;
        padding: 16px;
        border: 1px solid #f3f4f6;
        box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        transition: transform 0.15s ease;
    }
    .pelanggan-card:active { transform: scale(0.985); }

    .border-aktif { border-left: 5px solid #10b981 !important; }
    .border-baru { border-left: 5px solid #f59e0b !important; }
    .border-isolir { border-left: 5px solid #6b7280 !important; }
    .border-berhenti { border-left: 5px solid #ef4444 !important; }

    /* Small components */
    .badge-area {
        background: #f8fafc;
        color: #64748b;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        border: 1px solid #f1f5f9;
    }
    .detail-col { border-left: 1px dashed #e5e7eb; }
    .ip-badge { font-family: monospace; font-size: 0.7rem; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 6px; }
    .date-info { font-size: 0.7rem; color: #94a3b8; line-height: 1.2; }
    .price-label { font-size: 0.65rem; color: #94a3b8; margin-bottom: -2px; }
    .price-value { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
    .price-value .currency { font-size: 0.8rem; color: #64748b; margin-right: 2px; }

    /* Filters */
    .filters { position: relative; z-index: 10; }
    .search-box { position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 15px; color: #94a3b8; font-size: 0.9rem; }

    .form-control-custom {
        width: 100%;
        height: 42px;
        padding: 10px 15px 10px 40px;
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
        height: 42px;
        padding: 10px 32px 10px 14px;
        font-size: 0.85rem;
        background-color: white;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        color: #64748b;
        cursor: pointer;
        transition: all 0.2s ease;
        appearance: none;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 12px;
        min-width: 140px;
    }
    .form-select-custom:focus { outline: none; border-color: #f59e0b; color: #1e293b; }

    .btn-reset-filter{
        display: inline-flex;
        align-items: center;
        height: 42px;
        padding: 0 14px;
        border-radius: 12px;
        text-decoration: none;
        font-size: 0.85rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .btn-reset-filter:active{ transform: scale(0.98); }

    /* Empty state animation */
    .text-center.mt-5 { animation: fadeIn 0.5s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-input');
    const areaFilter  = document.getElementById('area-filter');
    const cards       = document.querySelectorAll('.pelanggan-card');

    function applyFilter() {
        const q    = (searchInput?.value || '').toLowerCase().trim();
        const area = (areaFilter?.value || '').toLowerCase();

        cards.forEach(card => {
            const nama   = card.dataset.nama   || '';
            const hp     = card.dataset.hp     || '';
            const alamat = card.dataset.alamat || '';
            const areaCd = card.dataset.area   || '';
            const ip     = card.dataset.ip     || '';
            const status = card.dataset.status || '';

            const textMatch = !q ||
                nama.includes(q) ||
                hp.includes(q) ||
                alamat.includes(q) ||
                areaCd.includes(q) ||
                ip.includes(q) ||
                status.includes(q);

            const areaMatch = !area || areaCd === area;

            card.style.display = (textMatch && areaMatch) ? '' : 'none';
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilter);
    if (areaFilter)  areaFilter.addEventListener('change', applyFilter);

    // Bulan/Tahun khusus status "baru" -> reload page + bawa query
    const bulanFilter = document.getElementById('bulan-filter');
    const tahunFilter = document.getElementById('tahun-filter');

    function updateBulanTahun() {
        if (!bulanFilter || !tahunFilter) return;

        const url = new URL(window.location.href);
        url.searchParams.set('status', 'baru');
        url.searchParams.set('bulan', bulanFilter.value);
        url.searchParams.set('tahun', tahunFilter.value);
        url.searchParams.delete('page'); // reset pagination
        window.location.href = url.toString();
    }

    if (bulanFilter) bulanFilter.addEventListener('change', updateBulanTahun);
    if (tahunFilter) tahunFilter.addEventListener('change', updateBulanTahun);
});
</script>
@endpush
