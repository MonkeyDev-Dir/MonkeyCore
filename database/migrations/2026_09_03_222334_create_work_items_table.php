<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('public_code', 13)->unique();
            $table->unsignedSmallInteger('public_code_year')->index();
            $table->unsignedInteger('public_code_sequence');
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_item_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_item_category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('origin', 30)->default('internal');
            $table->string('priority', 30)->default('medium');
            $table->string('status', 40)->default('new')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['public_code_year', 'public_code_sequence']);
            $table->index(['client_id', 'project_id']);
            $table->index(['work_item_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
