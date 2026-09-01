<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            // Menyimpan path file PDF Surat Disposisi yang otomatis dibuat
            // sistem setiap kali tahap ini ditandatangani (GM / Kabag SDM /
            // Staff SDM), supaya PIC bisa langsung melihat/mengunduhnya
            // tanpa perlu surat fisik.
            $table->string('file_path')->nullable()->after('catatan');
        });
    }

    public function down(): void
    {
        Schema::table('approval_workflows', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
