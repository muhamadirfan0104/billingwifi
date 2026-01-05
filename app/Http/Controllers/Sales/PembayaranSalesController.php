<?php

// app/Http/Controllers/Sales/PembayaranSalesController.php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Pembayaran;
use App\Models\Area;
use App\Models\Sales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PembayaranSalesController extends Controller
{
public function riwayat(Request $request)
{
    $user  = Auth::user();
    $sales = $user->sales;

    $query = Pembayaran::with([
        'pelanggan.area',
        'sales.user',
        'user',
        'items.tagihan.langganan.paket',
    ]);

    // 1. Filter Wajib: Hanya milik sales yang sedang login
    if ($sales) {
        $query->where('id_sales', $sales->id_sales);
    } else {
        // Jika user bukan sales (opsional: agar tidak muncul data orang lain jika admin iseng buka link ini)
        $query->whereRaw('1 = 0'); 
    }

    // 2. Filter Search (No Bayar / Nama Pelanggan)
    $query->when($request->search, function ($q, $search) {
        $q->where(function ($sq) use ($search) {
            $sq->where('no_pembayaran', 'like', "%{$search}%")
               ->orWhereHas('pelanggan', function ($pq) use ($search) {
                   $pq->where('nama', 'like', "%{$search}%");
               });
        });
    });

    // 3. Filter Tanggal
    $query->when($request->filled('tanggal'), function ($q) use ($request) {
        $q->whereDate('tanggal_bayar', $request->tanggal);
    });

    // 4. Filter Area
    $query->when($request->filled('area_id'), function ($q) use ($request) {
        $q->whereHas('pelanggan', function ($aq) use ($request) {
            $aq->where('id_area', $request->area_id);
        });
    });

    $pembayaran = $query->orderByDesc('tanggal_bayar')->paginate(15);
    $areas = Area::orderBy('nama_area')->get();

    return view('seles2.pembayaran.riwayat', compact('pembayaran', 'areas'));
}
}
