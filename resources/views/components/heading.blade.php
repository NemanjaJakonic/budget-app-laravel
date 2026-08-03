@props([
    'as' => 'h2',
])

<{{ $as }} {{ $attributes->merge(['class' => 'text-lg font-semibold text-white']) }}>
    {{ $slot }}
</{{ $as }}>
