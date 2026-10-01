<?php

namespace App\Service;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
// use PHPUnit\Event\Code\Throwable;
use Exception;

class ChatService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getChatHome($userId){
       
    try {
         $chat = DB::select('
            SELECT 
             c.id AS conversation_id,
             c.last_message_at,

             CASE 
            WHEN c.user_a_id = :current_user_id_case THEN c.user_b_id
            ELSE c.user_a_id
            END AS other_user_id,

             u.id AS user_id,
                u.uid,
                u.username,
                u.name,
                u.avatar_path,
                u.last_seen_at,

                 m.body AS last_message_body,
    m.user_id AS last_message_sender_id,
                m.created_at AS last_message_at_actual

                FROM conversations c

                LEFT JOIN users u ON u.id = CASE
                    WHEN c.user_a_id = :current_user_id_join THEN c.user_b_id
                    ELSE c.user_a_id
                END

    LEFT JOIN messages m ON m.id = (
    SELECT id FROM messages
    WHERE conversation_id = c.id
    ORDER BY id DESC
    LIMIT 1
)

WHERE (c.user_a_id = :current_user_id_where_a OR c.user_b_id = :current_user_id_where_b)
  AND c.last_message_at IS NOT NULL    -- skip conversation kosong
ORDER BY c.last_message_at DESC
LIMIT 20;
    ', [
        'current_user_id_case' => $userId,
        'current_user_id_join' => $userId,
        'current_user_id_where_a' => $userId,
        'current_user_id_where_b' => $userId,
    ]);
    if (empty($chat)) {
        throw new Exception ("Error Processing Request", 1);
        
    }

    foreach ($chat as $item) {
        if (!empty($item->last_message_body)) {
            try{
                $item->last_message_body = Crypt::decryptString($item->last_message_body);
            }catch(DecryptException $e){
                $item->last_message_body = 'errror read message';
            }
        }
    }

    return $chat;
    } catch (Exception $e) {
       return [
        'status' => false,
        'messgage' => $e->getMessage()
      ]; 
    }
      

    }

    public function getConversationChat($userId, $conversationId)
    {
                $conversationAccess = DB::selectOne(
                        'SELECT id
                         FROM conversations
                         WHERE id = :conversationId
                             AND (user_a_id = :userAId OR user_b_id = :userBId)
                         LIMIT 1',
                        [
                                'conversationId' => $conversationId,
                                'userAId' => $userId,
                                'userBId' => $userId,
                        ]
                );

                abort_if($conversationAccess === null, 404);

        $otherUser = DB::select("SELECT
                        u.id,
                        u.uid,
                        u.username,
                        u.name,
                        u.avatar_path,
                        u.last_seen_at
                    FROM conversations c
                    LEFT JOIN users u ON u.id = CASE
                        WHEN c.user_a_id = :userId THEN c.user_b_id
                        ELSE c.user_a_id
                    END
                    WHERE c.id = :conversationId
                    LIMIT 1", ['userId' => $userId, 'conversationId' => $conversationId]);

        $otherUser = $otherUser[0] ?? null;

        $conversation = DB::select("SELECT
                        m.id,
                        m.user_id,
                        m.body,
                        m.read_at,
                        m.created_at,

                        CASE
                          WHEN m.user_id = :userId THEN 'me'
                         ELSE 'them'
                       END AS sender 
                       FROM messages m
                       WHERE m.conversation_id = :cId
                       ORDER BY m.id ASC
                        
        ", ['userId' => $userId, 'cId' => $conversationId]);


        foreach ($conversation as $item) {
        if (!empty($item->body)) {
            try{
                $item->body = Crypt::decryptString($item->body);
            }catch(DecryptException $e){
                $item->body = 'errror read message';
            }
        }
    }

        return [
            'otherUser' => $otherUser,
            'chat' => $conversation,
        ];
    }

    public function messageHandler($userId, $conversationId, $message)
{
    $conversationAccess = DB::selectOne(
        'SELECT id
         FROM conversations
         WHERE id = :conversationId
           AND (user_a_id = :userAId OR user_b_id = :userBId)
         LIMIT 1',
        [
            'conversationId' => $conversationId,
            'userAId' => $userId,
            'userBId' => $userId,
        ]
    );

    abort_if($conversationAccess === null, 404, 'Conversation tidak dijumpai.');

    if (empty($userId) || empty($conversationId) || empty($message)) {
        return [
            'status' => false,
            'message' => 'User ID, conversation ID, atau mesej kosong',
        ];
    }

    try {
        $messageId = (string) Str::ulid();
        $encryptedBody = Crypt::encryptString($message);
        $now = now();
        $conversationInfo = DB::selectOne('SELECT id,user_a_id,user_b_id FROM conversations 
                        WHERE id = :conversationId AND (user_a_id = :userAId OR user_b_id= :userBId)
                        LIMIT 1',[
                            'conversationId'=>$conversationId,
                            'userAId' =>$userId,
                            'userBId' =>$userId
                        ]);
        $recipientId = $conversationInfo->user_a_id === (int)$userId ? $conversationInfo->user_b_id : $conversationInfo->user_a_id;

        DB::transaction(function () use ($messageId, $conversationId, $userId, $encryptedBody, $now) {
            DB::insert(
                'INSERT INTO messages (id, conversation_id, user_id, body, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$messageId, $conversationId, $userId, $encryptedBody, $now, $now]
            );

            DB::update(
                'UPDATE conversations SET last_message_at = ?, updated_at = ? WHERE id = ?',
                [$now, $now, $conversationId]
            );
        });

        // ============================================
        // BROADCAST EVENT
        // ============================================
          //massukan seperti constructor di messageSent
        broadcast(new \App\Events\MessageSent(
            conversationId: (int) $conversationId,
            senderId: (int) $userId,
            recipientId:(int) $recipientId,
            messageId: $messageId,
            body: $message,       // hantar plain text, bukan encrypted
            createdAt: $now->toIso8601String(),
        ))->toOthers();   // supaya sender tak terima balik

        return [
            'status' => true,
            'message' => 'Berjaya masukkan message',
            'data' => [
                'id' => $messageId,
                'conversation_id' => (int) $conversationId,
                'sender_id' => (int) $userId,
                'body' => $message,
                'created_at' => $now->toIso8601String(),
            ],
        ];

    } catch (Exception $e) {
        return [
            'status' => false,
            'message' => $e->getMessage(),
        ];
    }
}

public function createConversation($userId, $otherUserUid,$message)
{
    $otherUser = DB::selectOne('SELECT id FROM users WHERE uid = :uid', ['uid' => $otherUserUid]);

    if (!$otherUser) {
        return [
            'status' => false,
            'message' => 'Pengguna lain tidak dijumpai',
        ];
    }

    $otherUserId = $otherUser->id;

    // Check if a conversation already exists
    $existingConversation = DB::selectOne(
        'SELECT id FROM conversations WHERE (user_a_id = :userAId AND user_b_id = :userBId) OR (user_a_id = :reverseUserAId AND user_b_id = :reverseUserBId) LIMIT 1',
        [
            'userAId' => $userId,
            'userBId' => $otherUserId,
            'reverseUserAId' => $otherUserId,
            'reverseUserBId' => $userId,
        ]
    );

    if ($existingConversation) {
        return [
            'status' => true,
            'message' => 'Perbualan sudah wujud',
            'conversation_id' => $existingConversation->id,
        ];
    }

    // Create a new conversation
    $now = now();
    $conversationId = DB::table('conversations')->insertGetId([
        'user_a_id' => $userId,
        'user_b_id' => $otherUserId,
        'last_message_at' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);


    if ($conversationId) {
        // Insert the initial message if provided
        if (!empty($message)) {
            $this->messageHandler($userId, $conversationId, $message);
        }
    } else {
        return [
            'status' => false,
            'message' => 'Gagal mencipta perbualan',
        ];
    }



    return [
        'status' => true,
        'message' => 'Perbualan berjaya dicipta',
        'conversation_id' => $conversationId,
    ];
}
}
