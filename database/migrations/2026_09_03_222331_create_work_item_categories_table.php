<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_item_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->unique(['work_item_type_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_categories');
    }
};
