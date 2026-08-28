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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('hosting_provider')->nullable();
            $table->decimal('annual_cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('GTQ');
            $table->date('expires_at')->index();
            $table->unsignedSmallInteger('renewal_period_months')->default(12);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'name']);
            $table->index(['client_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
