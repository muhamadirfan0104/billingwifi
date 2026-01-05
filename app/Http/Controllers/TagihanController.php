<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pelanggan;
use App\Models\Langganan;
use App\Models\Sales;
use App\Models\Area;

class TagihanController extends Controller
{
    public function index(Request $request)
    {
        // status di URL: '', 'semua', 'belum_lunas', 'lunas'
        $statusFilter = $request->get('status', '');

        // sort dipakai saat belum_lunas: tunggakan_desc / tunggakan_asc
        $sort = $request->get('sort', 'tunggakan_desc');
        if (!in_array($sort, ['tunggakan_desc', 'tunggakan_asc'])) {
            $sort = 'tunggakan_desc';
        }

        // base query: per pelanggan
        $query = Pelanggan::with([
            'area',
            'sales.user',
            'langganan.paket',
            'langganan.tagihan',
        ]);

        // 🔍 FILTER SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhereHas('langganan.paket', function ($q2) use ($search) {
                        $q2->where('nama_paket', 'like', "%{$search}%");
                    })
                    ->orWhereHas('area', function ($q3) use ($search) {
                        $q3->where('nama_area', 'like', "%{$search}%");
                    })
                    ->orWhereHas('sales.user', function ($q4) use ($search) {
                        $q4->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 📦 FILTER PAKET
        if ($request->filled('paket')) {
            $query->whereHas('langganan.paket', function ($q) use ($request) {
                $q->where('id_paket', $request->paket);
            });
        }

        // 👤 FILTER SALES
        if ($request->filled('sales')) {
            $query->where('id_sales', $request->sales);
        }

        // 📍 FILTER WILAYAH
        if ($request->filled('area')) {
            $query->where('id_area', $request->area);
        }

        // 🧾 FILTER STATUS TAGIHAN PER PELANGGAN
        if ($statusFilter === 'lunas') {
            // pelanggan yang tidak punya tagihan "belum lunas"
            $query->whereDoesntHave('tagihan', function ($q) {
                $q->where('status_tagihan', 'belum lunas');
            });

            $query->latest();

        } elseif ($statusFilter === 'belum_lunas') {

            // Pelanggan yang punya minimal 1 tagihan "belum lunas"
            $query->whereHas('tagihan', function ($q) {
                $q->where('status_tagihan', 'belum lunas');
            });

            // ✅ HITUNG TUNGGAKAN: belum lunas DAN jatuh tempo sudah lewat
            $query->withCount([
                'tagihan as tunggakan_count' => function ($q) {
                    $q->where('status_tagihan', 'belum lunas')
                    ->where('jatuh_tempo', '<', now());
                }
            ]);

            // ✅ Sorting berdasarkan angka tunggakan_count (3 bulan, 2 bulan, 1 bulan)
            $sort = $request->get('sort', 'tunggakan_desc'); // tunggakan_desc / tunggakan_asc
            $direction = ($sort === 'tunggakan_asc') ? 'asc' : 'desc';
            $query->orderBy('tunggakan_count', $direction);

            // (opsional) kalau sama jumlahnya, urutkan nama biar stabil
            $query->orderBy('nama', 'asc');
        }


        // PAGINATE
        $pelanggan = $query->paginate(10)->withQueryString();

        // paket unik untuk filter dropdown (kalau dipakai)
        $paketList = Langganan::with('paket')
            ->get()
            ->pluck('paket')
            ->unique('id_paket')
            ->values();

        // data filter Sales & Wilayah
        $dataSales = Sales::with('user')->get();
        $dataArea  = Area::all();

        // AJAX → partial
        if ($request->ajax()) {
            $html = view('tagihan.partials.table', compact('pelanggan'))->render();
            $pagination = $pelanggan->onEachSide(1)->links('pagination::bootstrap-5')->toHtml();

            return response()->json([
                'html' => $html,
                'pagination' => $pagination,
            ]);
        }

        // NON-AJAX → full page
        return view('tagihan.index', [
            'pelanggan' => $pelanggan,
            'paketList' => $paketList,
            'dataSales' => $dataSales,
            'dataArea'  => $dataArea,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
        ]);
    }

    public function hapusTagihanPelanggan(Request $request)
    {
        $request->validate([
            'id_pelanggan' => 'required|exists:pelanggan,id_pelanggan',
            'tagihan_ids' => 'required|array',
            'tagihan_ids.*' => 'exists:tagihan,id_tagihan',
        ]);

        $deleted = \App\Models\Tagihan::whereIn('id_tagihan', $request->tagihan_ids)
            ->whereHas('langganan', fn ($q) => $q->where('id_pelanggan', $request->id_pelanggan))
            ->where('status_tagihan', 'belum lunas')
            ->whereDoesntHave('paymentItem')
            ->delete();

        return back()->with('success', "Berhasil menghapus $deleted tagihan.");
    }
}
