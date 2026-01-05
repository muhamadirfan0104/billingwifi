@extends('layouts.master')
@section('title', 'Riwayat Setoran')

@section('content')
@php
    use Carbon\Carbon;

    $namaBulan = $namaBulan ?? now()->translatedFormat('F');

    $saldoBulan = ($totalSetoranBulan ?? 0) - ($wajibBulan ?? 0); // + = kelebihan, - = sisa
    $isKelebihanGlobal = $saldoBulan > 0;
    $jumlahGlobal = abs($saldoBulan);

    $classGlobal = $jumlahGlobal == 0
        ? 'text-muted'
        : ($isKelebihanGlobal ? 'text-success' : 'text-danger');
@endphp

<style>
    :root {
        --theme-yellow: #ffc107;
        --theme-yellow-dark: #e0a800;
        --theme-yellow-soft: #fff9e6;
        --text-dark: #212529;
        --card-radius: 14px;
    }

    .card-soft {
        border-radius: var(--card-radius);
        box-shadow: 0 6px 20px rgba(0,0,0,.06);
        border: none;
        overflow: hidden;
    }

    .header-bar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 14px;
    }

    .subtle {
        color: #6c757d;
        font-size: 12px;
    }

    .badge-soft {
        background: var(--theme-yellow-soft);
        border: 1px solid rgba(255,193,7,.45);
        color: #7a5a00;
        font-weight: 700;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-bottom: 14px;
    }
    @media (max-width: 991px) {
        .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .summary-item {
        background: #fff;
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        padding: 10px 12px;
    }
    .summary-item .label {
        font-size: 11px;
        font-weight: 800;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 4px;
    }
    .summary-item .value {
        font-weight: 800;
        color: var(--text-dark);
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum" 1;
        white-space: nowrap;
    }

    .table-wrap {
        border: 1px solid #f0f0f0;
        border-radius: 12px;
        overflow: hidden;
    }

    .table-riwayat {
        margin-bottom: 0;
        table-layout: fixed;
        width: 100%;
    }

    .table-riwayat thead th {
        background: #fafafa;
        border-bottom: 1px solid #eee;
        font-size: 12px;
        font-weight: 800;
        color: #495057;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 12px 10px;
        vertical-align: middle;
    }

    .table-riwayat tbody td {
        padding: 11px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f2f2f2;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .table-riwayat tbody tr:hover td {
        background: #fffdf5;
    }

    .num {
        font-variant-numeric: tabular-nums;
        font-feature-settings: "tnum" 1;
        white-space: nowrap;
    }

    .aksi-wrap {
        display: inline-flex;
        gap: 6px;
        justify-content: center;
        align-items: center;
        flex-wrap: nowrap;
    }

    .btn-aksi {
        font-size: 11px;
        font-weight: 800;
        padding: 6px 10px;
        border-radius: 10px;
        white-space: nowrap;
    }

    .btn-edit {
        background: var(--theme-yellow);
        border: none;
        color: #212529;
    }
    .btn-edit:hover { background: var(--theme-yellow-dark); }

    .btn-del {
        border: 1px solid #dc3545;
        color: #dc3545;
        background: transparent;
    }
    .btn-del:hover {
        background: #dc3545;
        color: #fff;
    }
</style>

{{-- WRAPPER SAMA DENGAN INDEX --}}
<div class="container-fluid py-4 px-3">
    <div class="mx-auto" style="max-width: 1550px; width:100%;">

        {{-- TOP BAR --}}
        <div class="header-bar">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="subtle">Periode:</span>
                    <span class="badge badge-soft">{{ $namaBulan }} {{ $tahun }}</span>
                </div>
                <div class="subtle mt-1">
                    Wilayah : <strong class="text-dark">{{ $salesArea->nama_area }}</strong>
                </div>
                <div class="subtle">
                    Sales   : <strong class="text-dark">{{ $salesArea->nama_sales }}</strong>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- CARD --}}
        <div class="card card-soft mb-3">
            <div class="card-header d-flex justify-content-between align-items-center"
                 style="background: var(--theme-yellow-soft); border-bottom: 1px solid rgba(255,193,7,.35);">
                <div class="fw-bold text-dark">
                    <i class="bi bi-clock-history me-2 text-warning"></i>
                    Riwayat Setoran
                    <span class="text-muted fw-normal">– {{ $salesArea->nama_sales }} ({{ $salesArea->nama_area }})</span>
                </div>
            </div>

            <div class="card-body">

                {{-- STATUS --}}
                <div class="mb-2">
                    <span class="subtle">Posisi bulan ini:</span>
                    <span class="fw-bold {{ $classGlobal }}">
                        @if($jumlahGlobal == 0)
                            Pas (Rp 0)
                        @elseif($isKelebihanGlobal)
                            Kelebihan (Rp {{ number_format($jumlahGlobal, 0, ',', '.') }})
                        @else
                            Sisa (Rp {{ number_format($jumlahGlobal, 0, ',', '.') }})
                        @endif
                    </span>
                </div>

                {{-- RINGKASAN --}}
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="label">Wajib Setor</div>
                        <div class="value num">Rp {{ number_format($wajibBulan ?? 0, 0, ',', '.') }}</div>
                    </div>

                    <div class="summary-item">
                        <div class="label">Total Setoran</div>
                        <div class="value num">Rp {{ number_format($totalSetoranBulan ?? 0, 0, ',', '.') }}</div>
                    </div>

                    <div class="summary-item">
                        <div class="label">Kelebihan</div>
                        <div class="value num text-success">Rp {{ number_format($kelebihanBulan ?? 0, 0, ',', '.') }}</div>
                    </div>

                    <div class="summary-item">
                        <div class="label">Sisa Kewajiban</div>
                        <div class="value num {{ ($sisaBulan ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($sisaBulan ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                {{-- TABEL RIWAYAT --}}
                <div class="table-wrap">
                    <div class="table-responsive">
                        <table class="table table-riwayat align-middle">
                            <colgroup>
                                <col style="width:70px">
                                <col style="width:150px">
                                <col style="width:160px">
                                <col style="width:170px">
                                <col>
                                <col style="width:190px">
                            </colgroup>

                            <thead>
                                <tr>
                                    <th class="text-center">No</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Diterima</th>
                                    <th>Catatan</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($setorans as $i => $row)
                                    <tr>
                                        <td class="text-center text-muted">{{ sprintf('%03d', $i+1) }}</td>
                                        <td>{{ Carbon::parse($row->tanggal_setoran)->format('d M Y') }}</td>

                                        <td class="text-end fw-bold text-success num">
                                            Rp {{ number_format($row->nominal, 0, ',', '.') }}
                                        </td>

                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                {{ $row->nama_admin }}
                                            </span>
                                        </td>

                                        <td title="{{ $row->catatan ?? '-' }}">
                                            {{ $row->catatan ?? '-' }}
                                        </td>

                                        <td class="text-center">
                                            <div class="aksi-wrap">
                                                <a href="{{ route('admin.setoran.edit', [
                                                        'id_setoran' => $row->id_setoran,
                                                        'tahun'      => $tahun,
                                                        'bulan'      => $bulan,
                                                    ]) }}"
                                                   class="btn btn-aksi btn-edit">
                                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                                </a>

                                                <form action="{{ route('admin.setoran.destroy', $row->id_setoran) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Yakin ingin menghapus setoran ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="tahun" value="{{ $tahun }}">
                                                    <input type="hidden" name="bulan" value="{{ $bulan }}">
                                                    <button type="submit" class="btn btn-aksi btn-del">
                                                        <i class="bi bi-trash3 me-1"></i>Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Belum ada setoran di bulan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <a href="{{ route('admin.setoran.index', ['tahun' => $tahun, 'bulan' => $bulan]) }}"
           class="btn btn-outline-secondary btn-sm">
            &laquo; Kembali ke daftar wilayah
        </a>

    </div>
</div>
@endsection
