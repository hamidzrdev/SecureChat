<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['public', 'private']);
            $table->boolean('is_passphrase')->default(false);
            $table->string('pair_key', 64)->nullable();
            $table->text('passphrase_salt')->nullable();
            $table->text('passphrase_verify_blob')->nullable();
            $table->timestamps();

            $table->index('type');
            $table->index('is_passphrase');
            $table->unique(['type', 'is_passphrase', 'pair_key'], 'conversations_unique_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
