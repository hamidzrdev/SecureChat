<?php

use Livewire\Component;

new class extends Component
{
    public string $mode = 'public';

    public string $state = 'filled';

    public array $messages = [];

    public function mount(string $mode = 'public', string $state = 'filled'): void
    {
        $this->mode = $mode;
        $this->state = $state;

        $baseMessages = [
            [
                'id' => 1,
                'day' => 'Today',
                'outgoing' => false,
                'type' => 'text',
                'author' => 'Ava Hart',
                'body' => 'I pushed a clean draft for the onboarding copy. Can you review now?',
                'time' => '09:14',
                'status' => 'seen',
            ],
            [
                'id' => 2,
                'day' => 'Today',
                'outgoing' => true,
                'type' => 'text',
                'author' => 'You',
                'body' => 'Reviewing now. The first section reads much clearer.',
                'time' => '09:16',
                'status' => 'delivered',
            ],
            [
                'id' => 3,
                'day' => 'Today',
                'outgoing' => false,
                'type' => 'image',
                'author' => 'Ava Hart',
                'caption' => 'Wireframe v3',
                'image' => 'https://images.unsplash.com/photo-1516387938699-a93567ec168e?auto=format&fit=crop&w=900&q=80',
                'time' => '09:18',
                'status' => 'seen',
            ],
            [
                'id' => 4,
                'day' => 'Today',
                'outgoing' => true,
                'type' => 'image',
                'author' => 'You',
                'caption' => 'Spacing notes',
                'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?auto=format&fit=crop&w=900&q=80',
                'time' => '09:20',
                'status' => 'read',
            ],
        ];

        $privateExtra = [
            [
                'id' => 5,
                'day' => 'Today',
                'outgoing' => false,
                'type' => 'text',
                'author' => '@noah_kim',
                'body' => 'Switching to passphrase mode for this thread.',
                'time' => '09:23',
                'status' => 'seen',
            ],
        ];

        $this->messages = $mode === 'private' ? [...$baseMessages, ...$privateExtra] : $baseMessages;

        if ($this->state !== 'filled') {
            $this->messages = [];
        }
    }
};
?>

<div class="h-[28rem] overflow-y-auto px-4 py-4 sm:px-5">
    @if ($state === 'loading')
        <div class="space-y-4">
            @for ($i = 0; $i < 6; $i++)
                <div class="animate-pulse">
                    <div class="mb-2 h-3 w-16 rounded bg-slate-200 dark:bg-slate-800"></div>
                    <div class="h-14 rounded-2xl bg-slate-200 dark:bg-slate-800"></div>
                </div>
            @endfor
        </div>
    @elseif (count($messages) === 0)
        <div class="flex h-full items-center justify-center">
            <div class="max-w-sm text-center">
                <x-avatar name="Empty" size="lg" class="mx-auto" />
                <h3 class="mt-3 text-base font-semibold text-slate-900 dark:text-slate-100">No messages yet</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Start the conversation by sending your first message.</p>
            </div>
        </div>
    @else
        @php
            $currentDay = null;
        @endphp

        <div class="space-y-4 pb-24 lg:pb-6">
            @foreach ($messages as $message)
                @if ($currentDay !== $message['day'])
                    @php
                        $currentDay = $message['day'];
                    @endphp

                    <div class="flex items-center gap-3 py-1">
                        <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                        <span class="text-[11px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500">{{ $message['day'] }}</span>
                        <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                    </div>
                @endif

                <article @class(['group flex items-end gap-3', 'justify-end' => $message['outgoing']])>
                    @if (! $message['outgoing'])
                        <x-avatar :name="$message['author']" size="sm" />
                    @endif

                    <div class="max-w-[85%] sm:max-w-[72%]">
                        @if ($message['type'] === 'text')
                            <div @class([
                                'rounded-2xl border px-3.5 py-2.5 text-sm shadow-soft',
                                'border-brand-500 bg-brand-600 text-white' => $message['outgoing'],
                                'border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100' => ! $message['outgoing'],
                            ])>
                                <p class="leading-relaxed">{{ $message['body'] }}</p>
                            </div>
                        @else
                            <div @class([
                                'relative overflow-hidden rounded-2xl border shadow-soft',
                                'border-brand-500' => $message['outgoing'],
                                'border-slate-200 dark:border-slate-700' => ! $message['outgoing'],
                            ])>
                                <button
                                    type="button"
                                    class="block focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                                    x-on:click="$dispatch('open-image-lightbox', { src: '{{ $message['image'] }}', caption: '{{ $message['caption'] }}', author: '{{ $message['author'] }}', time: '{{ $message['time'] }}' })"
                                >
                                    <img src="{{ $message['image'] }}" alt="{{ $message['caption'] }}" class="h-52 w-full object-cover sm:h-64" />
                                </button>

                                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/55 to-transparent opacity-0 transition group-hover:opacity-100"></div>
                                <div class="absolute bottom-2 left-2 right-2 flex items-center justify-between opacity-0 transition group-hover:opacity-100">
                                    <p class="rounded-md bg-black/50 px-2 py-1 text-xs text-white">{{ $message['caption'] }}</p>
                                    <div class="flex items-center gap-1.5">
                                        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-black/45 text-white transition hover:bg-black/70">
                                            <span class="sr-only">Share image</span>
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M7 10h6M10 7l3 3-3 3" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M4 4h6a3 3 0 0 1 3 3v1M16 16h-6a3 3 0 0 1-3-3v-1" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <button type="button" class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-black/45 text-white transition hover:bg-black/70">
                                            <span class="sr-only">Download image</span>
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path d="M10 3v9m0 0l3-3m-3 3L7 9" stroke-linecap="round" stroke-linejoin="round" />
                                                <path d="M4 14.5A1.5 1.5 0 0 0 5.5 16h9a1.5 1.5 0 0 0 1.5-1.5" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div @class([
                            'mt-1.5 flex items-center gap-1.5 text-[11px]',
                            'justify-end text-slate-500 dark:text-slate-400' => $message['outgoing'],
                            'text-slate-500 dark:text-slate-400' => ! $message['outgoing'],
                        ])>
                            <span>{{ $message['time'] }}</span>

                            @if ($message['outgoing'])
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M4.5 10.5l3 3 8-8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <svg class="-ml-1.5 h-3.5 w-3.5 text-brand-500" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M4.5 10.5l3 3 8-8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            @else
                                <span class="truncate">{{ $message['author'] }}</span>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
