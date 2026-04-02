@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-3 py-2 text-sm font-semibold leading-5 text-primary dark:text-indigo-100 bg-indigo-50 dark:bg-indigo-500/20 rounded-xl transition duration-150 ease-in-out self-center border-b-2 border-primary dark:border-indigo-300 shadow-sm ring-1 ring-indigo-200/70 dark:ring-indigo-400/25'
            : 'inline-flex items-center px-3 py-2 text-sm font-medium leading-5 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition duration-150 ease-in-out self-center border-b-2 border-transparent';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
