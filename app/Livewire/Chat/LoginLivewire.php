<?php

namespace App\Livewire\Chat;

use App\Models\User;
use App\Services\Chat\PresenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class LoginLivewire extends Component
{
    public string $chatId = '';

    public bool $setPassword = false;

    public string $password = '';

    public string $passwordConfirmation = '';

    public int $ttlMinutes = 120;

    public bool $requiresPassword = false;

    public function mount(): void
    {
        $this->ttlMinutes = (int) config('chat.ttl_minutes', 120);
    }

    public function updatedChatId(string $value): void
    {
        $normalizedChatId = Str::lower(trim($value));

        if ($normalizedChatId === '') {
            $this->requiresPassword = false;

            return;
        }

        $this->requiresPassword = User::query()
            ->where('chat_id', $normalizedChatId)
            ->whereNotNull('password_hash')
            ->exists();
    }

    public function authenticate(PresenceService $presenceService): void
    {
        $validated = $this->validate([
            'chatId' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9._-]+$/'],
            'setPassword' => ['boolean'],
            'password' => ['nullable', 'string', 'min:6', 'max:64'],
            'passwordConfirmation' => ['nullable', 'string', 'min:6', 'max:64', 'same:password'],
        ], [
            'chatId.required' => __('chat.validation.chat_id_required'),
            'chatId.min' => __('chat.validation.chat_id_min'),
            'chatId.max' => __('chat.validation.chat_id_max'),
            'chatId.regex' => __('chat.validation.chat_id_format'),
            'password.min' => __('chat.validation.password_min'),
            'password.max' => __('chat.validation.password_max'),
            'passwordConfirmation.min' => __('chat.validation.password_min'),
            'passwordConfirmation.max' => __('chat.validation.password_max'),
            'passwordConfirmation.same' => __('chat.validation.password_confirmation_same'),
        ], [
            'chatId' => __('chat.login.chat_id_label'),
            'password' => __('chat.login.password_label'),
            'passwordConfirmation' => __('chat.login.password_confirmation_label'),
        ]);

        $chatId = Str::lower((string) $validated['chatId']);
        $setPassword = (bool) $validated['setPassword'];
        $password = (string) ($validated['password'] ?? '');
        $passwordProvided = $password !== '';

        if ($setPassword && ! $passwordProvided) {
            throw ValidationException::withMessages([
                'password' => __('chat.errors.password_required_for_protection'),
            ]);
        }

        $rateKey = sprintf('chat-login:%s|%s', $chatId, request()->ip());
        $maxAttempts = (int) config('chat.rate_limits.login_per_minute', 10);

        if (RateLimiter::tooManyAttempts($rateKey, $maxAttempts)) {
            $retryInSeconds = RateLimiter::availableIn($rateKey);

            throw ValidationException::withMessages([
                'chatId' => __('chat.errors.too_many_login_attempts', ['seconds' => $retryInSeconds]),
            ]);
        }

        RateLimiter::hit($rateKey, 60);

        $user = User::query()
            ->where('chat_id', $chatId)
            ->first();

        if (! $user instanceof User) {
            $user = User::query()->create([
                'chat_id' => $chatId,
                'password_hash' => $passwordProvided ? Hash::make($password) : null,
            ]);
        } elseif (filled($user->password_hash)) {
            if (! $passwordProvided || ! Hash::check($password, (string) $user->password_hash)) {
                throw ValidationException::withMessages([
                    'password' => __('chat.errors.invalid_password'),
                ]);
            }
        } elseif ($setPassword) {
            if ($presenceService->hasActiveSession($user)) {
                throw ValidationException::withMessages([
                    'chatId' => __('chat.errors.chat_id_active'),
                ]);
            }

            $user->forceFill([
                'password_hash' => Hash::make($password),
            ])->save();
        }

        Auth::login($user);
        session()->regenerate();
        session()->put('chat.authenticated', true);

        $presenceService->touch($user, (string) session()->getId());
        RateLimiter::clear($rateKey);

        $routeName = config('chat.public_enabled') ? 'chat.public' : 'chat.private';

        $this->redirectRoute($routeName);
    }

    public function render(): View
    {
        return view('livewire.chat.login-livewire');
    }
}
