@props([
    'class' => '',
])

<hr {{ $attributes->merge(['class' => "border-zinc-700/50 $class"]) }} />
