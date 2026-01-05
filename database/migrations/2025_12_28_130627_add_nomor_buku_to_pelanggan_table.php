<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::table('pelanggan', function (Blueprint $table) {
        $table->string('nomor_buku', 50)->nullable()->after('nama');
        // boleh double => tidak pakai unique
        // kalau mau cepat search, boleh kasih index:
        // $table->index('nomor_buku');
    });
}

public function down(): void
{
    Schema::table('pelanggan', function (Blueprint $table) {
        $table->dropColumn('nomor_buku');
        // kalau pakai index:
        // $table->dropIndex(['nomor_buku']);
    });
}

};
