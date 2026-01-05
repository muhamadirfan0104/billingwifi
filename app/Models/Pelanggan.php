<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sales;
use App\Models\Langganan;
use App\Models\Area;
use App\Models\Tagihan;
use Carbon\Carbon;

class Pelanggan extends Model
{
    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'id_sales',
        'id_area',
        'nama',
        'nomor_buku', 
        'nik',
        'alamat',
        'nomor_hp',
        'ip_address',
        'status_pelanggan',
        'tanggal_registrasi',
    ];

    protected $casts = [
        'tanggal_registrasi' => 'datetime',
    ];

    protected $appends = ['status_pelanggan_efektif'];

    public function sales()
    {
        return $this->belongsTo(Sales::class, 'id_sales');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    public function langganan()
    {
        return $this->hasMany(Langganan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * ✅ RELASI FLAT: semua tagihan milik pelanggan (lewat langganan)
     * Ini dibutuhkan untuk withCount() & whereHas() tanpa dotted relation.
     */
    public function tagihan()
    {
        return $this->hasManyThrough(
            Tagihan::class,     // model tujuan
            Langganan::class,   // model perantara
            'id_pelanggan',     // FK di langganan -> pelanggan
            'id_langganan',     // FK di tagihan -> langganan
            'id_pelanggan',     // PK pelanggan
            'id_langganan'      // PK langganan
        );
    }

    // ===============================
    // STATUS PELANGGAN EFEKTIF
    // "baru" hanya untuk bulan & tahun yang sama dengan tanggal_registrasi
    // setelah ganti bulan → dianggap "aktif"
    // ===============================
    public function getStatusPelangganEfektifAttribute()
    {
        if ($this->status_pelanggan !== 'baru') {
            return $this->status_pelanggan;
        }

        if (!$this->tanggal_registrasi) {
            return $this->status_pelanggan;
        }

        $bulanDaftar   = $this->tanggal_registrasi->format('Y-m');
        $bulanSekarang = now()->format('Y-m');

        if ($bulanDaftar === $bulanSekarang) {
            return 'baru';
        }

        return 'aktif';
    }
    public function langgananAktifTerbaru()
{
    // Ambil 1 langganan terbaru (berdasarkan id / tanggal kalau ada),
    // bisa kamu sesuaikan kalau punya kolom status aktif.
    return $this->hasOne(Langganan::class, 'id_pelanggan', 'id_pelanggan')
        ->latest('id_langganan'); // kalau ada kolom created_at/tanggal_mulai, boleh ganti
}

}
