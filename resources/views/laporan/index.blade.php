@extends('layouts.master')
@section('title', 'Export Laporan')

@section('content')
@php
    use Carbon\Carbon;
@endphp

<style>
    :root {
        --theme-yellow: #ffc107;
        --theme-yellow-dark: #e0a800;
        --theme-yellow-soft: #fff9e6;
        --text-dark: #212529;
        --card-radius: 12px;
    }

    .page-title {
        font-size: 22px;
        font-weight: 800;
        color: var(--text-dark);
        letter-spacing: -0.5px;
    }

    .btn-admin-yellow {
        background-color: var(--theme-yellow);
        color: var(--text-dark);
        font-weight: 600;
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 13px;
        box-shadow: 0 2px 6px rgba(255, 193, 7, 0.3);
        transition: all 0.2s ease;
    }
    .btn-admin-yellow:hover {
        background-color: var(--theme-yellow-dark);
        color: var(--text-dark);
        transform: translateY(-2px);
    }

    .card-admin {
        background: #fff;
        border: none;
        border-radius: var(--card-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-top: 4px solid var(--theme-yellow);
    }

    .form-control-admin {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
    }
    .form-control-admin:focus {
        border-color: var(--theme-yellow);
        box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.2);
    }

    .filter-label {
        font-size: 11px;
        font-weight: 700;
        color: #6c757d;
        margin-bottom: 4px;
        display: block;
    }

    /* ===== SIDEBAR EXPORT (ikut tema pembukuan) ===== */
    .sidebar-title {
        font-size: 14px;
        font-weight: 800;
        color: var(--text-dark);
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: .4px;
    }
    .sidebar-sub {
        font-size: 12px;
        color: #6c757d;
        margin-bottom: 12px;
        display: block;
        text-decoration: none;
    }
    .export-item {
        padding: 10px 0;
        border-bottom: 1px dashed rgba(0,0,0,.08);
    }
    .export-item:last-child {
        border-bottom: none;
    }
    .export-label {
        font-size: 13px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 8px;
    }
    .export-label.danger {
        color: #dc3545;
    }
.btn-export {
    width: auto;                 /* INI KUNCI: jangan 100% */
    display: inline-flex;
    align-items: center;
    justify-content: flex-start; /* biar isi tombol rata kiri */
    gap: 8px;
    padding: 8px 14px;           /* biar enak */
}

.export-actions{
    display: flex;
    justify-content: flex-start; /* tombol nempel kiri */
}


    /* ===== BOX BULAN (kanan) ===== */
    .bulan-box {
        background: var(--theme-yellow-soft);
        border: 1px solid rgba(255,193,7,.35);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 12px;
    }
    .bulan-title {
        font-size: 12px;
        font-weight: 800;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .bulan-value {
        font-size: 16px;
        font-weight: 900;
        color: var(--text-dark);
    }

    /* ===== FILTER KIRI (pendek & rapi) ===== */
.filter-bar {
    display: flex;
    gap: 10px;
    align-items: flex-end;
    justify-content: flex-start;
    width: fit-content;   /* biar gak melebar */
}

.filter-item {
    min-width: 130px;
}

.filter-item select {
    width: 100%;
}

</style>

<div class="py-4 px-3">
<div class="mx-auto" style="max-width: 1550px; width:100%;">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="page-title mb-1">
                <i class="bi bi-clipboard-data-fill text-warning me-2"></i>Export Laporan
            </h4>
            <div class="text-muted small">Pilih kategori export berdasarkan periode</div>
        </div>
    </div>

<div class="row g-3">
    <div class="col-12">
        <div class="card-admin p-3">

            <div class="sidebar-title">Filter</div>
            <span class="sidebar-sub">Pilih periode Bulan & Tahun</span>

            <form method="GET" id="filterForm">
<div class="filter-bar">
    <div class="filter-item">
        <span class="filter-label">Bulan</span>
        <select name="bulan" class="form-select form-control-admin" id="filterBulan">
            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
                    {{ Carbon::create()->month($m)->translatedFormat('F') }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="filter-item">
        <span class="filter-label">Tahun</span>
        <select name="tahun" class="form-select form-control-admin" id="filterTahun">
            @foreach(range(now()->year - 3, now()->year + 1) as $y)
                <option value="{{ $y }}" {{ (int)$selectedYear === $y ? 'selected' : '' }}>
                    {{ $y }}
                </option>
            @endforeach
        </select>
    </div>
</div>

            </form>

            <hr class="my-3 opacity-25">

            {{-- LIST EXPORT --}}
<div class="export-item">
  <div class="export-label">Export Data Pelanggan(Semua)</div>
  <button type="button" class="btn btn-admin-yellow btn-export"
          onclick="goExport('{{ route('laporan.export.excel') }}', {tipe:'pelanggan'})">
      <i class="bi bi-download"></i> EXPORT
  </button>
</div>

            <div class="export-item">
                <div class="export-label">Data Registrasi Pelanggan(Tahunan)</div>
  <button type="button" class="btn btn-admin-yellow btn-export"
          onclick="goExport('{{ route('laporan.export.excel') }}', {tipe:'registrasi_tahun'})">
      <i class="bi bi-download"></i> EXPORT
  </button>
            </div>

<div class="export-item">
  <div class="export-label">Data Pembayaran Pelanggan(Tahunan)</div>
  <button type="button" class="btn btn-admin-yellow btn-export"
          onclick="goExport('{{ route('laporan.export.excel') }}', {tipe:'pembayaran_tahun'})">
      <i class="bi bi-download"></i> EXPORT
  </button>
</div>

<div class="export-item">
  <div class="export-label danger">Data Tunggakan (Semua)</div>
  <button type="button" class="btn btn-admin-yellow btn-export"
          onclick="goExport('{{ route('laporan.export.excel') }}', {tipe:'tunggakan_lifetime'})">
      <i class="bi bi-download"></i> EXPORT
  </button>
</div>

            <hr class="my-3 opacity-25">

            <div class="sidebar-title" style="font-size:13px;">Pembukuan</div>
            <span class="sidebar-sub">Rekap harian / bulanan</span>

<div class="export-item">
  <div class="export-label">Pembukuan (Bulanan per Sheet)</div>
  <button type="button" class="btn btn-admin-yellow btn-export"
          onclick="goExport('{{ route('laporan.export.excel') }}', {tipe:'pembukuan_bulanan'})">
      <i class="bi bi-download"></i> EXPORT
  </button>
</div>


        </div>
    </div>

</div>

</div>
</div>
@endsection

@push('scripts')
<script>
    // export selalu bawa bulan & tahun + param tambahan
      
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm  = document.getElementById('filterForm');
        const filterBulan = document.getElementById('filterBulan');
        const filterTahun = document.getElementById('filterTahun');

        // realtime submit (debounce)
        let filterTimer = null;
        function submitFilterRealtime() {
            if (!filterForm) return;
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => {
                filterForm.submit();
            }, 250);
        }

        if (filterBulan) filterBulan.addEventListener('change', submitFilterRealtime);
        if (filterTahun) filterTahun.addEventListener('change', submitFilterRealtime);

        // update label bulan kanan (tanpa nunggu reload)
        function updateBulanLabel(){
            const bulan = filterBulan?.value;
            const tahun = filterTahun?.value;
            if (!bulan || !tahun) return;

            const namaBulan = new Date(tahun, bulan - 1, 1)
                .toLocaleString('id-ID', { month: 'long' });

            const label = namaBulan.charAt(0).toUpperCase() + namaBulan.slice(1) + ' ' + tahun;
            const el = document.getElementById('bulanLabel');
            if (el) el.innerText = label;
        }

        if (filterBulan) filterBulan.addEventListener('change', updateBulanLabel);
        if (filterTahun) filterTahun.addEventListener('change', updateBulanLabel);
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const filterForm  = document.getElementById('filterForm');
  const filterBulan = document.getElementById('filterBulan');
  const filterTahun = document.getElementById('filterTahun');

  let filterTimer = null;
  function submitFilterRealtime() {
    if (!filterForm) return;
    clearTimeout(filterTimer);
    filterTimer = setTimeout(() => filterForm.submit(), 250);
  }

  if (filterBulan) filterBulan.addEventListener('change', submitFilterRealtime);
  if (filterTahun) filterTahun.addEventListener('change', submitFilterRealtime);
});

// ✅ INI YANG KAMU BUTUH: export selalu bawa bulan & tahun
function goExport(baseUrl, extra = {}) {
  const bulan = document.getElementById('filterBulan')?.value;
  const tahun = document.getElementById('filterTahun')?.value;

  const params = new URLSearchParams();
  if (bulan) params.set('bulan', bulan);
  if (tahun) params.set('tahun', tahun);

  Object.entries(extra || {}).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') params.set(k, v);
  });

  // kalau nanti kamu pakai checkbox units[] di halaman ini, otomatis kebawa
  document.querySelectorAll('input[name="units[]"]:checked')
    .forEach(cb => params.append('units[]', cb.value));

  window.location.href = `${baseUrl}?${params.toString()}`;
}
</script>

@endpush
