<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->unsignedTinyInteger('urutan'); // 1=GM, 2=Kepala Bagian SDM, 3=Staff SDM
            $table->foreignId('penandatangan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['menunggu', 'ditandatangani'])->default('menunggu');
            $table->text('catatan')->nullable();
            $table->timestamp('diproses_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
