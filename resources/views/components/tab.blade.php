@props([
    'name' => null,
])

<button
    type="button"
    {{ $attributes->merge(['class' => 'rounded-md px-3 py-1.5 text-sm font-medium transition-all text-zinc-400 hover:text-white']) }}
    :class="{ 'bg-zinc-700/60 text-white shadow-sm': selected === '{{ $name }}' }"
    @click="selected = '{{ $name }}'; $dispatch('change', '{{ $name }}')"
>
    {{ $slot }}
</button>
