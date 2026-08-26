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
        Schema::create('core_files', function (Blueprint $table) {
            $table->id();
            $table->uuid('identifier')->unique();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('file_type_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('url');
            $table->decimal('size_mb', 12, 8);
            $table->string('format', 20);
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('bucket');
            $table->string('disk', 50)->default('s3');
            $table->string('path');
            $table->string('mime_type', 100);
            $table->index(['client_id', 'user_id']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_files');
    }
};
