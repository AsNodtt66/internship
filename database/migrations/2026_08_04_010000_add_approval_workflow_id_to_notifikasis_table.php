<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->foreignId('approval_workflow_id')
                ->nullable()
                ->after('pengajuan_id')
                ->constrained('approval_workflows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('notifikasis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_workflow_id');
        });
    }
};
