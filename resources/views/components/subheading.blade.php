@props([])

<p {{ $attributes->merge(['class' => 'text-sm text-zinc-400']) }}>
    {{ $slot }}
</p>
