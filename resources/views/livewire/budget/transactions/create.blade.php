<?php

use App\Models\Transaction;
use App\Services\DeepseekService;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public string $name = '';
    public float|string $amount = '';
    public string $type = 'expense';
    public string $currency = 'RSD';
    public string $date = '';
    public ?string $category = null;

    // Voice input properties
    public string $inputMode = 'standard';
    public string $voiceTranscript = '';
    public string $voiceError = '';
    public bool $isParsing = false;

    public function mount(): void
    {
        $this->date = now()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:income,expense'],
            'currency' => ['required', 'in:RSD,EUR,USD'],
            'date' => ['required', 'date'],
            'category' => $this->type === 'expense' 
                ? ['required', 'in:bills,food,rest'] 
                : ['nullable', 'in:bills,food,rest'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Transaction name is required.',
            'name.min' => 'Name must be at least 2 characters.',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'category.required' => 'Category is required for expenses.',
        ];
    }

    public function updatedType(): void
    {
        if ($this->type === 'income') {
            $this->category = null;
        }
    }

    public function save(): void
    {
        $validated = $this->validate();

        Transaction::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'currency' => $validated['currency'],
            'date' => $validated['date'],
            'category' => $validated['category'],
        ]);

        session()->flash('message', 'Transaction created successfully!');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function parseVoiceInput(string $transcript): void
    {
        $this->voiceError = '';
        $this->voiceTranscript = $transcript;
        $this->isParsing = true;

        $deepseek = new DeepseekService();
        $result = $deepseek->parseTransaction($transcript);

        $this->isParsing = false;

        if ($result['error']) {
            $this->voiceError = $result['error'];

            return;
        }

        // Amount is required
        if ($result['amount'] === null) {
            $this->voiceError = 'Amount is required. Please try again and include an amount.';

            return;
        }

        // Fill in the form fields
        if ($result['name'] !== null) {
            $this->name = $result['name'];
        }

        $this->amount = $result['amount'];

        if ($result['type'] !== null && in_array($result['type'], Transaction::TYPES, true)) {
            $this->type = $result['type'];
            // Clear category if type changed to income
            if ($result['type'] === 'income') {
                $this->category = null;
            }
        }

        if ($result['currency'] !== null && in_array($result['currency'], Transaction::CURRENCIES, true)) {
            $this->currency = $result['currency'];
        }

        if ($result['category'] !== null && in_array($result['category'], Transaction::CATEGORIES, true)) {
            $this->category = $result['category'];
        }

        // Clear transcript after successful parsing
        $this->voiceTranscript = '';
    }

    public function clearVoiceError(): void
    {
        $this->voiceError = '';
    }
}; ?>

<section class="w-full page-enter">
    <div class="mx-auto w-full max-w-xl sm:px-0">
        {{-- Header --}}
        <div class="pb-3 sm:pb-5">
            <h1 class="text-xl font-semibold text-white">Add Transaction</h1>
            <p class="mt-0.5 text-sm text-zinc-500">Record a new income or expense</p>
        </div>

        {{-- Input Mode Toggle --}}
        <div class="mb-5 flex justify-center">
            <div x-data="{ selected: @entangle('inputMode') }" class="inline-flex rounded-lg border border-zinc-700/50 bg-zinc-800/60 p-0.5">
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-all"
                    :class="selected === 'standard' ? 'bg-zinc-700/60 text-white shadow-sm' : 'text-zinc-400 hover:text-white'"
                    @click="selected = 'standard'"
                >
                    Standard
                </button>
                <button
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium transition-all"
                    :class="selected === 'voice' ? 'bg-zinc-700/60 text-white shadow-sm' : 'text-zinc-400 hover:text-white'"
                    @click="selected = 'voice'"
                >
                    Voice
                </button>
            </div>
        </div>

        {{-- Voice Input Section --}}
        @if ($inputMode === 'voice')
            <div 
                class="card mb-4 sm:mb-5"
                x-data="{
                    isRecording: false,
                    transcript: @entangle('voiceTranscript'),
                    isSupported: ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window),
                    isSecureContext: window.isSecureContext,
                    recognition: null,
                    interimTranscript: '',
                    shouldRestart: false,
                    errorMessage: '',
                    
                    init() {
                        if (this.isSupported && this.isSecureContext) {
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                            this.recognition = new SpeechRecognition();
                            this.recognition.continuous = true;
                            this.recognition.interimResults = true;
                            this.recognition.lang = 'sr-RS';
                            this.recognition.maxAlternatives = 1;
                            
                            this.recognition.onresult = (event) => {
                                let interim = '';
                                let final = '';
                                
                                for (let i = 0; i < event.results.length; i++) {
                                    if (event.results[i].isFinal) {
                                        final += event.results[i][0].transcript + ' ';
                                    } else {
                                        interim += event.results[i][0].transcript;
                                    }
                                }
                                
                                if (final.trim()) {
                                    this.transcript = final.trim();
                                }
                                this.interimTranscript = interim;
                            };
                            
                            this.recognition.onend = () => {
                                if (this.shouldRestart && this.isRecording) {
                                    try {
                                        this.recognition.start();
                                    } catch (e) {
                                        this.isRecording = false;
                                        this.shouldRestart = false;
                                    }
                                } else {
                                    this.isRecording = false;
                                    this.shouldRestart = false;
                                }
                            };
                            
                            this.recognition.onerror = (event) => {
                                if (event.error === 'no-speech' || event.error === 'aborted') {
                                    return;
                                }
                                if (event.error === 'network') {
                                    this.errorMessage = 'Network error: Speech recognition requires internet. Use text input below instead.';
                                    this.isRecording = false;
                                    this.shouldRestart = false;
                                    return;
                                }
                                if (event.error === 'not-allowed') {
                                    this.errorMessage = 'Microphone access denied. Allow microphone access in your browser settings.';
                                    this.isRecording = false;
                                    this.shouldRestart = false;
                                    return;
                                }
                                this.errorMessage = 'Speech recognition error: ' + event.error;
                                this.isRecording = false;
                                this.shouldRestart = false;
                            };
                        }
                    },
                    
                    startRecording() {
                        this.transcript = '';
                        this.interimTranscript = '';
                        this.errorMessage = '';
                        this.shouldRestart = true;
                        $wire.clearVoiceError();
                        try {
                            this.recognition.start();
                            this.isRecording = true;
                        } catch (e) {
                            this.errorMessage = 'Failed to start recording. Please try again.';
                        }
                    },
                    
                    stopRecording() {
                        this.shouldRestart = false;
                        this.recognition.stop();
                        this.isRecording = false;
                    },
                    
                    toggleRecording() {
                        if (this.isRecording) {
                            this.stopRecording();
                        } else {
                            this.startRecording();
                        }
                    },
                    
                    parseTranscript() {
                        if (this.transcript.trim()) {
                            $wire.parseVoiceInput(this.transcript);
                        }
                    }
                }"
            >
                {{-- Error Message --}}
                <template x-if="errorMessage">
                    <div class="toast-error mb-4" x-text="errorMessage"></div>
                </template>

                <template x-if="isSupported && isSecureContext">
                    <div class="space-y-4">
                        {{-- Recording Button --}}
                        <div class="flex justify-center">
                            <button
                                type="button"
                                @click="toggleRecording()"
                                class="btn-press relative flex h-20 w-20 items-center justify-center rounded-full transition-all"
                                :class="isRecording ? 'bg-red-500 shadow-lg shadow-red-500/20' : 'bg-zinc-700 hover:bg-zinc-600'"
                                :aria-label="isRecording ? 'Stop recording' : 'Start recording'"
                            >
                                <svg x-show="!isRecording" class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 14c1.66 0 3-1.34 3-3V5c0-1.66-1.34-3-3-3S9 3.34 9 5v6c0 1.66 1.34 3 3 3z"/>
                                    <path d="M17 11c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-3.08c3.39-.49 6-3.39 6-6.92h-2z"/>
                                </svg>
                                <svg x-show="isRecording" class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <rect x="6" y="6" width="12" height="12" rx="2"/>
                                </svg>
                                @if (false)
                                    <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full bg-red-400 animate-pulse"></span>
                                @endif
                            </button>
                        </div>
                        
                        <p class="text-center text-sm text-zinc-400">
                            <span x-show="!isRecording">Tap to start recording</span>
                            <span x-show="isRecording" class="text-red-400">Listening... tap to stop</span>
                        </p>
                        
                        {{-- Transcript Display --}}
                        <div x-show="transcript || interimTranscript" x-transition class="rounded-lg bg-zinc-900/50 p-4">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wider text-zinc-500">Transcript</p>
                            <p class="text-sm text-white">
                                <span x-text="transcript"></span>
                                <span x-text="interimTranscript" class="text-zinc-500 italic"></span>
                            </p>
                        </div>
                        
                        {{-- Parse Button --}}
                        <div x-show="transcript && !isRecording" x-transition class="flex justify-center">
                            <x-form.button 
                                type="button" 
                                variant="primary" 
                                @click="parseTranscript()"
                                wire:loading.attr="disabled"
                                wire:target="parseVoiceInput"
                                class="btn-press"
                            >
                                <span wire:loading.remove wire:target="parseVoiceInput">Parse & Fill Form</span>
                                <span wire:loading wire:target="parseVoiceInput">Parsing...</span>
                            </x-form.button>
                        </div>
                        
                        {{-- Text input fallback --}}
                        <div x-show="errorMessage" x-transition class="mt-4 space-y-3 border-t border-zinc-700/50 pt-4">
                            <p class="text-center text-sm text-zinc-400">Or type your transaction:</p>
                            <x-form.input 
                                x-model="transcript"
                                placeholder="e.g., coffee 250 dinars food"
                            />
                            <div class="flex justify-center">
                                <x-form.button 
                                    type="button" 
                                    variant="primary" 
                                    @click="parseTranscript()"
                                    wire:loading.attr="disabled"
                                    wire:target="parseVoiceInput"
                                    class="btn-press"
                                >
                                    <span wire:loading.remove wire:target="parseVoiceInput">Parse & Fill Form</span>
                                    <span wire:loading wire:target="parseVoiceInput">Parsing...</span>
                                </x-form.button>
                            </div>
                        </div>
                    </div>
                </template>
                
                {{-- Fallback for unsupported browsers --}}
                <template x-if="!isSupported || !isSecureContext">
                    <div class="space-y-4">
                        <p class="text-center text-sm text-zinc-400" x-show="!isSecureContext">
                            Voice input requires HTTPS. Type your transaction instead:
                        </p>
                        <p class="text-center text-sm text-zinc-400" x-show="isSecureContext && !isSupported">
                            Voice input is not supported in your browser. Type your transaction instead:
                        </p>
                        <x-form.input 
                            wire:model="voiceTranscript"
                            placeholder="e.g., coffee 250 dinars food"
                        />
                        <div class="flex justify-center">
                            <x-form.button 
                                type="button" 
                                variant="primary" 
                                wire:click="parseVoiceInput($wire.voiceTranscript)"
                                wire:loading.attr="disabled"
                                wire:target="parseVoiceInput"
                                class="btn-press"
                            >
                                <span wire:loading.remove wire:target="parseVoiceInput">Parse & Fill Form</span>
                                <span wire:loading wire:target="parseVoiceInput">Parsing...</span>
                            </x-form.button>
                        </div>
                    </div>
                </template>
                
                {{-- Voice Error --}}
                @if ($voiceError)
                    <div class="toast-error mt-4">{{ $voiceError }}</div>
                @endif
            </div>
        @endif

        <form wire:submit="save" class="card space-y-4 sm:space-y-5">
            @if (session('message'))
                <div class="toast-success">{{ session('message') }}</div>
            @endif

            {{-- Name and Type --}}
            <div class="flex gap-3 sm:gap-4">
                <div class="w-2/3">
                    <x-form.input 
                        wire:model="name" 
                        label="{{ __('Name') }}" 
                        placeholder="Transaction name"
                        autofocus
                    />
                    @error('name')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="w-1/3">
                    <x-form.select wire:model.live="type" label="{{ __('Type') }}">
                        <option value="expense">Expense</option>
                        <option value="income">Income</option>
                    </x-form.select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Category (only for expenses) --}}
            @if ($type === 'expense')
                <div>
                    <x-form.select wire:model="category" label="{{ __('Category') }}">
                        <option value="">Select category</option>
                        @foreach (Transaction::CATEGORY_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                    @error('category')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Amount and Currency --}}
            <div class="flex gap-3 sm:gap-4">
                <div class="w-2/3">
                    <x-form.input 
                        wire:model="amount" 
                        label="{{ __('Amount') }}" 
                        type="text"
                        placeholder="0.00"
                        x-mask:dynamic="$money($input, '.', '')"
                    />
                    @error('amount')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
               
                <div class="w-1/3">
                    <x-form.select wire:model="currency" label="{{ __('Currency') }}">
                        @foreach (Transaction::CURRENCIES as $curr)
                            <option value="{{ $curr }}">{{ $curr }}</option>
                        @endforeach
                    </x-form.select>
                    @error('currency')
                        <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Date --}}
            <div>
                <x-form.date-picker 
                    wire:model="date"
                    label="{{ __('Date') }}"
                    placeholder="Select date"
                />
                @error('date')
                    <p class="mt-1 text-xs text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit --}}
            <div class="pt-2">
                <x-form.button type="submit" variant="primary" class="btn-press w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove>Save Transaction</span>
                    <span wire:loading>Saving...</span>
                </x-form.button>
            </div>
        </form>
    </div>
</section>
