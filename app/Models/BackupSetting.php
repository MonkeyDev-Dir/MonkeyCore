<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['frequency', 'daily_retention_months', 'monthly_retention_years', 'last_run_at'])]
class BackupSetting extends Model
{
    protected $attributes = ['frequency' => 'daily', 'daily_retention_months' => 1, 'monthly_retention_years' => 3];

    protected function casts(): array
    {
        return ['daily_retention_months' => 'integer', 'monthly_retention_years' => 'integer', 'last_run_at' => 'datetime'];
    }
}
