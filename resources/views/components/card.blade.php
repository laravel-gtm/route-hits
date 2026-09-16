<section {{ $attributes->merge(['class' => 'rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-200']) }}>
    <h2 class="mb-4 text-sm font-medium uppercase tracking-wide text-gray-500">{{ $title }}</h2>
    {{ $slot }}
</section>
