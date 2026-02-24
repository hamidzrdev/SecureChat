<?php

namespace App\Services\Chat;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PresenceService
{
    public function touch(User $user, string $sessionId): void
    {
        $now = now();

        $user->forceFill([
            'last_seen_at' => $now,
        ])->saveQuietly();

        $expiresAt = $now->copy()->addMinutes($this->windowMinutes() + 1);

        Cache::put($this->userMarkerKey($user->id), $sessionId, $expiresAt);
        Cache::put($this->sessionMarkerKey($user->id, $sessionId), true, $expiresAt);
    }

    public function clearSession(User $user, string $sessionId): void
    {
        Cache::forget($this->sessionMarkerKey($user->id, $sessionId));

        if (Cache::get($this->userMarkerKey($user->id)) === $sessionId) {
            Cache::forget($this->userMarkerKey($user->id));
        }
    }

    public function hasActiveSession(User $user): bool
    {
        $activeSessionId = Cache::get($this->userMarkerKey($user->id));

        if (! is_string($activeSessionId) || $activeSessionId === '') {
            return false;
        }

        return $this->isOnline($user) && Cache::has($this->sessionMarkerKey($user->id, $activeSessionId));
    }

    public function isOnline(User $user): bool
    {
        if (! $user->last_seen_at instanceof CarbonInterface) {
            return false;
        }

        return $user->last_seen_at->greaterThanOrEqualTo($this->onlineThreshold());
    }

    public function onlineUsers(int $limit = 100): Collection
    {
        return $this->onlineUsersQuery()
            ->limit($limit)
            ->get();
    }

    public function onlineUsersQuery(): Builder
    {
        $query = User::query()->orderBy('chat_id');

        if (! config('chat.online_list_enabled')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('last_seen_at', '>=', $this->onlineThreshold());
    }

    private function onlineThreshold(): CarbonInterface
    {
        return now()->subMinutes($this->windowMinutes());
    }

    private function windowMinutes(): int
    {
        return (int) config('chat.online_window_minutes', 5);
    }

    private function userMarkerKey(int $userId): string
    {
        return 'chat:presence:user:'.$userId;
    }

    private function sessionMarkerKey(int $userId, string $sessionId): string
    {
        return 'chat:presence:user:'.$userId.':session:'.$sessionId;
    }
}
