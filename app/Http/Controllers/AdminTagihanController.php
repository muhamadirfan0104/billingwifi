<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Tagihan;
use App\Models\Pembayaran;
use App\Models\PaymentItem;
use App\Models\BukuKas;
use App\Models\Pelanggan;
use App\Models\Paket;
use App\Models\Langganan;
use App\Models\Sales;
use App\Models\Area;
use Illuminate\Support\Str;
use App\Services\TagihanService;

use Mpdf\Mpdf;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AdminTagihanController extends Controller
{
    protected TagihanService $tagihanService;

    public function __construct(TagihanService $tagihanService)
    {
        $this->tagihanService = $tagihanService;
    }

    // List semua tagihan belum lunas
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $paketId  = $request->input('paket');
        $salesId  = $request->input('sales');
        $areaId   = $request->input('area');

        $query = Pelanggan::with([
            'langganan.tagihan',
            'langganan.paket',
            'area',
            'sales.user',
        ]);

        // 🔍 FILTER SEARCH (nama / hp / paket)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nomor_hp', 'like', "%{$search}%")
                  ->orWhereHas('langganan.paket', function ($sub) use ($search) {
                      $sub->where('nama_paket', 'like', "%{$search}%");
                  });
            });
        }

        // 📦 FILTER PAKET
        if ($paketId) {
            $query->whereHas('langganan', function ($q) use ($paketId) {
                $q->where('id_paket', $paketId);
            });
        }

        // 👤 FILTER SALES
        if ($salesId) {
            $query->where('id_sales', $salesId);
            // atau kalau mau lewat relasi:
            // $query->whereHas('sales', fn($q) => $q->where('id_sales', $salesId));
        }

        // 🌍 FILTER WILAYAH / AREA
        if ($areaId) {
            $query->where('id_area', $areaId);
        }

        $pelanggan = $query->orderBy('nama')->paginate(15);

        $paketList = Paket::orderBy('nama_paket')->get();
        $dataSales = Sales::with('user')->orderBy('id_sales')->get();
        $dataArea  = Area::orderBy('nama_area')->get();

        // REQUEST AJAX (untuk reload tabel)
if ($request->ajax()) {
    $html       = view('admin.tagihan.partials.table', compact('pelanggan'))->render();
    $pagination = $pelanggan->links()->toHtml();
    $modals     = view('admin.tagihan.partials.modals', compact('pelanggan'))->render();

    return response()->json([
        'html'       => $html,
        'pagination' => $pagination,
        'modals'     => $modals,
    ]);
}

        // REQUEST BIASA
        return view('admin.tagihan.index', [
            'pelanggan' => $pelanggan,
            'paketList' => $paketList,
            'dataSales' => $dataSales,
            'dataArea'  => $dataArea,
        ]);
    }

 
public function bayarBanyak(Request $request)
{
    $request->validate([
        'id_langganan'  => 'required|exists:langganan,id_langganan',
        'start_ym'      => 'required|string',     // format YYYY-MM
        'jumlah_bulan'  => 'required|integer|min:1|max:60',
    ]);

        $langganan = Langganan::with(['paket', 'pelanggan', 'tagihan'])->findOrFail($request->id_langganan);
        $admin     = Admin::where('user_id', Auth::id())->firstOrFail(); // boleh pakai Auth::id()

    DB::beginTransaction();

    try {
        // ------------ Ambil data form ------------
        $startYm     = $request->start_ym;     // contoh "2025-08"
        $targetCount = (int) $request->jumlah_bulan;

        [$startYear, $startMonth] = array_map('intval', explode('-', $startYm));
        $current = Carbon::create($startYear, $startMonth, 1); // pointer bulan berjalan

        $tagihanDiproses = collect();

        // Semua tagihan existing untuk langganan ini (collect)
        $allTagihan = $langganan->tagihan;

        // Contoh: 2025-09 -> 202509
        $maxExistingYm = $allTagihan->isNotEmpty()
            ? $allTagihan->max(fn($t) => (int)$t->tahun * 100 + (int)$t->bulan)
            : null;

        $loopGuard = 0; // buat jaga-jaga biar nggak endless loop
        $maxLoop   = 120; // maksimal cek 120 bulan ke depan (10 tahun)

        while ($tagihanDiproses->count() < $targetCount && $loopGuard < $maxLoop) {

            $tahun = (int) $current->format('Y');
            $bulan = (int) $current->format('n');
            $ym    = $tahun * 100 + $bulan;

            // 1. Cari tagihan existing untuk bulan ini
            $tagihan = $allTagihan->first(function ($t) use ($tahun, $bulan) {
                return (int)$t->tahun === $tahun && (int)$t->bulan === $bulan;
            });

            // 2. Kalau belum ada tagihan di bulan ini
            if (!$tagihan) {

                if (!is_null($maxExistingYm) && $ym <= $maxExistingYm) {
                    // INI GAP MASA LALU / BULAN PUTUS
                    // -> jangan create tagihan baru, jangan count, cukup next month
                    $current->addMonth();
                    $loopGuard++;
                    continue;
                }

                // Kalau ym > maxExistingYm -> bulan masa depan
                $tagihan = $this->tagihanService->getOrCreateForMonth(
                    $langganan,
                    $tahun,
                    $bulan,
                    true // dibuat karena pembayaran periode
                );

                // Kalau service memutuskan tidak boleh create (mis. di luar tanggal_mulai/berhenti)
                if (!$tagihan) {
                    $current->addMonth();
                    $loopGuard++;
                    continue;
                }

                // Masukkan ke koleksi allTagihan dan update maxExistingYm
                $allTagihan->push($tagihan);
                $maxExistingYm = max($maxExistingYm ?? 0, $ym);
            }

            // 3. Kalau sudah lunas -> tidak perlu dibayar lagi
            if ($tagihan->status_tagihan === 'lunas') {
                $current->addMonth();
                $loopGuard++;
                continue;
            }

            // 4. Tagihan valid untuk diproses
            $tagihanDiproses->push($tagihan);

            $current->addMonth();
            $loopGuard++;
        }

        if ($tagihanDiproses->isEmpty()) {
            throw new \Exception("Tidak ada tagihan yang perlu dibayar pada periode ini.");
        }

        // ======================================================
        // Semua tagihan harus dari pelanggan yang sama
        // ======================================================
        $pelangganId = $langganan->pelanggan->id_pelanggan;

        $totalBayar = $tagihanDiproses->sum('total_tagihan');

        // ======================================================
        // BUAT PEMBAYARAN
        // ======================================================
        $pembayaran = Pembayaran::create([
            'id_pelanggan'  => $pelangganId,
            'id_sales'      => null,
            'id_user'       => Auth::id(),  
            'tanggal_bayar' => now(),
            'nominal'       => $totalBayar,
            'no_pembayaran' => $this->generateNoPembayaran(),
        ]);

        // ======================================================
        // PAYMENT ITEM + UPDATE STATUS TAGIHAN
        // ======================================================
        foreach ($tagihanDiproses as $t) {

            PaymentItem::create([
                'id_pembayaran' => $pembayaran->id_pembayaran,
                'id_tagihan'    => $t->id_tagihan,
                'nominal_bayar' => $t->total_tagihan,
            ]);

            $t->update(['status_tagihan' => 'lunas']);
        }

        // ======================================================
        // BUKU KAS
        // ======================================================
        BukuKas::create([
            'id_admin'       => $admin->id_admin,
            'id_sales'       => null,
            'id_pembayaran'  => $pembayaran->id_pembayaran,
            'id_setoran'     => null,
            'id_pengeluaran' => null,
            'tipe'           => 'pemasukan',
            'sumber'         => 'Pembayaran periode oleh admin',
            'nominal'        => $totalBayar,
        ]);

DB::commit();

// ambil pelanggan (buat WA & nama)
$pelanggan = $langganan->pelanggan;

return back()
    ->with('success', 'Pembayaran periode berhasil diproses.') // alert biasa tetap ada
    ->with('success_modal', 'Pembayaran periode berhasil diproses.') // khusus modal
    ->with('last_pembayaran_id', $pembayaran->id_pembayaran)
    ->with('wa_pelanggan', $pelanggan->nomor_hp)      // optional
    ->with('no_invoice', $pembayaran->no_pembayaran)  // optional
    ->with('total_bayar', $totalBayar);               // optional

    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
    }
}

public function nota($id)
{
    $pembayaran = Pembayaran::with([
        'pelanggan',
        'sales.user',
        'paymentItems.tagihan.langganan.paket',
    ])->findOrFail($id);

    $download = request()->boolean('download');
    $embed    = request()->boolean('embed');

    $no   = $pembayaran->no_pembayaran ?? 'NOTA';
    $nama = $pembayaran->pelanggan->nama ?? 'PELANGGAN';

    $namaSafe = Str::of($nama)
        ->upper()
        ->replaceMatches('/[^A-Z0-9 ]/', '')
        ->trim()
        ->replaceMatches('/\s+/', ' ')
        ->toString();

    $filename = $no.' - '.$namaSafe.'.pdf';

    // PREVIEW (HTML biasa)
    if (!$download) {
        return view('seles2.tagihan.nota', [
            'pembayaran' => $pembayaran,
            'embed'      => $embed,
        ]);
    }

    // DOWNLOAD (mPDF pakai view khusus PDF)
    $tempDir = storage_path('app/mpdf');
    if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

    $mpdf = new \Mpdf\Mpdf([
        'mode'    => 'utf-8',
        'format'  => 'A4',
        'tempDir' => $tempDir,
    ]);

    $html = view('seles2.tagihan.nota_pdf', [
        'pembayaran' => $pembayaran,
    ])->render();

    $mpdf->WriteHTML($html);

    return response($mpdf->Output($filename, 'S'), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
    ]);
}


    protected function generateNoPembayaran(): string
    {
        $last = Pembayaran::orderByDesc('id_pembayaran')->first();
        $counter = $last && str_contains($last->no_pembayaran, '-')
            ? (int) substr($last->no_pembayaran, -4) + 1
            : 1;

        return 'ADM-' . now()->format('Ymd') . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }
}
