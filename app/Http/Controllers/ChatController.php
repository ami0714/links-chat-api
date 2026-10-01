<?php

namespace App\Http\Controllers;

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
    public function getConversationChat(Request $request,$conversationId)
    {
        $userId = request()->user()->id;

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
    public function messageHandler(Request $request,$conversationId)
    {
        $userId = request()->user()->id;
        $bodyMessage = $request->input('message');

        $insertMessage = $this->chatService->messageHandler($userId,$conversationId,$bodyMessage);

        if ($insertMessage['status'] == true) {
            return response()->json($insertMessage);
        }
        return response()->json([
            'status' => false,
            'message' => 'err'
        ]);
    }

    
    public function createConversation(Request $request)
    {
        $userId = request()->user()->id;
        $otherUserUid = $request->query('uid');
        $message = $request->input('message');

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
