<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_item_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('work_item_id');
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 50);
            $table->json('previous_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('work_item_id')->references('id')->on('work_items')->cascadeOnDelete();
            $table->index(['work_item_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_item_events');
    }
};
