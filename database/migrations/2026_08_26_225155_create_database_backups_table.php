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
        Schema::create('database_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('backup_connection_id')->constrained()->cascadeOnDelete();
            $table->uuid('execution_id')->unique();
            $table->string('disk');
            $table->string('path')->nullable();
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->text('command')->nullable();
            $table->integer('exit_code')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('storage_verified_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('error_message')->nullable();
            $table->text('error_output')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'project_id', 'generated_at']);
            $table->index(['backup_connection_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('database_backups');
    }
};
