<?php

use Livewire\Component;

new class extends Component
{
    public array $emojiGroups = [];

    public function mount(): void
    {
        $this->emojiGroups = [
            'Smileys' => ['😀', '😁', '😂', '😊', '😍', '😎', '🤔', '😴'],
            'Gestures' => ['👍', '👏', '🙏', '🔥', '✅', '🙌', '💡', '🎉'],
            'Objects' => ['📌', '📎', '🔒', '🔔', '📷', '💬', '🧩', '🚀'],
        ];
    }
};
?>

<div class="w-72 rounded-2xl border border-slate-200 bg-white p-3 shadow-soft dark:border-slate-700 dark:bg-slate-900">
    <div class="mb-3 flex items-center justify-between">
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">Emoji picker</p>
        <x-badge size="sm" variant="secondary">UI only</x-badge>
    </div>

    <div class="space-y-3">
        @foreach ($emojiGroups as $groupName => $emojis)
            <section>
                <h4 class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $groupName }}</h4>
                <div class="grid grid-cols-8 gap-1.5">
                    @foreach ($emojis as $emoji)
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-lg transition hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 dark:hover:bg-slate-800"
                            x-on:click="$dispatch('composer-emoji-picked', { emoji: '{{ $emoji }}' })"
                            aria-label="Insert emoji {{ $emoji }}"
                        >
                            <span>{{ $emoji }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
