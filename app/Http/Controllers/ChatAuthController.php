<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatLoginRequest;
use App\Models\User;
use App\Services\Chat\PresenceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatAuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            $routeName = config('chat.public_enabled') ? 'chat.public' : 'chat.private';

            return redirect()->route($routeName);
        }

        return view('ui.pages.login');
    }

    public function store(ChatLoginRequest $request, PresenceService $presenceService): RedirectResponse
    {
        $validated = $request->validated();
        $chatId = Str::lower((string) $validated['chat_id']);
        $setPassword = (bool) ($validated['set_password'] ?? false);
        $password = $validated['password'] ?? null;

        $user = User::query()
            ->where('chat_id', $chatId)
            ->first();

        if (! $user instanceof User) {
            $user = User::query()->create([
                'chat_id' => $chatId,
                'password_hash' => filled($password) ? Hash::make($password) : null,
            ]);
        } else {
            if (filled($user->password_hash)) {
                if (! filled($password) || ! Hash::check((string) $password, (string) $user->password_hash)) {
                    throw ValidationException::withMessages([
                        'password' => __('chat.errors.invalid_password'),
                    ]);
                }
            } elseif ($setPassword) {
                if ($presenceService->hasActiveSession($user)) {
                    throw ValidationException::withMessages([
                        'chat_id' => __('chat.errors.chat_id_active'),
                    ]);
                }

                $user->forceFill([
                    'password_hash' => Hash::make((string) $password),
                ])->save();
            }
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('chat.authenticated', true);
        $presenceService->touch($user, (string) $request->session()->getId());

        $routeName = config('chat.public_enabled') ? 'chat.public' : 'chat.private';

        return redirect()->intended(route($routeName));
    }

    public function destroy(Request $request, PresenceService $presenceService): RedirectResponse
    {
        if ($request->user() instanceof User) {
            $presenceService->clearSession($request->user(), (string) $request->session()->getId());
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('chat.login');
    }
}
