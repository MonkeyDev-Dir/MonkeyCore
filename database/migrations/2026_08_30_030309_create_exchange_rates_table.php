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
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->string('currency', 3);
            $table->string('rate_type', 20);
            $table->unsignedInteger('indicator_code');
            $table->decimal('value', 15, 8);
            $table->timestamps();

            $table->unique(['rate_date', 'currency', 'rate_type']);
            $table->index(['currency', 'rate_type', 'rate_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
