<?php

namespace App\Services;

use App\Models\BackupConnection;
use App\Models\BackupSetting;
use Carbon\CarbonImmutable;

class BackupSettingsService
{
    public function current(): BackupSetting
    {
        return BackupSetting::query()->firstOrCreate(['id' => 1]);
    }

    /** @return array{frequency: string, daily_retention_months: int, monthly_retention_years: int} */
    public function forConnection(BackupConnection $connection): array
    {
        $setting = $this->current();

        return [
            'frequency' => $connection->backup_frequency ?? $setting->frequency,
            'daily_retention_months' => $connection->backup_daily_retention_months ?? $setting->daily_retention_months,
            'monthly_retention_years' => $connection->backup_monthly_retention_years ?? $setting->monthly_retention_years,
        ];
    }

    public function isDue(?BackupConnection $connection = null): bool
    {
        $setting = $connection === null ? $this->current() : null;
        $frequency = $connection === null ? $setting->frequency : $this->forConnection($connection)['frequency'];
        $lastRunAt = $connection?->backup_last_run_at ?? $setting?->last_run_at;

        if ($lastRunAt === null) {
            return true;
        }

        $nextRun = match ($frequency) {
            'hourly' => CarbonImmutable::instance($lastRunAt)->addHour(),
            'every_six_hours' => CarbonImmutable::instance($lastRunAt)->addHours(6),
            'every_twelve_hours' => CarbonImmutable::instance($lastRunAt)->addHours(12),
            'weekly' => CarbonImmutable::instance($lastRunAt)->addWeek(),
            'every_two_days' => CarbonImmutable::instance($lastRunAt)->addDays(2),
            default => CarbonImmutable::instance($lastRunAt)->addDay(),
        };

        return CarbonImmutable::now()->greaterThanOrEqualTo($nextRun);
    }

    public function hasDueConnections(): bool
    {
        return BackupConnection::query()
            ->where('is_active', true)
            ->get()
            ->contains(fn (BackupConnection $connection): bool => $this->isDue($connection));
    }

    public function markRun(?BackupConnection $connection = null): void
    {
        if ($connection !== null) {
            $connection->update(['backup_last_run_at' => now()]);

            return;
        }

        $this->current()->update(['last_run_at' => now()]);
    }

    /** @param array{frequency: string, daily_retention_months: int, monthly_retention_years: int} $values */
    public function update(array $values): BackupSetting
    {
        $setting = $this->current();
        $setting->update($values);

        return $setting->refresh();
    }
}
