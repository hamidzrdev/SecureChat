@props([
    'title' => __('chat.shell.default_title'),
    'appName' => config('app.name', 'SChat'),
    'chatId' => 'chat_8F2A',
])

@php
    $isRtl = app()->isLocale('fa');
    $menuAlignmentClass = $isRtl ? 'left-0' : 'right-0';
    $drawerPositionClass = $isRtl ? 'left-0 border-r' : 'right-0 border-l';
    $drawerEnterStartClass = $isRtl ? '-translate-x-6 opacity-0' : 'translate-x-6 opacity-0';
    $drawerLeaveEndClass = $isRtl ? '-translate-x-6 opacity-0' : 'translate-x-6 opacity-0';
    $displayChatId = (string) ($chatId ?: (auth()->user()?->chat_id ?? 'guest'));
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700|vazirmatn:400,500,600,700" rel="stylesheet" />

        <script>
            (() => {
                const storageKey = 'schat-theme';
                const root = document.documentElement;
                const systemScheme = window.matchMedia('(prefers-color-scheme: dark)');
                const themeLabels = {
                    switchToLight: @js(__('chat.theme.switch_to_light')),
                    switchToDark: @js(__('chat.theme.switch_to_dark')),
                };

                const canUseStorage = () => {
                    try {
                        localStorage.getItem(storageKey);
                        return true;
                    } catch (error) {
                        return false;
                    }
                };

                const getSavedTheme = () => {
                    if (! canUseStorage()) {
                        return null;
                    }

                    return localStorage.getItem(storageKey);
                };

                const syncThemeToggles = (theme) => {
                    document.querySelectorAll('[data-theme-toggle]').forEach((toggle) => {
                        const lightIcon = toggle.querySelector('[data-theme-icon="light"]');
                        const darkIcon = toggle.querySelector('[data-theme-icon="dark"]');

                        toggle.dataset.theme = theme;
                        toggle.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
                        toggle.setAttribute('aria-label', theme === 'dark' ? themeLabels.switchToLight : themeLabels.switchToDark);

                        if (lightIcon) {
                            lightIcon.classList.toggle('hidden', theme !== 'light');
                        }

                        if (darkIcon) {
                            darkIcon.classList.toggle('hidden', theme !== 'dark');
                        }

                    });
                };

                const resolveTheme = () => {
                    const savedTheme = getSavedTheme();

                    if (savedTheme === 'light' || savedTheme === 'dark') {
                        return savedTheme;
                    }

                    return systemScheme.matches ? 'dark' : 'light';
                };

                const applyTheme = (theme) => {
                    root.classList.toggle('dark', theme === 'dark');
                    if (document.body) {
                        document.body.classList.toggle('dark', theme === 'dark');
                    }
                    root.dataset.theme = theme;
                    root.style.colorScheme = theme;
                    syncThemeToggles(theme);
                };

                const notifyThemeChange = (theme) => {
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
                };

                const initialTheme = resolveTheme();
                applyTheme(initialTheme);

                window.setAppTheme = (theme) => {
                    if (canUseStorage()) {
                        localStorage.setItem(storageKey, theme);
                    }

                    applyTheme(theme);
                    notifyThemeChange(theme);
                };

                window.toggleAppTheme = () => {
                    const nextTheme = root.classList.contains('dark') ? 'light' : 'dark';
                    window.setAppTheme(nextTheme);
                };

                const handleSystemThemeChange = (event) => {
                    if (getSavedTheme()) {
                        return;
                    }

                    const nextTheme = event.matches ? 'dark' : 'light';
                    applyTheme(nextTheme);
                    notifyThemeChange(nextTheme);
                };

                if (typeof systemScheme.addEventListener === 'function') {
                    systemScheme.addEventListener('change', handleSystemThemeChange);
                } else if (typeof systemScheme.addListener === 'function') {
                    systemScheme.addListener(handleSystemThemeChange);
                }

                window.addEventListener('DOMContentLoaded', () => {
                    applyTheme(root.classList.contains('dark') ? 'dark' : 'light');
                });
            })();
        </script>

        <style>
            [x-cloak] {
                display: none !important;
            }

            :root {
                color-scheme: light;
            }

            :root.dark {
                color-scheme: dark;
            }

            html[lang^='fa'] body {
                font-family: Vazirmatn, ui-sans-serif, system-ui, sans-serif;
            }

            @keyframes schat-heartbeat {
                0%, 100% {
                    transform: scale(1);
                }
                25% {
                    transform: scale(1.14);
                }
                45% {
                    transform: scale(0.97);
                }
                70% {
                    transform: scale(1.08);
                }
            }

            @keyframes schat-steam {
                0%, 100% {
                    transform: translateY(0);
                    opacity: 0.45;
                }
                50% {
                    transform: translateY(-2px);
                    opacity: 1;
                }
            }

            @keyframes schat-github-bob {
                0%, 100% {
                    transform: translateY(0) rotate(0deg);
                }
                50% {
                    transform: translateY(-2px) rotate(5deg);
                }
            }

            .schat-heartbeat {
                animation: schat-heartbeat 1.4s ease-in-out infinite;
                transform-origin: center;
            }

            .schat-steam {
                animation: schat-steam 1.8s ease-in-out infinite;
                transform-origin: center;
            }

            .schat-github-bob {
                animation: schat-github-bob 2.1s ease-in-out infinite;
                transform-origin: center;
            }
        </style>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="h-full text-slate-900 antialiased dark:text-brand-50">
        <div
            x-data="{ sidebarOpen: false, desktopSidebarOpen: true, menuOpen: false }"
            @toggle-sidebar.window="if (window.innerWidth >= 1024) { desktopSidebarOpen = ! desktopSidebarOpen } else { sidebarOpen = true }"
            @chat-mobile-close-sidebar.window="sidebarOpen = false"
            @keydown.escape.window="sidebarOpen = false; menuOpen = false"
            class="relative min-h-full"
        >
            <header class="sticky top-0 z-40 border-b border-amber-200/70 bg-white/82 backdrop-blur-xl dark:border-brand-500/30 dark:bg-zinc-950/84">
                <div class="mx-auto flex h-16 w-full max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-300/90 bg-white/90 text-slate-700 shadow-soft transition hover:border-brand-300 hover:bg-white dark:border-brand-500/40 dark:bg-zinc-900/80 dark:text-brand-100 dark:hover:border-brand-400/70 dark:hover:bg-zinc-900 lg:hidden"
                            x-on:click="sidebarOpen = true"
                        >
                            <span class="sr-only">{{ __('chat.shell.open_sidebar') }}</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round" />
                            </svg>
                        </button>

                        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-300 to-brand-600 text-sm font-semibold text-zinc-950 shadow-soft ring-1 ring-brand-200/60">S</span>
                            <span class="text-sm font-semibold tracking-tight text-slate-800 dark:text-brand-100">{{ $appName }}</span>
                        </a>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <form method="POST" action="{{ route('locale.update') }}" class="hidden items-center overflow-hidden rounded-xl border border-amber-300/90 bg-white/90 text-xs font-semibold text-slate-700 shadow-soft dark:border-brand-500/35 dark:bg-zinc-900/85 dark:text-brand-100 sm:inline-flex">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

                            <button
                                type="submit"
                                name="locale"
                                value="en"
                                aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex items-center gap-1 px-2.5 py-1.5 transition hover:bg-amber-100 dark:hover:bg-brand-500/16',
                                    'bg-brand-500 text-zinc-950 hover:bg-brand-500' => app()->getLocale() === 'en',
                                ])
                            >
                                {{ __('chat.language.english_short') }}
                            </button>
                            <button
                                type="submit"
                                name="locale"
                                value="fa"
                                aria-pressed="{{ app()->getLocale() === 'fa' ? 'true' : 'false' }}"
                                @class([
                                    'inline-flex items-center gap-1 px-2.5 py-1.5 transition hover:bg-amber-100 dark:hover:bg-brand-500/16',
                                    'bg-brand-500 text-zinc-950 hover:bg-brand-500' => app()->getLocale() === 'fa',
                                ])
                            >
                                {{ __('chat.language.persian_short') }}
                            </button>
                        </form>

                        <button
                            type="button"
                            data-theme-toggle
                            aria-pressed="false"
                            aria-label="{{ __('chat.theme.switch_label') }}"
                            onclick="window.toggleAppTheme()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-300/90 bg-white/90 text-slate-700 shadow-soft transition hover:border-brand-300 hover:bg-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-300 dark:border-brand-500/35 dark:bg-zinc-900/86 dark:text-brand-200 dark:hover:bg-zinc-900"
                        >
                            <svg data-theme-icon="light" class="h-4 w-4 text-amber-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10 3.2a.8.8 0 0 1 .8.8v.5a.8.8 0 1 1-1.6 0V4a.8.8 0 0 1 .8-.8Zm0 11.5a.8.8 0 0 1 .8.8v.5a.8.8 0 1 1-1.6 0v-.5a.8.8 0 0 1 .8-.8Z" />
                                <circle cx="10" cy="10" r="3.1" />
                            </svg>
                            <svg data-theme-icon="dark" class="hidden h-4 w-4 text-brand-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M10.65 2.1a.75.75 0 0 0-.97.83 6.35 6.35 0 0 1-7.07 7.06.75.75 0 0 0-.83.98A8.34 8.34 0 1 0 10.65 2.1Z" />
                            </svg>
                            <span class="sr-only">{{ __('chat.theme.switch_label') }}</span>
                        </button>

                        <div class="relative">
                            <button
                                type="button"
                                class="inline-flex h-9 items-center gap-2 rounded-xl border border-amber-300/90 bg-white/90 px-3 text-xs font-semibold text-slate-700 shadow-soft transition hover:border-brand-300 hover:bg-white dark:border-brand-500/35 dark:bg-zinc-900/88 dark:text-brand-100 dark:hover:border-brand-400/60 dark:hover:bg-zinc-900"
                                x-on:click="menuOpen = ! menuOpen"
                            >
                                <span class="max-w-28 truncate">{{ '@'.$displayChatId }}</span>
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </button>

                            <div
                                x-cloak
                                x-show="menuOpen"
                                x-on:click.outside="menuOpen = false"
                                x-transition
                                class="absolute {{ $menuAlignmentClass }} mt-2 w-44 overflow-hidden rounded-2xl border border-amber-200/80 bg-white/96 p-1.5 shadow-[var(--shadow-floating)] backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/96"
                            >
                                @auth
                                    <form method="POST" action="{{ route('chat.logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full rounded-xl px-3 py-2 text-start text-sm font-medium text-rose-600 transition hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/14">
                                            {{ __('chat.shell.menu_sign_out') }}
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('chat.login') }}" class="block rounded-xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-amber-100 dark:text-brand-100 dark:hover:bg-brand-500/12">
                                        {{ __('chat.pages.login_title') }}
                                    </a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]">
                    <section class="min-h-[calc(100vh-9.5rem)]">
                        {{ $slot }}
                    </section>

                    <aside class="hidden lg:block" x-show="desktopSidebarOpen" x-transition.opacity.duration.150ms>
                        @isset($sidebar)
                            {{ $sidebar }}
                        @else
                            <x-card title="{{ __('chat.shell.sidebar_title') }}" description="{{ __('chat.shell.sidebar_description') }}">
                                <p class="text-sm text-slate-500 dark:text-brand-200/65">{{ __('chat.shell.sidebar_empty') }}</p>
                            </x-card>
                        @endisset
                    </aside>
                </div>

                <footer class="mt-6 rounded-2xl border border-amber-200/80 bg-white/80 px-4 py-3 text-xs text-slate-600 shadow-soft backdrop-blur-sm dark:border-brand-500/30 dark:bg-zinc-950/78 dark:text-brand-200/75">
                    <a
                        href="https://github.com/hamidzrdev"
                        target="_blank"
                        rel="noopener noreferrer"
                        dir="{{ $isRtl ? 'rtl' : 'ltr' }}"
                        aria-label="{{ __('chat.shell.footer_github_aria') }}"
                        class="group mx-auto inline-flex flex-wrap items-center justify-center gap-2 rounded-xl px-2 py-1.5 text-center transition hover:bg-amber-100/70 dark:hover:bg-brand-500/12 {{ $isRtl ? 'text-right' : 'text-left' }}"
                    >
                        <span class="font-medium text-black dark:text-white">{{ __('chat.shell.footer_made_with') }}</span>
                        <span class="inline-flex items-center text-rose-500 dark:text-rose-500 schat-heartbeat" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 17.2c-.2 0-.4-.1-.6-.2C5 14.4 2 11.7 2 8.3 2 5.9 3.9 4 6.3 4c1.5 0 2.8.7 3.7 1.9A4.6 4.6 0 0 1 13.7 4C16.1 4 18 5.9 18 8.3c0 3.4-3 6.1-7.4 8.7-.2.1-.4.2-.6.2Z"/>
                            </svg>
                        </span>
                        <span>+</span>
                        <span class="inline-flex items-center text-amber-600 dark:text-amber-300 schat-steam" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4.5 8A1.5 1.5 0 0 0 3 9.5v3A3.5 3.5 0 0 0 6.5 16h6a3.5 3.5 0 0 0 3.3-2.4h.7A2.5 2.5 0 0 0 19 11.1 2.5 2.5 0 0 0 16.5 8.6h-.5A1.5 1.5 0 0 0 14.5 8h-10Zm11 4V10h1a1 1 0 0 1 0 2h-1Zm-8.9-6.2c.5-.4.6-1 .3-1.6-.2-.4-.1-.7.2-1 .3-.3.4-.8.1-1.1a.8.8 0 0 0-1.1 0c-.7.7-.9 1.8-.4 2.8.1.2.1.3-.1.5-.3.3-.3.8 0 1.1.3.3.8.3 1.1 0Zm3.1 0c.5-.4.6-1 .3-1.6-.2-.4-.1-.7.2-1 .3-.3.4-.8.1-1.1a.8.8 0 0 0-1.1 0c-.7.7-.9 1.8-.4 2.8.1.2.1.3-.1.5-.3.3-.3.8 0 1.1.3.3.8.3 1.1 0Z"/>
                            </svg>
                        </span>
                        <span>=</span>
                        <span class="inline-flex items-center text-slate-700 transition group-hover:text-slate-900 dark:text-brand-100 dark:group-hover:text-brand-50 schat-github-bob" aria-hidden="true">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 .5a12 12 0 0 0-3.8 23.4c.6.1.8-.2.8-.6v-2.2c-3.3.7-4-1.4-4-1.4-.6-1.3-1.3-1.7-1.3-1.7-1.1-.7.1-.7.1-.7 1.2.1 1.8 1.3 1.8 1.3 1.1 1.9 2.9 1.4 3.6 1.1.1-.8.4-1.4.8-1.8-2.7-.3-5.5-1.4-5.5-6 0-1.3.5-2.4 1.3-3.3-.1-.3-.6-1.6.1-3.3 0 0 1.1-.4 3.5 1.3a11.7 11.7 0 0 1 6.3 0c2.4-1.7 3.5-1.3 3.5-1.3.7 1.7.2 3 .1 3.3.8.9 1.3 2 1.3 3.3 0 4.6-2.8 5.7-5.5 6 .4.4.9 1.1.9 2.2v3.2c0 .4.2.7.8.6A12 12 0 0 0 12 .5Z"/>
                            </svg>
                        </span>
                        <span class="font-semibold text-slate-700 transition group-hover:text-slate-900 dark:text-brand-100 dark:group-hover:text-brand-50">hamidzrdev</span>
                    </a>
                </footer>
            </main>

            <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="sidebarOpen = false"></div>

                <aside
                    class="absolute {{ $drawerPositionClass }} top-0 h-full w-full max-w-sm overflow-y-auto border-amber-200/80 bg-white/96 p-4 backdrop-blur-sm dark:border-brand-500/28 dark:bg-zinc-950/96"
                    x-show="sidebarOpen"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="{{ $drawerEnterStartClass }}"
                    x-transition:enter-end="translate-x-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-x-0 opacity-100"
                    x-transition:leave-end="{{ $drawerLeaveEndClass }}"
                >
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900 dark:text-brand-100">{{ __('chat.shell.sidebar_title') }}</h2>
                        <button
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-300/90 bg-white text-slate-700 hover:border-brand-300 hover:bg-white dark:border-brand-500/35 dark:bg-zinc-900 dark:text-brand-100 dark:hover:border-brand-400/65 dark:hover:bg-zinc-900"
                            x-on:click="sidebarOpen = false"
                        >
                            <span class="sr-only">{{ __('chat.shell.close_sidebar') }}</span>
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M5 5l10 10M15 5L5 15" stroke-linecap="round" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('locale.update') }}" class="mb-4 flex items-center overflow-hidden rounded-xl border border-amber-300/90 bg-white/90 text-xs font-semibold text-slate-700 shadow-soft dark:border-brand-500/35 dark:bg-zinc-900/85 dark:text-brand-100 sm:hidden">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                        <button
                            type="submit"
                            name="locale"
                            value="en"
                            aria-pressed="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}"
                            @class([
                                'inline-flex w-1/2 items-center justify-center gap-1 px-2.5 py-1.5 transition hover:bg-amber-100 dark:hover:bg-brand-500/16',
                                'bg-brand-500 text-zinc-950 hover:bg-brand-500' => app()->getLocale() === 'en',
                            ])
                        >
                            {{ __('chat.language.english') }}
                        </button>
                        <button
                            type="submit"
                            name="locale"
                            value="fa"
                            aria-pressed="{{ app()->getLocale() === 'fa' ? 'true' : 'false' }}"
                            @class([
                                'inline-flex w-1/2 items-center justify-center gap-1 px-2.5 py-1.5 transition hover:bg-amber-100 dark:hover:bg-brand-500/16',
                                'bg-brand-500 text-zinc-950 hover:bg-brand-500' => app()->getLocale() === 'fa',
                            ])
                        >
                            {{ __('chat.language.persian') }}
                        </button>
                    </form>

                    @isset($mobileSidebar)
                        {{ $mobileSidebar }}
                    @elseif (isset($sidebar))
                        {{ $sidebar }}
                    @else
                        <x-card title="{{ __('chat.shell.sidebar_title') }}" description="{{ __('chat.shell.sidebar_description') }}">
                            <p class="text-sm text-slate-500 dark:text-brand-200/65">{{ __('chat.shell.sidebar_empty') }}</p>
                        </x-card>
                    @endisset
                </aside>
            </div>
        </div>

        <script>
            window.SCHAT_DEBUG = @js((bool) config('app.debug', false));

            window.addEventListener('chat-debug', (event) => {
                if (!window.SCHAT_DEBUG) {
                    return;
                }

                console.info('[SChat][Livewire Event]', event.detail);
            });

            document.addEventListener('livewire:init', () => {
                if (!window.SCHAT_DEBUG || !window.Livewire) {
                    return;
                }

                console.info('[SChat] Debug hooks enabled');

                window.Livewire.on('chat-debug', (payload) => {
                    console.info('[SChat][Livewire.on chat-debug]', payload);
                });

                const logDuplicateWireIds = () => {
                    const ids = Array.from(document.querySelectorAll('[wire\\:id]'))
                        .map((el) => el.getAttribute('wire:id'))
                        .filter(Boolean);
                    const duplicateIds = ids.filter((id, index) => ids.indexOf(id) !== index);

                    if (duplicateIds.length > 0) {
                        console.warn('[SChat][Livewire] duplicate wire:id detected', [...new Set(duplicateIds)]);
                    }
                };

                logDuplicateWireIds();

                if (typeof window.Livewire.interceptRequest === 'function') {
                    window.Livewire.interceptRequest(({ request, onSend, onSuccess, onError, onFailure, onFinish }) => {
                        const actions = [];

                        request.messages.forEach((message) => {
                            message.actions.forEach((action) => {
                                actions.push({
                                    componentId: message.component.id,
                                    component: message.component.name,
                                    method: action.name,
                                });
                            });
                        });

                        console.info('[SChat][Livewire] request queued', actions);

                        onSend(() => {
                            console.info('[SChat][Livewire] request sent', actions);
                        });

                        onSuccess(() => {
                            console.info('[SChat][Livewire] request success', actions);
                        });

                        onError(({ response }) => {
                            console.error('[SChat][Livewire] request error', {
                                status: response?.status ?? null,
                                actions,
                            });
                        });

                        onFailure(({ error }) => {
                            console.error('[SChat][Livewire] request failure', {
                                error,
                                actions,
                            });
                        });

                        onFinish(() => {
                            console.info('[SChat][Livewire] request finished', actions);
                        });
                    });
                }
            });
        </script>

        @livewireScripts
    </body>
</html>
