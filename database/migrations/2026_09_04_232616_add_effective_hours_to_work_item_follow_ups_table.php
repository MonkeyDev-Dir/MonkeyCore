<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_item_follow_ups', function (Blueprint $table): void {
            $table->decimal('effective_hours', 8, 2)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('work_item_follow_ups', function (Blueprint $table): void {
            $table->dropColumn('effective_hours');
        });
    }
};
