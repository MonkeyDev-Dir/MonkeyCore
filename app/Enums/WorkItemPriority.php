<?php

namespace App\Enums;

enum WorkItemPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::Low => __('Baja'),
            self::Medium => __('Media'),
            self::High => __('Alta'),
            self::Critical => __('Crítica'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Low => 'bg-gray-400',
            self::Medium => 'bg-blue-500',
            self::High => 'bg-orange-500',
            self::Critical => 'bg-red-500',
        };
    }
}
