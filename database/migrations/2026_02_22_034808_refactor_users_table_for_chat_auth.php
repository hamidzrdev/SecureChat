<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'chat_id')) {
                $table->string('chat_id', 32)->nullable();
            }

            if (! Schema::hasColumn('users', 'password_hash')) {
                $table->string('password_hash')->nullable();
            }

            if (! Schema::hasColumn('users', 'last_seen_at')) {
                $table->timestamp('last_seen_at')->nullable();
            }
        });

        $rows = DB::table('users')
            ->select('id', 'chat_id', 'name', 'email', 'password', 'password_hash')
            ->orderBy('id')
            ->get();

        $usedChatIds = DB::table('users')
            ->whereNotNull('chat_id')
            ->pluck('chat_id')
            ->map(static fn (string $value): string => Str::lower($value))
            ->flip()
            ->all();

        foreach ($rows as $row) {
            $baseChatId = $row->chat_id ?? $row->email ?? $row->name ?? 'user_'.$row->id;
            $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '', Str::lower((string) $baseChatId)) ?: 'user_'.$row->id;
            $trimmed = Str::substr($sanitized, 0, 24);
            $candidate = $trimmed;
            $suffix = 1;

            while (array_key_exists($candidate, $usedChatIds)) {
                $candidate = Str::substr($trimmed, 0, 20).'-'.$suffix;
                $suffix++;
            }

            $usedChatIds[$candidate] = true;

            $payload = ['chat_id' => $candidate];

            if (empty($row->password_hash) && ! empty($row->password)) {
                $payload['password_hash'] = $row->password;
            }

            DB::table('users')
                ->where('id', $row->id)
                ->update($payload);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('chat_id', 32)->nullable(false)->change();
            $table->unique('chat_id');
            $table->index('last_seen_at');
        });

        $columnsToDrop = [];

        foreach (['name', 'email', 'email_verified_at', 'password', 'remember_token'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            if (in_array('email', $columnsToDrop, true)) {
                try {
                    Schema::table('users', function (Blueprint $table): void {
                        $table->dropUnique('users_email_unique');
                    });
                } catch (\Throwable) {
                    //
                }
            }

            Schema::table('users', function (Blueprint $table) use ($columnsToDrop): void {
                $table->dropColumn($columnsToDrop);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable();
            }

            if (! Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable();
            }

            if (! Schema::hasColumn('users', 'email_verified_at')) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'password')) {
                $table->string('password')->nullable();
            }

            if (! Schema::hasColumn('users', 'remember_token')) {
                $table->rememberToken();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'chat_id')) {
                $table->dropUnique(['chat_id']);
                $table->dropColumn('chat_id');
            }

            if (Schema::hasColumn('users', 'password_hash')) {
                $table->dropColumn('password_hash');
            }

            if (Schema::hasColumn('users', 'last_seen_at')) {
                $table->dropIndex(['last_seen_at']);
                $table->dropColumn('last_seen_at');
            }
        });
    }
};
