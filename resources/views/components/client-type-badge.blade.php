@props(['type'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-semibold',
    'border-blue-200 bg-blue-100 text-blue-800 dark:border-blue-500/30 dark:bg-blue-500/15 dark:text-blue-300' => $type === 'company',
    'border-violet-200 bg-violet-100 text-violet-800 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-400' => $type !== 'company',
]) }}>{{ __($type === 'company' ? 'Plan empresarial' : 'Plan personal') }}</span>
