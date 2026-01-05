@extends('layouts.master')
@section('title', 'Riwayat Pembayaran')
@section('content')
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

    .btn-tab-active {
        background-color: var(--theme-yellow);
        color: #000;
        font-weight: 700;
        border: 1px solid var(--theme-yellow);
    }
    .btn-tab-inactive {
        background-color: #fff;
        color: #666;
        border: 1px solid #dee2e6;
    }
    .btn-tab-inactive:hover {
        background-color: var(--theme-yellow-soft);
        color: #000;
        border-color: var(--theme-yellow);
    }

    .card-admin {
        background: #fff;
        border: none;
        border-radius: var(--card-radius);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border-top: 4px solid var(--theme-yellow);
        width: 100%;
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

    .table-admin {
        width: 100%;
        margin-bottom: 0;
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
    }

    .table-admin tbody td {
        padding: 10px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f0f0f0;
    }

    .table-admin tbody tr:hover td {
        background-color: #fffdf5;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center !important;
        align-items: center;
        width: 100%;
        padding: 15px;
        background: #fff;
        border-top: 1px solid #f0f0f0;
    }

    .pagination-wrapper nav .d-none.d-sm-flex > div:first-child {
        display: none !important;
    }
    .pagination-wrapper nav .d-none.d-sm-flex {
        justify-content: center !important;
    }

    .page-item .page-link {
        color: #333;
        border: none;
        margin: 0 2px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
        padding: 6px 12px;
        background: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .page-item.active .page-link {
        background-color: var(--theme-yellow) !important;
        border-color: var(--theme-yellow) !important;
        color: #000 !important;
        box-shadow: 0 2px 6px rgba(255, 193, 7, 0.4);
    }
</style>

<div class="container-fluid p-4" id="tagihan-page-wrapper">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="page-title mb-1">
                <i class="bi bi-receipt text-warning me-2"></i>Status Tagihan
            </h4>
            <div class="text-muted small">Cek status pembayaran tagihan pelanggan</div>
        </div>
    </div>

    @php
        $statusFilter = $statusFilter ?? request('status', '');
        $sort = $sort ?? request('sort', 'tunggakan_desc');
    @endphp

    {{-- TAB STATUS --}}
    <div class="mb-3">
        <div class="btn-group shadow-sm" role="group" style="border-radius: 8px; overflow: hidden;">
            <a href="{{ route('tagihan.index', array_merge(request()->query(), ['status' => ''])) }}"
               class="btn btn-sm px-4 py-2 {{ $statusFilter === '' ? 'btn-tab-active' : 'btn-tab-inactive' }}">
               Semua
            </a>

<a href="{{ route('tagihan.index', array_merge(request()->query(), [
        'status' => 'belum_lunas',
        'sort'   => 'tunggakan_desc',
        'page'   => 1
    ])) }}"
   class="btn btn-sm px-4 py-2 {{ $statusFilter === 'belum_lunas' ? 'btn-tab-active' : 'btn-tab-inactive' }}">
   Belum Lunas
</a>


            <a href="{{ route('tagihan.index', array_merge(request()->query(), ['status' => 'lunas'])) }}"
               class="btn btn-sm px-4 py-2 {{ $statusFilter === 'lunas' ? 'btn-tab-active' : 'btn-tab-inactive' }}">
               Lunas
            </a>
        </div>
    </div>

    {{-- TOGGLE SORT (muncul hanya saat belum lunas) --}}
@if($statusFilter === 'belum_lunas')
    <div class="mb-3 d-flex gap-2">
        <a class="btn btn-sm {{ request('sort','tunggakan_desc') === 'tunggakan_desc' ? 'btn-tab-active' : 'btn-tab-inactive' }}"
           href="{{ route('tagihan.index', array_merge(request()->query(), [
                'status' => 'belum_lunas',
                'sort' => 'tunggakan_desc',
                'page' => 1
           ])) }}">
            Tunggakan Terbanyak
        </a>

        <a class="btn btn-sm {{ request('sort') === 'tunggakan_asc' ? 'btn-tab-active' : 'btn-tab-inactive' }}"
           href="{{ route('tagihan.index', array_merge(request()->query(), [
                'status' => 'belum_lunas',
                'sort' => 'tunggakan_asc',
                'page' => 1
           ])) }}">
            Tunggakan Tersedikit
        </a>
    </div>
@endif

    {{-- FILTER CARD --}}
    <div class="card-admin p-3 mb-3">
        <div class="row g-2" id="filter-tagihan-wrapper">
            {{-- HIDDEN INPUT UNTUK STATUS & SORT --}}
            <input type="hidden" id="status-tagihan" value="{{ $statusFilter }}">
            <input type="hidden" id="sort-tagihan" value="{{ $sort }}">

            {{-- SEARCH --}}
            <div class="col-12 col-md-4">
                <label class="form-label fw-bold text-muted small mb-1">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 8px 0 0 8px;">
                        <i class="bi bi-search text-warning" style="font-size: 13px;"></i>
                    </span>
                    <input type="text" id="search-tagihan" class="form-control form-control-admin border-start-0"
                           value="{{ request('search') }}"
                           style="border-radius: 0 8px 8px 0;"
                           placeholder="Cari pelanggan / paket...">
                </div>
            </div>

            {{-- SALES --}}
            <div class="col-6 col-md-4">
                <label class="form-label fw-bold text-muted small mb-1">Sales</label>
                <select id="sales-tagihan" class="form-select form-select-admin">
                    <option value="">Semua Sales</option>
                    @foreach($dataSales as $s)
                        <option value="{{ $s->id_sales }}" {{ request('sales') == $s->id_sales ? 'selected' : '' }}>
                            {{ $s->user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- WILAYAH --}}
            <div class="col-6 col-md-4">
                <label class="form-label fw-bold text-muted small mb-1">Wilayah</label>
                <select id="area-tagihan" class="form-select form-select-admin">
                    <option value="">Semua Wilayah</option>
                    @foreach($dataArea as $area)
                        <option value="{{ $area->id_area }}" {{ request('area') == $area->id_area ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- TABLE CARD --}}
    <div class="card-admin p-0" style="overflow: hidden;">
        <div class="table-responsive">
            <table class="table table-admin mb-0">
<thead>
    <tr>
        <th class="ps-4 text-center" style="width:70px;">No</th>
        <th class="text-center" style="width:40px;">ID</th>
        <th>Nama</th>
        <th>Area & Sales</th>
        <th>Paket Layanan</th>
        <th class="text-center" style="width:140px;">IP Address</th>
        <th class="text-center" style="width:150px;">Jatuh Tempo</th>
        <th class="text-center" style="width:160px;">Total Tagihan</th>
        <th class="text-center" style="width:220px;">Status</th>
    </tr>
</thead>


                <tbody id="tagihan-table-body">
                    @include('tagihan.partials.table', ['pelanggan' => $pelanggan])
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        <div class="pagination-wrapper" id="pagination-wrapper">
            {{ $pelanggan->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {

    let currentAjax = null;

    function loadTagihanData(page = 1) {
        if (currentAjax !== null) currentAjax.abort();

        const status = $('#status-tagihan').val() || '';

        // ✅ ambil sort dari hidden input (bukan urlParams di sini)
        const sort = $('#sort-tagihan').val() || 'tunggakan_desc';

        const search = $('#search-tagihan').val();
        const paket  = $('#paket-tagihan').val(); // kalau memang ada dropdown paket
        const sales  = $('#sales-tagihan').val();
        const area   = $('#area-tagihan').val();

        currentAjax = $.ajax({
            url: '{{ route("tagihan.index") }}',
            type: 'GET',
            cache: false,
            data: {
                ajax:   true,
                page:   page,
                status: status,
                sort:   sort,
                search: search,
                paket:  paket,
                sales:  sales,
                area:   area,
            },
            success: function (response) {
                $('#tagihan-table-body').html(response.html);
                $('#pagination-wrapper').html(response.pagination);

                updateUrl(status, sort, search, paket, sales, area, page);
            },
            error: function (xhr, textStatus) {
                if (textStatus === 'abort') return;
                console.error(xhr.responseText);
                alert('Terjadi kesalahan saat memuat data tagihan.');
            },
            complete: function () {
                currentAjax = null;
            }
        });
    }

    function updateUrl(status, sort, search, paket, sales, area, page) {
        const params = new URLSearchParams();

        if (status) params.set('status', status);
        if (sort)   params.set('sort', sort);
        if (search) params.set('search', search);
        if (paket)  params.set('paket', paket);
        if (sales)  params.set('sales', sales);
        if (area)   params.set('area', area);
        if (page > 1) params.set('page', page);

        const newUrl = params.toString()
            ? '{{ route("tagihan.index") }}?' + params.toString()
            : '{{ route("tagihan.index") }}';

        window.history.replaceState({}, '', newUrl);
    }

    // ✅ SEARCH
    $('#search-tagihan').on('input', function () {
        loadTagihanData(1);
    });

    // ✅ FILTER
    $('#sales-tagihan, #area-tagihan').on('change', function () {
        loadTagihanData(1);
    });

    // ✅ PAGINATION (fix URL relatif/absolut)
    $(document).on('click', '#pagination-wrapper .pagination a', function (e) {
        e.preventDefault();

        const href = $(this).attr('href');
        if (!href) return;

        const url = new URL(href, window.location.origin);
        const page = url.searchParams.get('page') || 1;

        loadTagihanData(page);
    });

    // ✅ INIT: ambil dari URL sekali, simpan ke hidden input
    (function loadInitial() {
        const urlParams = new URLSearchParams(window.location.search);

        const status = urlParams.get('status') || $('#status-tagihan').val() || '';
        const sort   = urlParams.get('sort')   || $('#sort-tagihan').val()   || 'tunggakan_desc';

        const search = urlParams.get('search');
        const sales  = urlParams.get('sales');
        const area   = urlParams.get('area');
        const page   = urlParams.get('page') || 1;

        $('#status-tagihan').val(status);
        $('#sort-tagihan').val(sort);

        if (search) $('#search-tagihan').val(search);
        if (sales)  $('#sales-tagihan').val(sales);
        if (area)   $('#area-tagihan').val(area);

        loadTagihanData(page);
    })();

});
</script>

@endpush
