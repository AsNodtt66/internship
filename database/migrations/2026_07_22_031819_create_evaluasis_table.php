<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengajuan_id')->constrained('pengajuans')->cascadeOnDelete();
            $table->foreignId('pembimbing_id')->constrained('users');
            $table->date('jadwal_evaluasi');
            $table->decimal('nilai_akhir', 5, 2)->nullable();
            $table->enum('hasil', ['lulus', 'perlu_perpanjangan'])->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('dinilai_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasis');
    }
};
