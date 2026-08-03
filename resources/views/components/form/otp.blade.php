@props([
    'label' => null,
    'name' => null,
    'size' => 6,
])

<div x-data="{ code: Array({{ $size }}).fill(''), inputs: [] }" x-init="$nextTick(() => { inputs = $el.querySelectorAll('input[type=text]'); inputs[0]?.focus() })">
    @if($label)
        <label class="mb-1.5 block text-sm font-medium text-zinc-300">{{ $label }}</label>
    @endif

    <div class="flex gap-2">
        @for($i = 0; $i < $size; $i++)
            <input
                type="text"
                maxlength="1"
                inputmode="numeric"
                class="size-10 rounded-lg border border-zinc-600/60 bg-zinc-800/80 text-center text-lg font-semibold text-white transition-all focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/30"
                x-model="code[{{ $i }}]"
                @input="
                    if (code[{{ $i }}] && {{ $i }} < {{ $size - 1 }}) {
                        inputs[{{ $i + 1 }}]?.focus();
                    }
                    $dispatch('input', code.join(''))
                "
                @keydown.backspace="
                    if (!code[{{ $i }}] && {{ $i }} > 0) {
                        code[{{ $i - 1 }}] = '';
                        inputs[{{ $i - 1 }}]?.focus();
                    }
                "
            />
        @endfor
    </div>

    <input type="hidden" {{ $attributes }} :value="code.join('')" />
</div>
