@extends('layouts.master')
@section('title', 'Sales - Setoran per Wilayah')

@section('content')
@php
    use Carbon\Carbon;
    $selectedMonth = $selectedMonth ?? now()->month;
    $selectedYear  = $selectedYear ?? now()->year;
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

    .card-admin {
        background: #fff;
        border: none;
        border-radius: var(--card-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-top: 4px solid var(--theme-yellow);
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

    .form-control-admin, .form-select-admin {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 13px;
    }
    .form-control-admin:focus, .form-select-admin:focus {
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

    /* table rapi */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-admin {
        width: 100%;
        margin-bottom: 0;
        table-layout: fixed;
    }

    .table-admin thead th {
        background-color: var(--theme-yellow-soft);
        color: var(--text-dark);
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        border-bottom: 2px solid var(--theme-yellow);
        padding: 12px 10px;
        white-space: nowrap;
        vertical-align: middle;
    }

    .table-admin tbody td {
        padding: 10px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f0f0f0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .table-admin tbody tr:hover td { background-color: #fffdf5; }

    .table-admin th:nth-child(1), .table-admin td:nth-child(1) { width: 70px; }
    .table-admin th:nth-child(2), .table-admin td:nth-child(2) { width: 170px; }
    .table-admin th:nth-child(3), .table-admin td:nth-child(3) { width: 170px; }

    .table-admin th:nth-child(4), .table-admin td:nth-child(4),
    .table-admin th:nth-child(5), .table-admin td:nth-child(5),
    .table-admin th:nth-child(6), .table-admin td:nth-child(6) {
        width: 190px;
        text-align: right;
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum" 1;
    }

    .table-admin th:nth-child(7), .table-admin td:nth-child(7) { width: 140px; text-align: center; }

    .table-admin td:nth-child(3) .badge {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        display: inline-block;
        vertical-align: middle;
    }
    /* table rapi + stabil */
.table-responsive{
  overflow-x:auto;
  -webkit-overflow-scrolling:touch;
}

.table-admin{
  width:100%;
  margin-bottom:0;
  table-layout:fixed; /* kolom ngunci sesuai colgroup */
}

.table-admin thead th{
  background-color: var(--theme-yellow-soft);
  color: var(--text-dark);
  font-weight:700;
  font-size:12px;
  text-transform:uppercase;
  border-bottom:2px solid var(--theme-yellow);
  padding:12px 10px;
  white-space:nowrap;
  vertical-align:middle;
}

.table-admin tbody td{
  padding:10px;
  vertical-align:middle;
  font-size:13px;
  border-bottom:1px solid #f0f0f0;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
}

.table-admin tbody tr:hover td{ background:#fffdf5; }

/* angka biar rata & rapi */
.num{
  font-variant-numeric: tabular-nums;
  font-feature-settings: "tnum" 1;
}

/* badge wilayah biar ga melebar */
.badge-area{
  max-width:170px;
  display:inline-block;
  overflow:hidden;
  text-overflow:ellipsis;
  vertical-align:middle;
}

/* aksi biar tombol ga ketumpuk */
.aksi-cell{
  display:inline-flex;
  gap:8px;
  align-items:center;
  justify-content:center;
  flex-wrap:nowrap;
}

.btn-aksi-mini{
  font-size:11px;
  font-weight:700;
  padding:6px 10px;
  border-radius:10px;
  white-space:nowrap;
}

</style>

{{-- WRAPPER AMAN (ANTI KETABRAK SIDEBAR) --}}
<div class="py-4 px-3">
    <div class="mx-auto" style="max-width: 1550px; width:100%;">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="page-title mb-1">
                    <i class="bi bi-cash-coin text-warning me-2"></i>Setoran Sales
                </h4>
                <div class="text-muted small">Monitor setoran per wilayah dan sales</div>
            </div>
        </div>

        {{-- FLASH --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-left: 5px solid #198754;">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- FILTER CARD --}}
        <div class="card-admin p-3 mb-3">
            <form method="GET" action="{{ route('admin.setoran.index') }}" id="filterForm">
                <div class="row g-2 align-items-end">

                    <div class="col-6 col-md-2">
                        <span class="filter-label">Bulan</span>
                        <select name="bulan" class="form-select form-select-admin" id="filterBulan">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
                                    {{ Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <span class="filter-label">Tahun</span>
                        <select name="tahun" class="form-select form-select-admin" id="filterTahun">
                            @foreach (range(now()->year - 2, now()->year + 1) as $y)
                                <option value="{{ $y }}" {{ (int)$selectedYear === $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <span class="filter-label">Pencarian Cepat</span>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-radius: 8px 0 0 8px;">
                                <i class="bi bi-search text-warning" style="font-size: 13px;"></i>
                            </span>
                            <input type="text"
                                   class="form-control form-control-admin border-start-0"
                                   placeholder="Cari nama sales atau wilayah..."
                                   onkeyup="filterRows(this.value)"
                                   style="border-radius: 0 8px 8px 0;">
                        </div>
                    </div>

                </div>
            </form>
        </div>

{{-- TABLE CARD --}}
<div class="card-admin p-0" style="overflow:hidden;">
  <div class="table-responsive">
    <table class="table table-admin mb-0" id="tableSetoran">
      <colgroup>
        <col style="width:70px">   {{-- No --}}
        <col style="width:190px">  {{-- Sales --}}
        <col style="width:190px">  {{-- Wilayah --}}
        <col style="width:190px">  {{-- Target --}}
        <col style="width:190px">  {{-- Total --}}
        <col style="width:200px">  {{-- Sisa/Kelebihan --}}
        <col style="width:260px">  {{-- Aksi --}}
      </colgroup>

      <thead>
        <tr>
          <th class="ps-4">No</th>
          <th>Sales</th>
          <th>Wilayah</th>
          <th class="text-end">Target Setor</th>
          <th class="text-end">Total Setor</th>
          <th class="text-end">Sisa / Kelebihan</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse($rows as $i => $r)
          @php
            $isKelebihan = $r->sisa < 0;
            $jumlah = abs($r->sisa);
          @endphp

          <tr>
            <td class="ps-4 text-muted">{{ sprintf('%03d', $i+1) }}</td>
            <td class="fw-bold text-dark">{{ $r->nama_sales }}</td>

            <td>
              <span class="badge bg-light text-dark border badge-area" title="{{ $r->nama_area }}">
                {{ $r->nama_area }}
              </span>
            </td>

            <td class="text-end text-muted num">Rp {{ number_format($r->target_setor, 0, ',', '.') }}</td>
            <td class="text-end fw-bold text-success num">Rp {{ number_format($r->total_setoran, 0, ',', '.') }}</td>

            <td class="text-end fw-semibold num
                @if($jumlah == 0) text-muted
                @elseif($isKelebihan) text-success
                @else text-danger
                @endif">
              @if($jumlah == 0)
                <span class="badge bg-secondary bg-opacity-10 text-secondary">Lunas</span>
              @elseif($isKelebihan)
                <span class="badge bg-success bg-opacity-10 text-success">+ Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
              @else
                <span class="badge bg-danger bg-opacity-10 text-danger">- Rp {{ number_format($jumlah, 0, ',', '.') }}</span>
              @endif
            </td>

            <td class="text-center">
              <div class="aksi-cell">
                <a href="{{ route('admin.setoran.riwayat', [
                        'id_sales' => $r->id_sales,
                        'id_area'  => $r->id_area,
                        'tahun'    => $selectedYear,
                        'bulan'    => $selectedMonth,
                    ]) }}"
                   class="btn btn-sm btn-outline-warning text-dark border-warning btn-aksi-mini">
                  <i class="bi bi-clock-history me-1"></i> Riwayat
                </a>

                <button type="button"
                        class="btn btn-sm btn-admin-yellow btnTambahSetoran btn-aksi-mini"
                        data-bs-toggle="modal"
                        data-bs-target="#modalTambahSetoran"
                        data-sales="{{ $r->id_sales }}"
                        data-area="{{ $r->id_area }}"
                        data-nama-sales="{{ $r->nama_sales }}"
                        data-nama-area="{{ $r->nama_area }}"
                        data-tahun="{{ $selectedYear }}"
                        data-bulan="{{ $selectedMonth }}">
                  <i class="bi bi-plus-lg me-1"></i> Tambah
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-light-gray"></i>
              Belum ada data relasi sales & wilayah untuk periode ini.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>


    </div>
</div>
{{-- MODAL TAMBAH SETORAN (DARI INDEX) --}}
<div class="modal fade" id="modalTambahSetoran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 18px;">
      <div class="modal-header bg-warning text-white" style="border-radius: 18px 18px 0 0;">
        <h6 class="modal-title" id="modalTambahSetoranLabel">
            Tambah Setoran
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form action="{{ route('admin.setoran.store') }}" method="POST" id="formTambahSetoran">
        @csrf
        <input type="hidden" name="id_sales" id="modal_id_sales">
        <input type="hidden" name="id_area"  id="modal_id_area">

        <div class="modal-body">

          {{-- Periode --}}
          <div class="mb-3">
            <label class="form-label small">Periode Setoran</label>
            <div class="d-flex gap-2">
                <select name="bulan" class="form-select form-select-sm" id="modal_bulan" style="max-width: 140px;">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                    @endforeach
                </select>
                <select name="tahun" class="form-select form-select-sm" id="modal_tahun" style="max-width: 110px;">
                    @foreach (range(now()->year - 2, now()->year + 1) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
          </div>

          {{-- Nominal --}}
          <div class="mb-3">
            <label class="form-label">Nominal</label>
            <div class="input-group input-group-sm">
              <span class="input-group-text">Rp</span>
              <input type="number" name="nominal" class="form-control" min="1" step="1" required>
            </div>
          </div>

          {{-- Catatan --}}
          <div class="mb-2">
            <label class="form-label">Catatan</label>
            <textarea name="catatan" rows="3" class="form-control form-control-sm" placeholder="Opsional..."></textarea>
          </div>

        </div>

        <div class="modal-footer d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit" class="btn btn-warning btn-sm" style="font-weight:700;">
            Tambah
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
    // realtime submit untuk bulan/tahun
    let submitTimer = null;

    function submitFilterRealtime() {
        clearTimeout(submitTimer);
        submitTimer = setTimeout(() => {
            document.getElementById('filterForm')?.submit();
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('filterBulan')?.addEventListener('change', submitFilterRealtime);
        document.getElementById('filterTahun')?.addEventListener('change', submitFilterRealtime);
    });

    // filter client-side untuk tabel
    function filterRows(keyword) {
        keyword = (keyword || '').toLowerCase();
        document.querySelectorAll('#tableSetoran tbody tr').forEach(function (row) {
            if (row.cells.length < 3) return;
            const sales = row.cells[1].innerText.toLowerCase();
            const area  = row.cells[2].innerText.toLowerCase();
            row.style.display = (sales.includes(keyword) || area.includes(keyword)) ? '' : 'none';
        });
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btnTambahSetoran').forEach(btn => {
        btn.addEventListener('click', function () {
            const idSales   = this.dataset.sales;
            const idArea    = this.dataset.area;
            const namaSales = this.dataset.namaSales;
            const namaArea  = this.dataset.namaArea;
            const tahun     = this.dataset.tahun;
            const bulan     = this.dataset.bulan;

            document.getElementById('modal_id_sales').value = idSales;
            document.getElementById('modal_id_area').value  = idArea;

            // set periode default sesuai filter aktif
            document.getElementById('modal_tahun').value = tahun;
            document.getElementById('modal_bulan').value = bulan;

            // update judul modal
            document.getElementById('modalTambahSetoranLabel').innerText =
                `Tambah Setoran – ${namaSales} (${namaArea})`;

            // reset field nominal + catatan tiap buka modal
            const form = document.getElementById('formTambahSetoran');
            form.querySelector('input[name="nominal"]').value = '';
            form.querySelector('textarea[name="catatan"]').value = '';
        });
    });
});
</script>

@endsection
