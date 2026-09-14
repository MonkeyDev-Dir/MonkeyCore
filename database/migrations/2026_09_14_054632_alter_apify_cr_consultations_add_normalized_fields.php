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
        Schema::table('apify_cr_consultations', function (Blueprint $table): void {
            $table->string('name')->nullable();
            $table->string('first_surname')->nullable();
            $table->string('second_surname')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_identification', 20)->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_identification', 20)->nullable();
            $table->string('electoral_code', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('province')->nullable();
            $table->string('canton')->nullable();
            $table->string('district')->nullable();
            $table->string('identification_type', 20)->nullable();

            $table->dropColumn('response');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apify_cr_consultations', function (Blueprint $table): void {
            $table->json('response')->nullable();
            $table->dropColumn([
                'name',
                'first_surname',
                'second_surname',
                'father_name',
                'father_identification',
                'mother_name',
                'mother_identification',
                'electoral_code',
                'birth_date',
                'expiration_date',
                'province',
                'canton',
                'district',
                'identification_type',
            ]);
        });
    }
};
