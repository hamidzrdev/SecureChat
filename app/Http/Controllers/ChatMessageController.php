<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatSendImageMessageRequest;
use App\Http\Requests\ChatSendTextMessageRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChatMessageController extends Controller
{
    public function index(
        Request $request,
        Conversation $conversation,
        ConversationService $conversationService,
        MessageService $messageService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $conversationService->ensureConversationParticipant($conversation, $authenticatedUser);

        $limit = max(1, min((int) $request->integer('limit', 100), 200));
        $messages = $messageService->listConversationMessages($conversation, $authenticatedUser, $limit);

        return response()->json([
            'conversation_id' => (int) $conversation->getKey(),
            'messages' => $messages->values()->all(),
        ]);
    }

    public function sendText(
        ChatSendTextMessageRequest $request,
        Conversation $conversation,
        ConversationService $conversationService,
        MessageService $messageService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $conversationService->ensureConversationParticipant($conversation, $authenticatedUser);

        $message = $messageService->sendTextMessage(
            $conversation,
            $authenticatedUser,
            $request->validated(),
        );

        return response()->json([
            'message' => $messageService->toClientMessage($message),
        ], 201);
    }

    public function sendImage(
        ChatSendImageMessageRequest $request,
        Conversation $conversation,
        ConversationService $conversationService,
        MessageService $messageService,
    ): JsonResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        $conversationService->ensureConversationParticipant($conversation, $authenticatedUser);

        $validated = $request->validated();
        $meta = is_array($validated['meta'] ?? null) ? $validated['meta'] : [];
        $uploadedFile = $request->file('image');

        if (! $uploadedFile) {
            throw ValidationException::withMessages([
                'image' => __('chat.errors.image_required'),
            ]);
        }

        $message = $messageService->sendImageMessage(
            $conversation,
            $authenticatedUser,
            $uploadedFile,
            $meta,
        );

        return response()->json([
            'message' => $messageService->toClientMessage($message),
        ], 201);
    }
}
