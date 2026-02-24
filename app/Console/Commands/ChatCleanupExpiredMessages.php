<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ChatCleanupExpiredMessages extends Command
{
    protected $signature = 'chat:cleanup-expired';

    protected $description = 'Delete expired chat messages and their attachments.';

    public function handle(): int
    {
        $ttlMinutes = max((int) config('chat.ttl_minutes', 120), 1);
        $cutoff = now()->subMinutes($ttlMinutes);
        $deletedMessages = 0;
        $deletedFiles = 0;

        Message::query()
            ->where('created_at', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(200, function ($messages) use (&$deletedMessages, &$deletedFiles): void {
                $ids = [];

                foreach ($messages as $message) {
                    $ids[] = (int) $message->getKey();

                    if (! filled($message->attachment_path)) {
                        continue;
                    }

                    $attachmentMeta = is_array($message->attachment_meta) ? $message->attachment_meta : [];
                    $disk = (string) ($attachmentMeta['disk'] ?? config('chat.attachments.disk', 'local'));

                    try {
                        if (Storage::disk($disk)->exists((string) $message->attachment_path)) {
                            Storage::disk($disk)->delete((string) $message->attachment_path);
                            $deletedFiles++;
                        }
                    } catch (Throwable) {
                        //
                    }
                }

                if ($ids !== []) {
                    $deletedMessages += Message::query()
                        ->whereIn('id', $ids)
                        ->delete();
                }
            });

        $this->info(sprintf(
            'Chat TTL cleanup done. Deleted messages: %d, attachments: %d, cutoff: %s.',
            $deletedMessages,
            $deletedFiles,
            $cutoff->toDateTimeString(),
        ));

        return self::SUCCESS;
    }
}
