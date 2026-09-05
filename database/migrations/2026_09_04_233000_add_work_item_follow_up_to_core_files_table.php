<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('core_files', function (Blueprint $table): void {
            $table->foreignUuid('work_item_id')->nullable()->after('user_id')->constrained('work_items')->nullOnDelete();
            $table->foreignId('work_item_follow_up_id')->nullable()->after('work_item_id')->constrained('work_item_follow_ups')->nullOnDelete();
            $table->index(['work_item_id', 'work_item_follow_up_id']);
        });
    }

    public function down(): void
    {
        Schema::table('core_files', function (Blueprint $table): void {
            $table->dropForeign(['work_item_follow_up_id']);
            $table->dropForeign(['work_item_id']);
            $table->dropIndex(['work_item_id', 'work_item_follow_up_id']);
            $table->dropColumn(['work_item_follow_up_id', 'work_item_id']);
        });
    }
};
