@props([
    'label' => null,
    'name' => null,
    'checked' => false,
])

<label class="flex items-center gap-2.5 cursor-pointer select-none group">
    <input
        type="checkbox"
        {{ $attributes->merge(['class' => 'size-4 rounded border-zinc-600 bg-zinc-800 text-accent focus:ring-accent/30 focus:ring-2 transition']) }}
        @if($checked) checked @endif
    />
    @if($label || $slot->isNotEmpty())
        <span class="text-sm text-zinc-300 group-hover:text-zinc-200 transition-colors">{{ $label ?? $slot }}</span>
    @endif
</label>
