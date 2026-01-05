@extends('layouts.master')

@section('content')
<style>
    :root {
        --theme-yellow: #ffc107;
        --theme-yellow-dark: #e0a800;
        --theme-yellow-soft: #fff9e6;
        --text-dark: #212529;
        --card-radius: 12px;
    }
    .page-title { font-size: 22px; font-weight: 800; color: var(--text-dark); }
    .card-admin {
        background:#fff; border-radius:var(--card-radius);
        box-shadow:0 4px 15px rgba(0,0,0,.05);
        border-top:4px solid var(--theme-yellow);
    }
    .btn-admin-yellow{
        background:var(--theme-yellow); color:var(--text-dark);
        font-weight:600; border-radius:8px; padding:8px 16px; border:none;
    }
    .btn-admin-yellow:hover{ background:var(--theme-yellow-dark); }
    .hint-box{
        background:var(--theme-yellow-soft);
        border:1px solid rgba(255,193,7,.4);
        border-radius:10px; padding:14px; font-size:13px;
    }
</style>

<div class="container-fluid p-4">
    <div class="mb-4">
        <h4 class="page-title">
            <i class="bi bi-upload text-warning me-2"></i>Import Data Pelanggan
        </h4>
        <div class="text-muted small">Upload Excel/CSV untuk menambah pelanggan + langganan otomatis</div>
    </div>

    @foreach (['success','error'] as $msg)
        @if(session($msg))
            <div class="alert alert-{{ $msg == 'success' ? 'success' : 'danger' }}">{{ session($msg) }}</div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger">
            <b>Validasi gagal:</b>
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif
<div class="card-admin p-4">

    <div class="hint-box mb-3">
        <b>Format kolom Excel:</b><br>
        nama, nomor_hp, nik, alamat, ip_address, tanggal_registrasi, paket_layanan, area, sales, status_pelanggan
    </div>

    <a href="{{ route('import.pelanggan.template') }}" class="btn btn-admin-yellow mb-4">
        <i class="bi bi-download me-1"></i> Download Template Excel
    </a>

    <form action="{{ route('import.pelanggan.process') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 align-items-end">
            <div class="col-md-6">
                <label class="form-label fw-semibold">File Excel / CSV</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div class="col-md-6">
                <button class="btn btn-admin-yellow">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload & Import
                </button>
            </div>
        </div>
    </form>

</div>

@endsection
