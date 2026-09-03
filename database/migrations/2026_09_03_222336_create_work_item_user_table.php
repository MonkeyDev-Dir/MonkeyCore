<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_user', function (Blueprint $table): void {
            $table->uuid('work_item_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->foreign('work_item_id')->references('id')->on('work_items')->cascadeOnDelete();
            $table->primary(['work_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_user');
    }
};
