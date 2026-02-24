<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Chat\PresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatPresencePingController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, PresenceService $presenceService): JsonResponse
    {
        if (! $request->user() instanceof User) {
            abort(401);
        }

        $presenceService->touch($request->user(), (string) $request->session()->getId());

        return response()->json([
            'ok' => true,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
