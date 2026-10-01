<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId): bool {
    return DB::table('conversations')
        ->where('id', $conversationId)
        ->where(function ($query) use ($user) {
            $query->where('user_a_id', $user->id)
                ->orWhere('user_b_id', $user->id);
        })
        ->exists();
});

Broadcast::channel('user.{userId}', function (User $user, int $userId): bool {
    return (int) $user->id === $userId;
});
