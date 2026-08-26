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
        Schema::create('apify_cr_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('identification', 10);
            $table->json('response')->nullable();
            $table->boolean('found')->default(true);
            $table->timestamp('consulted_at');
            $table->timestamps();

            $table->unique(['type', 'identification']);
            $table->index('consulted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apify_cr_consultations');
    }
};
