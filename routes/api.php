<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChatController;

require __DIR__.'/auth.php';

Route::middleware(['auth:sanctum'])->group(function () {

Route::get('/user',[UserController::class, 'getUser']);
Route::get('/user/other',[UserController::class, 'getOtherUser']);

Route::get('/chat',[ChatController::class,'getChatHome']);

Route::get('/chat/{conversationId}',[ChatController::class,'getConversationChat']);
Route::post('/chat/message/{conversationId}',[ChatController::class,'messageHandler']);
Route::post('/chat/conversation/new',[ChatController::class,'createConversation']);
});
