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
        Schema::create('backup_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('database_type')->default('postgresql');
            $table->string('ssh_host');
            $table->unsignedSmallInteger('ssh_port')->default(22);
            $table->string('ssh_user');
            $table->text('ssh_private_key')->nullable();
            $table->string('postgres_host')->default('localhost');
            $table->unsignedSmallInteger('postgres_port')->default(5432);
            $table->string('postgres_database');
            $table->string('postgres_user');
            $table->text('postgres_password')->nullable();
            $table->string('mysql_host')->nullable();
            $table->unsignedSmallInteger('mysql_port')->nullable();
            $table->string('mysql_database')->nullable();
            $table->string('mysql_user')->nullable();
            $table->text('mysql_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('backup_frequency', 20)->nullable();
            $table->unsignedTinyInteger('backup_daily_retention_months')->nullable();
            $table->unsignedTinyInteger('backup_monthly_retention_years')->nullable();
            $table->timestamp('backup_last_run_at')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'name']);
            $table->index(['client_id', 'project_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_connections');
    }
};
