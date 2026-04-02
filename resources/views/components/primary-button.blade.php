<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 whitespace-nowrap px-5 py-2.5 bg-primary border border-transparent rounded-lg font-semibold text-sm !text-white hover:bg-primary-dark focus:bg-primary-dark active:bg-primary-dark/95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors duration-200 shadow-sm disabled:opacity-60 disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
