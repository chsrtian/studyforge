@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-lg ps-3 pe-4 py-2 border-l-4 border-indigo-500 dark:border-indigo-300 text-start text-base font-semibold text-indigo-700 dark:text-indigo-100 bg-indigo-50 dark:bg-indigo-500/20 focus:outline-none focus:ring-2 focus:ring-primary/30 transition duration-150 ease-in-out'
            : 'block w-full rounded-lg ps-3 pe-4 py-2 border border-transparent text-start text-base font-medium text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-primary/30 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
