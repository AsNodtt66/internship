<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 50);
            $table->string('auditable_type', 160);
            $table->string('auditable_id', 100);
            $table->json('changes')->nullable();
            $table->string('request_id', 100)->nullable()->index();
            $table->string('source', 20)->default('web');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_subject_idx');
            $table->index(['event', 'created_at'], 'audit_logs_event_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
