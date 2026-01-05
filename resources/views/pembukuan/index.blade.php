@extends('layouts.master')
@section('title', 'Data Pembukuan')

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
    /* jangan paksa width 100% */
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

    /* ===== TABLE CONSISTENT ===== */
    .table-admin {
        width: 100%;
        margin-bottom: 0;
        table-layout: fixed;              /* KUNCI ukuran kolom */
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

    .table-admin tbody td,
    .table-admin tfoot td {
        padding: 10px;
        vertical-align: middle;
        font-size: 13px;
        border-bottom: 1px solid #f0f0f0;
    }

    .table-admin tbody tr:hover td {
        background-color: #fffdf5;
    }

    /* Lebar kolom terkunci */
    .col-check { width: 44px; }
    .col-label { width: 320px; }

    /* Angka biar rapi dan ga patah */
    .num-cell {
        white-space: nowrap;
        font-variant-numeric: tabular-nums;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Label boleh turun baris */
    .label-cell {
        white-space: normal;
        word-break: break-word;
    }

    /* tfoot biar tegas */
    .tfoot-total td {
        background: #f8f9fa;
        font-weight: 800;
        border-top: 2px solid #e9ecef;
    }

    .modal-header-yellow {
        background-color: var(--theme-yellow-soft);
        border-bottom: 2px solid var(--theme-yellow);
    }
</style>


<div class="py-4 px-3">

<div class="mx-auto" style="max-width: 1550px; width:100%;">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="page-title mb-1">
                <i class="bi bi-journal-bookmark-fill text-warning me-2"></i>Data Pembukuan
            </h4>
            <div class="text-muted small">Laporan Sales & Admin</div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="card-admin p-3 mb-3">
        <form method="GET" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3">
                    <span class="filter-label">Bulan</span>
                    <select name="bulan" class="form-select form-control-admin" id="filterBulan">
                        @foreach(range(1,12) as $m)
                            <option value="{{ $m }}" {{ (int)$selectedMonth === $m ? 'selected' : '' }}>
                                {{ Carbon::create()->month($m)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-3">
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
    </div>

    {{-- TABLE --}}
    <div class="card-admin p-0" style="overflow:hidden;">
        <div class="table-responsive">
<table class="table table-admin mb-0 text-end">
    <colgroup>
        <col class="col-check">
        <col class="col-label">
        <col>
        <col>
        <col>
        <col>
        <col>
        <col>
    </colgroup>

    <thead class="text-center">
        <tr>
            <th>
                <input type="checkbox" id="checkAll">
            </th>
            <th class="text-start ps-3">Sales / Admin</th>
            <th>Pendapatan</th>
            <th>Komisi</th>
            <th>Pengeluaran</th>
            <th>
                Total Bersih<br>
                <small class="fw-normal opacity-75" style="text-transform:none;font-size:10px;">
                    (kewajiban bulan ini)
                </small>
            </th>
            <th>
                Setoran<br>
                <small class="fw-normal opacity-75" style="text-transform:none;font-size:10px;">
                    (bulan ini)
                </small>
            </th>
            <th class="pe-3">Selisih</th>
        </tr>
    </thead>

    <tbody>
        @if($rekap->isEmpty())
            <tr>
                <td colspan="8" class="text-center text-muted py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2 text-warning opacity-50"></i>
                    Belum ada data pembukuan untuk periode ini.
                </td>
            </tr>
        @else
            @foreach($rekap as $idx => $row)
                @php
                    $key = $row->modal_key ?? ('row-' . $idx);

                    $saldoGlobal = (float) ($row->saldo_global ?? 0);
                    $saldoLabel  = abs($saldoGlobal);
                    $saldoClass  = $saldoLabel == 0 ? 'text-muted' : ($saldoGlobal > 0 ? 'text-success' : 'text-danger');

                    $selisih      = (float) ($row->selisih ?? 0);
                    $selisihAbs   = abs($selisih);
                    $selisihClass = $selisihAbs == 0 ? 'text-muted' : ($selisih > 0 ? 'text-success' : 'text-danger');

                    $pendapatanNum  = (float) ($row->pendapatan ?? 0);
                    $komisiNum      = (float) ($row->total_komisi ?? 0);
                    $pengeluaranNum = (float) ($row->total_pengeluaran ?? 0);
                    $bersihNum      = (float) ($row->total_bersih ?? 0);
                    $setoranNum     = (float) ($row->total_setoran ?? 0);
                @endphp

                <tr class="rekap-row"
                    data-pendapatan="{{ $pendapatanNum }}"
                    data-komisi="{{ $komisiNum }}"
                    data-pengeluaran="{{ $pengeluaranNum }}"
                    data-bersih="{{ $bersihNum }}"
                    data-setoran="{{ $setoranNum }}"
                    data-selisih="{{ $selisih }}"
                >
                    <td class="text-center">
                        <input type="checkbox" class="row-check">
                    </td>

                    <td class="text-start ps-3 label-cell">
                        <div class="fw-bold text-dark">{{ $row->label }}</div>
                        <small class="text-muted d-block" style="font-size:11px;">
                            Saldo akumulasi:
                            <span class="{{ $saldoClass }}">
                                @if($saldoLabel == 0)
                                    Pas
                                @elseif($saldoGlobal > 0)
                                    Lebih: {{ number_format($saldoLabel, 0, ',', '.') }}
                                @else
                                    Kurang: {{ number_format($saldoLabel, 0, ',', '.') }}
                                @endif
                            </span>
                        </small>
                    </td>

                    <td class="num-cell">
                        @if($pendapatanNum > 0)
                            <a href="#" class="fw-bold text-dark text-decoration-none"
                               data-bs-toggle="modal" data-bs-target="#pendapatanModal-{{ $key }}">
                                Rp {{ number_format($pendapatanNum, 0, ',', '.') }}
                            </a>
                        @else
                            <span class="text-muted small">Rp 0</span>
                        @endif
                    </td>

                    <td class="num-cell">
                        @if($row->jenis === 'sales' && $komisiNum > 0)
                            <a href="#" class="fw-bold text-danger text-decoration-none"
                               data-bs-toggle="modal" data-bs-target="#komisiModal-{{ $key }}">
                                Rp {{ number_format($komisiNum, 0, ',', '.') }}
                            </a>
                        @else
                            <span class="text-muted small">Rp {{ number_format($komisiNum, 0, ',', '.') }}</span>
                        @endif
                    </td>

                    <td class="num-cell">
                        @if($row->jenis === 'sales' && $pengeluaranNum > 0)
                            <a href="#" class="fw-bold text-danger text-decoration-none"
                               data-bs-toggle="modal" data-bs-target="#pengeluaranModal-{{ $key }}">
                                Rp {{ number_format($pengeluaranNum, 0, ',', '.') }}
                            </a>
                        @else
                            <span class="text-muted small">Rp {{ number_format($pengeluaranNum, 0, ',', '.') }}</span>
                        @endif
                    </td>

                    <td class="num-cell bg-light fw-bold text-success">
                        Rp {{ number_format($bersihNum, 0, ',', '.') }}
                    </td>

                    <td class="num-cell">
                        @if($row->jenis === 'sales' && $setoranNum > 0)
                            <a href="#" class="fw-bold text-success text-decoration-none"
                               data-bs-toggle="modal" data-bs-target="#setoranModal-{{ $key }}">
                                Rp {{ number_format($setoranNum, 0, ',', '.') }}
                            </a>
                        @else
                            <span class="text-muted small">Rp {{ number_format($setoranNum, 0, ',', '.') }}</span>
                        @endif
                    </td>

                    <td class="num-cell {{ $selisihClass }} fw-bold pe-3">
                        @if($selisihAbs == 0)
                            <span class="badge bg-light text-secondary border">Pas</span>
                        @elseif($selisih > 0)
                            Lebih: {{ number_format($selisihAbs, 0, ',', '.') }}
                        @else
                            Kurang: {{ number_format($selisihAbs, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        @endif
    </tbody>

    <tfoot>
        <tr class="tfoot-total">
            <td></td>
            <td class="text-start ps-3">TOTAL (yang dipilih)</td>
            <td id="totalPendapatan" class="num-cell">Rp 0</td>
            <td id="totalKomisi" class="num-cell">Rp 0</td>
            <td id="totalPengeluaran" class="num-cell">Rp 0</td>
            <td id="totalBersih" class="num-cell text-success">Rp 0</td>
            <td id="totalSetoran" class="num-cell text-success">Rp 0</td>
            <td id="totalSelisih" class="num-cell pe-3">Rp 0</td>
        </tr>
    </tfoot>
</table>

        </div>
    </div>
      </div>

    {{-- MODALS --}}
    @foreach($rekap as $idx => $row)
        @php $key = $row->modal_key ?? ('row-' . $idx); @endphp

        {{-- Modal Pendapatan --}}
        <div class="modal fade" id="pendapatanModal-{{ $key }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header modal-header-yellow">
                        <h5 class="modal-title fw-bold" style="font-size:16px;">Detail Pendapatan – {{ $row->label }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-0">
                        @php $detailPembayaran = $row->detail_pembayaran ?? collect(); @endphp
                        <div class="table-responsive">
                            <table class="table table-admin table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-3">Tanggal</th>
                                        <th>No. Bayar</th>
                                        <th>Pelanggan</th>
                                        <th class="text-end pe-3">Nominal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detailPembayaran as $item)
                                        <tr>
                                            <td class="ps-3">{{ $item->tanggal_bayar ? Carbon::parse($item->tanggal_bayar)->format('d/m/Y H:i') : '-' }}</td>
                                            <td><span class="badge bg-white border text-dark">{{ $item->no_pembayaran }}</span></td>
                                            <td>{{ $item->nama_pelanggan ?? '-' }}</td>
                                            <td class="text-end pe-3 fw-bold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Tidak ada data.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($row->jenis === 'sales')
            {{-- Modal Komisi --}}
            <div class="modal fade" id="komisiModal-{{ $key }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header modal-header-yellow">
                            <h5 class="modal-title fw-bold" style="font-size:16px;">Detail Komisi – {{ $row->label }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            @php $detailKomisi = $row->detail_komisi ?? collect(); @endphp
                            <div class="table-responsive">
                                <table class="table table-admin table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Tanggal</th>
                                            <th>Pelanggan</th>
                                            <th class="text-end">Jumlah</th>
                                            <th class="text-end pe-3">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($detailKomisi as $item)
                                            <tr>
                                                <td class="ps-3">{{ $item->tanggal_bayar ? Carbon::parse($item->tanggal_bayar)->format('d/m/Y') : '-' }}</td>
                                                <td>{{ $item->nama_pelanggan ?? '-' }}</td>
                                                <td class="text-end">{{ $item->jumlah_komisi }}</td>
                                                <td class="text-end pe-3">Rp {{ number_format($item->nominal_komisi, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Pengeluaran --}}
            <div class="modal fade" id="pengeluaranModal-{{ $key }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header modal-header-yellow">
                            <h5 class="modal-title fw-bold" style="font-size:16px;">Detail Pengeluaran – {{ $row->label }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            @php $detailPengeluaran = $row->detail_pengeluaran ?? collect(); @endphp
                            <div class="table-responsive">
                                <table class="table table-admin table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Tanggal</th>
                                            <th>Nama</th>
                                            <th>Catatan</th>
                                            <th class="text-end pe-3">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($detailPengeluaran as $item)
                                            <tr>
                                                <td class="ps-3">{{ $item->tanggal_approve ? Carbon::parse($item->tanggal_approve)->format('d/m/Y') : '-' }}</td>
                                                <td>{{ $item->nama_pengeluaran }}</td>
                                                <td>{{ $item->catatan ?? '-' }}</td>
                                                <td class="text-end pe-3 fw-bold text-danger">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Setoran --}}
            <div class="modal fade" id="setoranModal-{{ $key }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header modal-header-yellow">
                            <h5 class="modal-title fw-bold" style="font-size:16px;">Detail Setoran – {{ $row->label }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            @php $detailSetoran = $row->detail_setoran ?? collect(); @endphp
                            <div class="table-responsive">
                                <table class="table table-admin table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-3">Tanggal</th>
                                            <th>Admin</th>
                                            <th>Catatan</th>
                                            <th class="text-end pe-3">Nominal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($detailSetoran as $item)
                                            <tr>
                                                <td class="ps-3">{{ $item->tanggal_setoran ? Carbon::parse($item->tanggal_setoran)->format('d/m/Y H:i') : '-' }}</td>
                                                <td>{{ $item->nama_admin ?? '-' }}</td>
                                                <td>{{ $item->catatan ?? '-' }}</td>
                                                <td class="text-end pe-3 fw-bold text-success">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-3">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkAll = document.getElementById('checkAll');
    const rowChecks = () => document.querySelectorAll('.row-check');
        // Realtime filter: auto submit saat bulan/tahun berubah
    const filterForm  = document.getElementById('filterForm');
    const filterBulan = document.getElementById('filterBulan');
    const filterTahun = document.getElementById('filterTahun');

    let filterTimer = null;
    function submitFilterRealtime() {
        if (!filterForm) return;
        clearTimeout(filterTimer);
        filterTimer = setTimeout(() => {
            filterForm.submit();
        }, 250); // debounce biar ga spam submit
    }

    if (filterBulan) filterBulan.addEventListener('change', submitFilterRealtime);
    if (filterTahun) filterTahun.addEventListener('change', submitFilterRealtime);


    function formatRupiah(num) {
        const n = Math.round(Number(num) || 0);
        return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function updateTotals() {
        let totalPendapatan = 0;
        let totalKomisi = 0;
        let totalPengeluaran = 0;
        let totalBersih = 0;
        let totalSetoran = 0;
        let totalSelisih = 0;

        document.querySelectorAll('tr.rekap-row').forEach(tr => {
            const cb = tr.querySelector('.row-check');
            if (cb && cb.checked) {
                totalPendapatan  += Number(tr.dataset.pendapatan || 0);
                totalKomisi      += Number(tr.dataset.komisi || 0);
                totalPengeluaran += Number(tr.dataset.pengeluaran || 0);
                totalBersih      += Number(tr.dataset.bersih || 0);
                totalSetoran     += Number(tr.dataset.setoran || 0);
                totalSelisih     += Number(tr.dataset.selisih || 0);
            }
        });

        document.getElementById('totalPendapatan').textContent  = formatRupiah(totalPendapatan);
        document.getElementById('totalKomisi').textContent      = formatRupiah(totalKomisi);
        document.getElementById('totalPengeluaran').textContent = formatRupiah(totalPengeluaran);
        document.getElementById('totalBersih').textContent      = formatRupiah(totalBersih);
        document.getElementById('totalSetoran').textContent     = formatRupiah(totalSetoran);

        const sel = Math.round(totalSelisih || 0);
        document.getElementById('totalSelisih').textContent =
            (sel < 0 ? '- ' : '') + formatRupiah(Math.abs(sel));
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            rowChecks().forEach(cb => cb.checked = checkAll.checked);
            updateTotals();
        });
    }

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-check')) {
            const all = rowChecks();
            const checked = document.querySelectorAll('.row-check:checked');
            if (checkAll) checkAll.checked = (all.length > 0 && checked.length === all.length);
            updateTotals();
        }
    });

    updateTotals();
});
</script>
@endpush
