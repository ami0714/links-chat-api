<?php

namespace App\Http\Controllers;

use App\Http\Requests\Chat\CreateConversationRequest;
use App\Http\Requests\Chat\GetConversationChatRequest;
use App\Http\Requests\Chat\SendMessageRequest;
use Illuminate\Http\Request;
use App\Service\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;


class ChatController extends Controller
{
    protected ChatService $chatService;

    public function __construct(ChatService $chatService){
       $this->chatService = $chatService;
    }
    /**
     * Display a listing of the resource.
     */
    public function getChatHome():JsonResponse
    {
       $userId = Request()->user()->id;

      try {
         $chatHome = $this->chatService->getChatHome($userId);
         return response()->json([
            'status' => true,
            'dataChat' => $chatHome
        ]);
      } catch (Exception $th) {
        return response()->json([
            'status' => false,
            'message' => 'gagal dapatkan chat di home'
        ]);
      }



    }

    /**
     * Show the form for creating a new resource.
     */
    public function getConversationChat(GetConversationChatRequest $request, string $conversationId)
    {
        $userId = request()->user()->id;
        $conversationId = $request->validated('conversationId');

        $chatConversation = $this->chatService->getConversationChat($userId, $conversationId);

        return response()->json([
            'status' => true,
            'otherUser' => $chatConversation['otherUser'],
            'chat' => $chatConversation['chat']
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function messageHandler(SendMessageRequest $request, string $conversationId)
    {
        $userId = request()->user()->id;
        $validated = $request->validated();
        $conversationId = $validated['conversationId'];
        $bodyMessage = $validated['message'];

        $insertMessage = $this->chatService->messageHandler($userId,$conversationId,$bodyMessage);

        if ($insertMessage['status'] == true) {
            return response()->json($insertMessage);
        }
        return response()->json([
            'status' => false,
            'message' => 'err'
        ]);
    }

    
    public function createConversation(CreateConversationRequest $request)
    {
        $userId = request()->user()->id;
        $validated = $request->validated();
        $otherUserUid = $validated['uid'];
        $message = $validated['message'] ?? null;

        $createConversation = $this->chatService->createConversation($userId, $otherUserUid, $message);

        return response()->json($createConversation);
    }

    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
