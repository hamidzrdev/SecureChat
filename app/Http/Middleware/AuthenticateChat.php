<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Chat\PresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateChat
{
    public function __construct(private PresenceService $presenceService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof User) {
            return redirect()->route('chat.login');
        }

        $this->presenceService->touch($request->user(), (string) $request->session()->getId());

        return $next($request);
    }
}
