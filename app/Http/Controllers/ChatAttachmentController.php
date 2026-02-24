<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use App\Services\Chat\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatAttachmentController extends Controller
{
    public function show(
        Request $request,
        Message $message,
        ConversationService $conversationService,
    ): StreamedResponse {
        $authenticatedUser = $request->user();

        if (! $authenticatedUser instanceof User) {
            abort(401);
        }

        if (! filled($message->attachment_path)) {
            abort(404);
        }

        $conversationService->ensureConversationParticipant($message->conversation, $authenticatedUser);

        $attachmentMeta = is_array($message->attachment_meta) ? $message->attachment_meta : [];
        $disk = (string) ($attachmentMeta['disk'] ?? config('chat.attachments.disk', 'local'));
        $mimeType = (string) ($attachmentMeta['mime_type'] ?? 'application/octet-stream');
        $originalName = (string) ($attachmentMeta['original_name'] ?? basename((string) $message->attachment_path));

        if (! Storage::disk($disk)->exists((string) $message->attachment_path)) {
            abort(404);
        }

        return Storage::disk($disk)->response(
            (string) $message->attachment_path,
            $originalName,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, max-age=60',
            ],
        );
    }
}
