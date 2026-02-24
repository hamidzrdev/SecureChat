<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatStorePassphraseVerifyBlobRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChatPassphraseController extends Controller
{
    public function issueChallenge(
        Request $request,
        Conversation $conversation,
        ConversationService $conversationService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $challenge = $conversationService->issuePassphraseVerifyChallenge($conversation, $authenticatedUser);

        return response()->json($challenge);
    }

    public function storeVerifyBlob(
        ChatStorePassphraseVerifyBlobRequest $request,
        Conversation $conversation,
        ConversationService $conversationService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $validated = $request->validated();
        $iter = (int) ($validated['iter'] ?? config('chat.passphrase.kdf_iter', 150000));

        if ($iter !== (int) config('chat.passphrase.kdf_iter', 150000)) {
            throw ValidationException::withMessages([
                'iter' => __('chat.errors.unsupported_kdf_iter'),
            ]);
        }

        $conversationService->storePassphraseVerifyBlob(
            $conversation,
            $authenticatedUser,
            (string) $validated['verify_blob_base64'],
        );

        return response()->json([
            'ok' => true,
            'conversation_id' => (int) $conversation->getKey(),
            'stored_at' => now()->toIso8601String(),
        ]);
    }

    public function meta(
        Request $request,
        Conversation $conversation,
        ConversationService $conversationService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $meta = $conversationService->getPassphraseMeta($conversation, $authenticatedUser);

        return response()->json($meta);
    }
}
