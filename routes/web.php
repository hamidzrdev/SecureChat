<?php

use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatAuthController;
use App\Http\Controllers\ChatConversationController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\ChatPassphraseController;
use App\Http\Controllers\ChatPresencePingController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $chatRoute = config('chat.public_enabled') ? 'chat.public' : 'chat.private';

        return redirect()->route($chatRoute);
    }

    return redirect()->route('chat.login');
})->name('home');

Route::view('/ui-preview', 'ui.preview')->name('ui.preview');
Route::view('/ui/login', 'ui.pages.login')->name('ui.login');
Route::view('/ui/chat/public', 'ui.pages.public-chat')->name('ui.chat.public');
Route::view('/ui/chat/private', 'ui.pages.private-chat')->name('ui.chat.private');
Route::view('/ui/chat/passphrase', 'ui.pages.passphrase-gate')->name('ui.chat.passphrase');
Route::post('/locale', LocaleController::class)->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [ChatAuthController::class, 'create'])
        ->name('chat.login');

    Route::post('/login', [ChatAuthController::class, 'store'])
        ->middleware('throttle:chat-login')
        ->name('chat.login.store');
});

Route::middleware('auth.chat')->group(function (): void {
    Route::post('/chat/logout', [ChatAuthController::class, 'destroy'])
        ->name('chat.logout');

    Route::post('/chat/ping', ChatPresencePingController::class)
        ->middleware('throttle:chat-message')
        ->name('chat.ping');

    Route::get('/chat/general', [ChatConversationController::class, 'showPublic'])
        ->name('chat.public');

    Route::view('/chat/private', 'ui.pages.private-chat')
        ->name('chat.private');

    Route::view('/chat/passphrase', 'ui.pages.passphrase-gate')
        ->name('chat.passphrase');

    Route::post('/chat/private/start', [ChatConversationController::class, 'startPrivateChat'])
        ->middleware('throttle:chat-message')
        ->name('chat.private.start');

    Route::get('/chat/conversations/{conversation}/messages', [ChatMessageController::class, 'index'])
        ->middleware('throttle:chat-message')
        ->name('chat.messages.index');

    Route::post('/chat/conversations/{conversation}/messages/text', [ChatMessageController::class, 'sendText'])
        ->middleware('throttle:chat-message')
        ->name('chat.messages.send-text');

    Route::post('/chat/conversations/{conversation}/messages/image', [ChatMessageController::class, 'sendImage'])
        ->middleware('throttle:chat-message')
        ->name('chat.messages.send-image');

    Route::post('/chat/conversations/{conversation}/passphrase/challenge', [ChatPassphraseController::class, 'issueChallenge'])
        ->middleware('throttle:chat-message')
        ->name('chat.passphrase.challenge');

    Route::put('/chat/conversations/{conversation}/passphrase/verify-blob', [ChatPassphraseController::class, 'storeVerifyBlob'])
        ->middleware('throttle:chat-message')
        ->name('chat.passphrase.store-verify-blob');

    Route::get('/chat/conversations/{conversation}/passphrase/meta', [ChatPassphraseController::class, 'meta'])
        ->middleware('throttle:chat-message')
        ->name('chat.passphrase.meta');

    Route::get('/chat/messages/{message}/attachment', [ChatAttachmentController::class, 'show'])
        ->middleware('signed')
        ->name('chat.messages.attachment');
});
