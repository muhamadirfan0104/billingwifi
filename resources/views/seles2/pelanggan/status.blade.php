@extends('seles2.layout.master')
@section('title', 'Status Pelanggan')
@section('content')
<div class="pelanggan-page">

    {{-- HEADER (Gaya Index) --}}
    <div class="pelanggan-header d-flex align-items-center">
        <a href="{{ route('dashboard-sales') }}" class="back-btn me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h5 class="mb-0 fw-bold">Status Pelanggan</h5>
    </div>

    {{-- TAB STATUS (Logika Status Lama) --}}
    @php
        $statusPage = $status ?? request('status', 'aktif');
    @endphp
    <div class="status-tabs-container px-1">
        <div class="status-tabs d-flex gap-2 overflow-auto pb-2 px-2">
            @foreach(['baru', 'aktif', 'isolir', 'berhenti'] as $st)
                <a href="{{ route('seles2.pelanggan.status', ['status' => $st]) }}"
                    class="status-tab {{ $statusPage === $st ? 'active' : '' }}">
                    {{ ucfirst($st) }}
                    <span class="badge-count">{{ $statusCounts[$st] ?? 0 }}</span>
                </a>
            @endforeach
        </div>
    </div>
{{-- SEARCH & AREA (Optimized) --}}
<div class="filter-wrapper mt-3 px-2">
    <div class="row g-2 align-items-center">
        {{-- Search Input --}}
        <div class="col">
            <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="search-input" class="form-control-custom" placeholder="Cari nama atau alamat...">
            </div>
        </div>
        {{-- Area Filter --}}
        <div class="col-auto">
            <div class="filter-box">
                <select id="area-filter" class="form-select-custom">
                    <option value="">Semua Wilayah</option>
                    @foreach ($dataArea ?? [] as $a)
                        <option value="{{ strtolower($a->nama_area) }}">{{ strtoupper($a->nama_area) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

    {{-- LIST PELANGGAN --}}
    <div class="pelanggan-list mt-3">
        @forelse ($pelanggan as $p)
            @php
                $rawStatus = $p->status_pelanggan;
                $statusBadge = $p->status_pelanggan_efektif ?? $rawStatus;
                $langgananAktif = $p->langganan->sortByDesc('tanggal_mulai')->first();

                // Warna Border & Badge Berdasarkan Status
                $themeColor = match ($statusBadge) {
                    'baru' => ['hex' => '#f59e0b', 'class' => 'border-baru', 'bg' => 'bg-warning'],
                    'aktif' => ['hex' => '#10b981', 'class' => 'border-aktif', 'bg' => 'bg-success'],
                    'isolir' => ['hex' => '#6b7280', 'class' => 'border-isolir', 'bg' => 'bg-secondary'],
                    'berhenti' => ['hex' => '#ef4444', 'class' => 'border-berhenti', 'bg' => 'bg-danger'],
                    default => ['hex' => '#e5e7eb', 'class' => '', 'bg' => 'bg-light'],
                };

                $tanggalKolom = match ($rawStatus) {
                    'baru' => $p->tanggal_registrasi,
                    'aktif' => $langgananAktif?->tanggal_mulai,
                    'isolir' => $langgananAktif?->tanggal_isolir,
                    'berhenti' => $langgananAktif?->tanggal_berhenti,
                    default => null
                };

                $labelTanggal = match ($rawStatus) {
                    'baru' => 'Registrasi',
                    'aktif' => 'Aktif',
                    'isolir' => 'Isolir',
                    'berhenti' => 'Berhenti',
                    default => 'Tanggal',
                };
            @endphp

            <div class="pelanggan-card position-relative {{ $themeColor['class'] }} mb-3"
                data-nama="{{ strtolower($p->nama ?? '') }}"
                data-area="{{ strtolower($p->area->nama_area ?? '') }}">
                
                <a href="{{ route('seles2.pelanggan.show', $p->id_pelanggan) }}" class="stretched-link"></a>

                <div class="row g-0 align-items-center w-100 m-0">
                    {{-- KIRI: Info Pelanggan --}}
                    <div class="col-7 pe-2">
                        <h6 class="fw-bold text-dark mb-1 text-truncate">{{ $p->nama ?? '-' }}</h6>
                        
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <span class="badge-area">
                                <i class="bi bi-geo-alt-fill me-1 text-warning"></i>
                                {{ strtoupper($p->area->nama_area ?? '-') }}
                            </span>
                        </div>

                        {{-- Label Status (Menyesuaikan permintaan: Status tidak hilang) --}}
                        <div class="mb-2">
                            <span class="badge {{ $themeColor['bg'] }} bg-opacity-10 {{ str_replace('bg-', 'text-', $themeColor['bg']) }} border rounded-pill px-2 py-1" style="font-size: 0.65rem;">
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i> {{ strtoupper($statusBadge) }}
                            </span>
                        </div>

                        <div class="small text-muted text-truncate mb-0" style="font-size: 0.75rem;">
                            {{ $p->alamat ?? 'Alamat tidak tersedia' }}
                        </div>
                    </div>

                    {{-- KANAN: Detail & Harga --}}
                    <div class="col-5 ps-3 detail-col d-flex flex-column justify-content-between h-100">
                        <div class="d-flex flex-column align-items-end w-100">
                            @if (!empty($p->ip_address))
                                <div class="ip-badge mb-1">{{ $p->ip_address }}</div>
                            @endif

                            @if ($tanggalKolom)
                                <div class="date-info text-end text-dark">
                                    {{ $labelTanggal }}:<br>
                                    <span class="fw-bold text-dark">{{ \Carbon\Carbon::parse($tanggalKolom)->translatedFormat('d M Y') }}</span>
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

    /* Tab Status */
    .status-tabs-container { margin-top: -20px; position: relative; z-index: 11; }
    .status-tabs::-webkit-scrollbar { display: none; }
    .status-tab {
        white-space: nowrap; padding: 8px 16px; border-radius: 20px;
        font-size: 0.85rem; background: white; color: #6b7280;
        text-decoration: none; border: 1px solid #f3f4f6;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 6px;
    }
    .status-tab.active { background: #d97706; color: white; border-color: #d97706; }
    .badge-count { background: rgba(0,0,0,0.05); padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: bold; }
    .status-tab.active .badge-count { background: rgba(255,255,255,0.2); }

    /* Card Layout (Sama dengan Index) */
    .pelanggan-card {
        background: white; border-radius: 16px; padding: 16px;
        border: 1px solid #f3f4f6; box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        margin-left: 4px; transition: transform 0.2s;
    }
    .pelanggan-card:active { transform: scale(0.98); }

    /* Border Warna Status */
    .border-aktif { border-left: 5px solid #10b981 !important; }
    .border-baru { border-left: 5px solid #f59e0b !important; }
    .border-isolir { border-left: 5px solid #6b7280 !important; }
    .border-berhenti { border-left: 5px solid #ef4444 !important; }

    /* Komponen Kecil */
    .badge-area { background: #f8fafc; color: #64748b; padding: 3px 10px; border-radius: 50px; font-size: 0.7rem; border: 1px solid #f1f5f9; }
    .detail-col { border-left: 1px dashed #e5e7eb; }
    .ip-badge { font-family: monospace; font-size: 0.7rem; color: #94a3b8; background: #f8fafc; padding: 2px 6px; border-radius: 4px; }
    .date-info { font-size: 0.7rem; color: #94a3b8; line-height: 1.2; }
    .price-label { font-size: 0.65rem; color: #94a3b8; margin-bottom: -2px; }
    .price-value { font-size: 1.1rem; font-weight: 800; color: #1e293b; }
    .price-value .currency { font-size: 0.8rem; color: #64748b; margin-right: 2px; }

/* Styling Search & Filter yang Lebih Rapi */
.filter-wrapper {
    position: relative;
    z-index: 10;
}

.search-box {
    position: relative;
    display: flex;
    align-items: center;
}

.search-icon {
    position: absolute;
    left: 15px;
    color: #94a3b8;
    font-size: 0.9rem;
}

.form-control-custom {
    width: 100%;
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
    background-color: #fff;
}

.form-select-custom {
    padding: 10px 30px 10px 15px;
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
    min-width: 125px;
}

.form-select-custom:focus {
    outline: none;
    border-color: #f59e0b;
    color: #1e293b;
}

/* Animasi saat filter tidak ditemukan */
.text-center.mt-5 {
    animation: fadeIn 0.5s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
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
                    const ip = card.dataset.ip || '';
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

            if (searchInput) {
                searchInput.addEventListener('input', applyFilter);
            }

            if (areaFilter) {
                areaFilter.addEventListener('change', applyFilter);
            }
        });
    </script>
@endpush
