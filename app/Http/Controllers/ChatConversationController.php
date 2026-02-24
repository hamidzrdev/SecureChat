<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartPrivateChatRequest;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class ChatConversationController extends Controller
{
    public function showPublic(ConversationService $conversationService): View
    {
        if (! config('chat.public_enabled')) {
            abort(404);
        }

        $publicConversation = $conversationService->ensurePublicConversation();

        return view('ui.pages.public-chat', [
            'publicConversation' => $publicConversation,
        ]);
    }

    public function startPrivateChat(StartPrivateChatRequest $request, ConversationService $conversationService): JsonResponse
    {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $validated = $request->validated();
        $targetUser = User::query()->findOrFail((int) $validated['target_user_id']);
        $isPassphrase = (bool) ($validated['is_passphrase'] ?? false);

        $conversation = $conversationService->startPrivateChat(
            opener: $authenticatedUser,
            target: $targetUser,
            isPassphrase: $isPassphrase,
        );

        return response()->json([
            'conversation_id' => $conversation->getKey(),
            'type' => $conversation->type,
            'is_passphrase' => (bool) $conversation->is_passphrase,
            'target_user_id' => $targetUser->getKey(),
            'target_chat_id' => $targetUser->chat_id,
            'opened_at' => now()->toIso8601String(),
        ]);
    }
}
