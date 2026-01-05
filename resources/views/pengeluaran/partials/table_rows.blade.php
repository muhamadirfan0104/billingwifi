@php
    // helper: map status_approve => label teks
    function statusLabel($s){
        if ($s === 'approved') return 'Setuju';
        if ($s === 'rejected') return 'Tolak';
        return 'Menunggu';
    }
@endphp

@if($pengajuan->count() == 0)
    <tr>
        <td colspan="9" class="small-muted text-center">Tidak ada data pengajuan.</td>
    </tr>
@else
    @foreach($pengajuan as $idx => $p)
        <tr>
            {{-- No --}}
            <td>{{ ($pengajuan->currentPage()-1) * $pengajuan->perPage() + $idx + 1 }}</td>

            {{-- Nama sales --}}
            <td>
                @if($p->sales && $p->sales->user)
                    {{ $p->sales->user->name }} <br>
                    <small class="small-muted">{{ $p->sales->user->email }}</small>
                @else
                    -
                @endif
            </td>
            <td>
    {{ optional($p->area)->nama_area ?? '-' }}
</td>


            {{-- Tanggal pengajuan --}}
            <td>
                {{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('d M Y') }} <br>
                <small>{{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('H:i') }} WIB</small>
            </td>

            {{-- Nama pengeluaran --}}
            <td>{{ $p->nama_pengeluaran }}</td>

            {{-- Nominal --}}
            <td>Rp. {{ number_format($p->nominal, 0, ',', '.') }}</td>

            {{-- Bukti --}}
            {{-- Wilayah --}}

            <td>
@php
    $modalIdBukti = 'modal-bukti-' . $p->id_pengeluaran;

    // URL bukti (route lama tetap dipakai, tapi dibuka via modal)
    $buktiUrl = route('admin.pengajuan.bukti', $p->id_pengeluaran);

    // Deteksi ekstensi (kalau bukti_file berisi nama file/path)
    $ext = strtolower(pathinfo($p->bukti_file, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg','jpeg','png','webp','gif']);
    $isPdf = ($ext === 'pdf');
@endphp

@if($p->bukti_file)
    <button type="button"
        class="btn btn-sm btn-outline-secondary rounded-pill px-3 file-link"
        data-bs-toggle="modal"
        data-bs-target="#{{ $modalIdBukti }}">
        File
    </button>

    {{-- MODAL BUKTI --}}
    <div class="modal fade" id="{{ $modalIdBukti }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 720px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div class="modal-header border-0 pb-0 pt-3 px-3">
                    <h6 class="modal-title fw-bold text-dark">Bukti Lampiran</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-3">
                    <div class="bg-light border border-secondary border-opacity-10 rounded-3 p-2">
                        @if ($isImage)
                            <img src="{{ $buktiUrl }}" alt="Bukti Lampiran"
                                 class="img-fluid rounded-3 w-100"
                                 style="max-height:70vh; object-fit:contain;">
                        @elseif ($isPdf)
                            <iframe src="{{ $buktiUrl }}"
                                    style="width:100%; height:70vh; border:0;"
                                    class="rounded-3"></iframe>
                        @else
                            <div class="text-center text-muted small p-3">
                                File tidak bisa dipreview. Silakan buka.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light px-3 py-3 d-flex gap-2">
                    <a href="{{ $buktiUrl }}" target="_blank"
                       class="btn btn-primary rounded-pill fw-bold flex-grow-1">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka
                    </a>
                    <button type="button" class="btn btn-secondary rounded-pill fw-bold flex-grow-1"
                            data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>
@else
    -
@endif



            </td>

            {{-- Admin yang approve/reject --}}
            <td>
                @if($p->id_admin && $p->adminUser)
                    {{ $p->adminUser->name }} <br>
                    <small class="small-muted">
                        {{ \Carbon\Carbon::parse($p->tanggal_approve)->format('d M Y | H:i') }}
                    </small>
                @else
                    -
                @endif
            </td>

            {{-- Status (klik untuk ubah) --}}
            <td>
                <span
                    class="status-badge status {{ $p->status_approve }}"
                    data-id="{{ $p->id_pengeluaran }}"
                    data-current="{{ $p->status_approve }}"
                    style="cursor:pointer;"
                >
                    {{ statusLabel($p->status_approve) }}
                </span>
            </td>
        </tr>
    @endforeach
@endif
