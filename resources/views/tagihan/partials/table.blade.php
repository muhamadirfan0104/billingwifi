@php
    use Carbon\Carbon;
@endphp

@forelse($pelanggan as $i => $p)
    @php
        $langganan   = $p->langganan->sortByDesc('tanggal_mulai')->first();
        $paket       = $langganan?->paket;
        $tagihanList = $langganan?->tagihan ?? collect();

        $today = now();
        $noUrut = $pelanggan->firstItem()
            ? $pelanggan->firstItem() + $i
            : $i + 1;

        // Tagihan bulan ini
        $tagihanBulanIni = $tagihanList->first(fn($t) =>
            $t->tahun == $today->year && $t->bulan == $today->month
        );

        // Tanggal jatuh tempo (bulan ini)
        $tanggalJatuhTempo = $tagihanBulanIni
            ? Carbon::parse($tagihanBulanIni->jatuh_tempo)->translatedFormat('d F Y')
            : '-';

        // Hitung status tagihan
        $unpaid = $tagihanList->where('status_tagihan', 'belum lunas');

        // tunggakan = belum lunas dan jatuh tempo sudah lewat
        $tunggakan = $unpaid->filter(fn($t) =>
            Carbon::parse($t->jatuh_tempo)->lt($today)
        );
        $tunggakanCount = $tunggakan->count();

        // Tagihan terakhir lunas
        $lastPaid = $tagihanList->where('status_tagihan', 'lunas')
            ->sortBy(fn($t) => $t->tahun * 100 + $t->bulan)
            ->last();

        // Belum bayar bulan ini?
        $belumBayarBulanIni = $tagihanBulanIni &&
            $tagihanBulanIni->status_tagihan === 'belum lunas';

        // TOTAL TAGIHAN = jumlah total_tagihan dari semua tunggakan
        $totalTagihan = $tunggakan->sum('total_tagihan');

        $totalTagihanLabel = $totalTagihan > 0
            ? 'Rp ' . number_format($totalTagihan, 0, ',', '.')
            : '-';

        // WARNA BADGE & TEKS STATUS TAGIHAN
        if ($tunggakanCount >= 2) {
            $badgeClass = 'bg-danger text-white';
            $textStatus = "Belum Bayar $tunggakanCount Bulan";
        } elseif ($tunggakanCount == 1) {
            $badgeClass = 'bg-warning text-dark';
            $textStatus = "Belum Bayar 1 Bulan";
        } elseif ($belumBayarBulanIni) {
            $badgeClass = 'bg-secondary text-white';
            $textStatus = "Belum Bayar";
        } else {
            $badgeClass = 'bg-success text-white';
            $textStatus = "Lunas";
        }

        $modalId = "modalTunggakan-" . $p->id_pelanggan;

        // STATUS PELANGGAN
        $pelangganStatus = $p->status_pelanggan_efektif ?? $p->status_pelanggan;
        $pelangganStatusLabel = ucfirst($pelangganStatus ?? '-');

        switch ($pelangganStatus) {
            case 'aktif':
                $pelangganStatusClass = 'bg-success';
                break;
            case 'baru':
                $pelangganStatusClass = 'bg-secondary';
                break;
            case 'isolir':
                $pelangganStatusClass = 'bg-warning text-dark';
                break;
            case 'berhenti':
                $pelangganStatusClass = 'bg-danger';
                break;
            default:
                $pelangganStatusClass = 'bg-secondary';
        }
    @endphp
    @php
    // ====== WA BUTTON (samakan gaya seperti sales) ======
    $waNumber = $p->nomor_hp ?? null; // pastikan field ini ada (kalau beda, sesuaikan)

    // daftar periode tunggakan
    $listPeriode = $tunggakan->map(function($tg){
        return Carbon::create($tg->tahun, $tg->bulan, 1)
            ->locale('id')
            ->translatedFormat('F Y');
    })->values()->toArray();

    $periodePertama  = $listPeriode[0] ?? null;
    $periodeTerakhir = $listPeriode[count($listPeriode) - 1] ?? null;

    if (count($listPeriode) === 1) {
        $periodeText = $periodePertama;
    } elseif (count($listPeriode) > 1) {
        $periodeText = $periodePertama . ' s.d ' . $periodeTerakhir;
    } else {
        $periodeText = '-';
    }

    $totalTunggakanText = 'Rp ' . number_format((int)$totalTagihan, 0, ',', '.');

    // batas bayar (pakai jatuh tempo tagihan paling tua di tunggakan kalau ada, fallback 2 hari)
    $tagihanPalingTua = $tunggakan->sortBy(fn($t) => $t->tahun * 100 + $t->bulan)->first();
    $batasBayar = $tagihanPalingTua && !empty($tagihanPalingTua->jatuh_tempo)
        ? Carbon::parse($tagihanPalingTua->jatuh_tempo)->locale('id')->translatedFormat('d F Y')
        : now()->addDays(2)->locale('id')->translatedFormat('d F Y');

    $msg =
"Assalamu’alaikum Bapak/Ibu/Sdr {$p->nama},

Kami informasikan bahwa tagihan layanan internet Anda saat ini masih *BELUM LUNAS*.

• Periode tunggakan: *{$periodeText}* (" . count($listPeriode) . " bulan)
• Total tagihan: *{$totalTunggakanText}*
• Batas pembayaran: *24 jam setelah pesan ini (jika tidak ada kejelasan)*

Mohon segera dilakukan pembayaran untuk menghindari isolir/putus layanan.
Terima kasih. 🙏";

    $waLink = null;
    if (!empty($waNumber)) {
        $digits = preg_replace('/[^0-9]/', '', $waNumber);

        // normalisasi 08xxxx → 62xxxx
        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        $waLink = "https://wa.me/" . $digits . "?text=" . urlencode($msg);
    }
@endphp


    <tr>
        <td class="ps-4">{{ $noUrut }}</td>
    <td class="text-center">
        {{ $p->nomor_buku ?? '-' }}
    </td>
        <td>{{ $p->nama }}</td>

        <td>
            <div>{{ $p->area->nama_area ?? '-' }}</div>
            <small class="text-muted">{{ $p->sales->user->name ?? '-' }}</small>
        </td>

        <td>
            @if($paket)
                <div>{{ $paket->nama_paket }}</div>
                <small class="text-muted">
                    Rp {{ number_format($paket->harga_total ?? 0, 0, ',', '.') }}
                </small>
            @else
                -
            @endif
        </td>

        <td>{{ $p->ip_address }}</td>

        <td>{{ $tanggalJatuhTempo }}</td>

        <td>{{ $totalTagihanLabel }}</td>

        <td>
            {{-- STATUS TAGIHAN --}}
            @if($tunggakanCount > 0)
                <button class="badge {{ $badgeClass }} border-0"
                        data-bs-toggle="modal"
                        data-bs-target="#{{ $modalId }}"
                        style="cursor:pointer;">
                    {{ $textStatus }}
                </button>
            @else
                <span class="badge {{ $badgeClass }}">{{ $textStatus }}</span>
            @endif

            {{-- TERAKHIR BAYAR --}}
            @if($lastPaid)
                @php
                    $lastPaidDate = Carbon::create($lastPaid->tahun, $lastPaid->bulan, 1);
                @endphp
                <br>
                <small class="text-muted">
                    Terakhir Bayar: {{ $lastPaidDate->translatedFormat('F Y') }}
                </small>
            @else
                <br>
                <small class="text-danger">Belum Pernah Bayar</small>
            @endif

            {{-- STATUS PELANGGAN --}}
            <br>
            <small>
                <span class="badge {{ $pelangganStatusClass }} mt-1">
                    Pelanggan: {{ $pelangganStatusLabel }}
                </span>
            </small>

            {{-- MODAL DETAIL TUNGGAKAN --}}
            @if($tunggakanCount > 0)
                <div class="modal fade" id="{{ $modalId }}" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">

                            <div class="modal-header">
                                <h5 class="modal-title">Detail Tunggakan – {{ $p->nama }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <ul class="list-group">
                                    @foreach($tunggakan as $t)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <strong>
                                                    {{ Carbon::create($t->tahun, $t->bulan, 1)->translatedFormat('F Y') }}
                                                </strong>
                                            </span>
                                            <span>
                                                Rp {{ number_format($t->total_tagihan, 0, ',', '.') }}
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

<div class="modal-footer">
    @if ($waLink)
        <a target="_blank" href="{{ $waLink }}" class="btn btn-outline-success fw-bold">
            <i class="bi bi-whatsapp"></i> Kirim WA Tagihan
        </a>
    @else
        <button type="button" class="btn btn-outline-secondary fw-bold" disabled>
            <i class="bi bi-whatsapp"></i> Nomor WA tidak tersedia
        </button>
    @endif

    <button class="btn btn-dark" data-bs-dismiss="modal">Tutup</button>
</div>


                        </div>
                    </div>
                </div>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted">
            Tidak ada data pelanggan/tagihan
        </td>
    </tr>
@endforelse
