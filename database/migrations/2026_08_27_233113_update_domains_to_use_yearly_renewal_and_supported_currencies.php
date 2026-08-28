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
        Schema::table('domains', function (Blueprint $table): void {
            $table->renameColumn('renewal_period_months', 'renewal_period_years');
        });

        Schema::table('domains', function (Blueprint $table): void {
            $table->unsignedSmallInteger('renewal_period_years')->default(1)->change();
            $table->string('currency', 3)->default('CRC')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table): void {
            $table->renameColumn('renewal_period_years', 'renewal_period_months');
        });

        Schema::table('domains', function (Blueprint $table): void {
            $table->unsignedSmallInteger('renewal_period_months')->default(12)->change();
            $table->string('currency', 3)->default('GTQ')->change();
        });
    }
};
