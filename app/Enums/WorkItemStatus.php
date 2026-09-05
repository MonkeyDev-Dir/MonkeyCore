<?php

namespace App\Enums;

enum WorkItemStatus: string
{
    case New = 'new';
    case Assigned = 'assigned';
    case UnderAnalysis = 'under_analysis';
    case WaitingForCustomer = 'waiting_for_customer';
    case WaitingForThirdParty = 'waiting_for_third_party';
    case InDevelopment = 'in_development';
    case InTesting = 'in_testing';
    case Resolved = 'resolved';
    case Closed = 'closed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => __('Nuevo'),
            self::Assigned => __('Asignado'),
            self::UnderAnalysis => __('En análisis'),
            self::WaitingForCustomer => __('Esperando cliente'),
            self::WaitingForThirdParty => __('Esperando tercero'),
            self::InDevelopment => __('En desarrollo'),
            self::InTesting => __('En pruebas'),
            self::Resolved => __('Resuelto'),
            self::Closed => __('Cerrado'),
            self::Cancelled => __('Cancelado'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400',
            self::Assigned => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400',
            self::UnderAnalysis => 'bg-violet-100 text-violet-700 dark:bg-violet-500/10 dark:text-violet-400',
            self::WaitingForCustomer => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
            self::WaitingForThirdParty => 'bg-orange-100 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400',
            self::InDevelopment => 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400',
            self::InTesting => 'bg-cyan-100 text-cyan-700 dark:bg-cyan-500/10 dark:text-cyan-400',
            self::Resolved => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
            self::Closed => 'bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400',
            self::Cancelled => 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400',
        };
    }
}
