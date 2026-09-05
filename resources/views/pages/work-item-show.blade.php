@extends('layouts.app')

@section('title', $workItem->title)

@section('content')
    <div class="space-y-6">
        <a href="{{ route('work-items.index') }}" class="text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400">{{ __('Volver a casos') }}</a>

        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] lg:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex min-w-0 items-start gap-3">
                    <span class="mt-2 size-3 shrink-0 rounded-full {{ $workItem->priority->color() }}" title="{{ __('Prioridad: :priority', ['priority' => $workItem->priority->label()]) }}" aria-label="{{ __('Prioridad del caso') }}"></span>
                    <div class="min-w-0">
                        <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $workItem->title }}</h1>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $workItem->public_code }} · {{ $workItem->type?->name ?? __('Sin tipo') }}</p>
                    </div>
                </div>
                <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-medium {{ $workItem->status->color() }}">{{ $workItem->status->label() }}</span>
            </div>
        </div>

        <livewire:work-items.work-item-editor :work-item="$workItem" />
    </div>
@endsection
