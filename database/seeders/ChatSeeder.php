<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class ChatSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('messages')->truncate();
        DB::table('conversations')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $password = Hash::make('password');
        $now = now();

        // Users
        $users = [
            ['id' => 1, 'uid' => '48291034', 'username' => 'ali_ahmad',  'name' => 'Ali Ahmad',  'email' => 'ali@linkschat.test'],
            ['id' => 2, 'uid' => '71930482', 'username' => 'sarah_lim',  'name' => 'Sarah Lim',  'email' => 'sarah@linkschat.test'],
            ['id' => 3, 'uid' => '26501847', 'username' => 'ahmad_92',   'name' => 'Ahmad Zaki', 'email' => 'ahmad@linkschat.test'],
            ['id' => 4, 'uid' => '83910265', 'username' => 'meiling',    'name' => 'Mei Ling',   'email' => 'meiling@linkschat.test'],
            ['id' => 5, 'uid' => '57382094', 'username' => 'raj_kumar',  'name' => 'Raj Kumar',  'email' => 'raj@linkschat.test'],
        ];

        foreach ($users as $u) {
            DB::table('users')->insert([
                'id' => $u['id'],
                'uid' => $u['uid'],
                'username' => $u['username'],
                'name' => $u['name'],
                'email' => $u['email'],
                'email_verified_at' => $now,
                'password' => $password,
                'avatar_path' => null,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Conversations
        $conversations = [
            ['id' => 1, 'a' => 1, 'b' => 2, 'last' => $now->copy()->subMinutes(2)],
            ['id' => 2, 'a' => 1, 'b' => 3, 'last' => $now->copy()->subHours(3)],
            ['id' => 3, 'a' => 1, 'b' => 4, 'last' => $now->copy()->subDays(2)],
            ['id' => 4, 'a' => 2, 'b' => 5, 'last' => $now->copy()->subHours(6)],
            ['id' => 5, 'a' => 3, 'b' => 4, 'last' => $now->copy()->subDay()],
        ];

        foreach ($conversations as $c) {
            DB::table('conversations')->insert([
                'id' => $c['id'],
                'user_a_id' => $c['a'],
                'user_b_id' => $c['b'],
                'last_message_at' => $c['last'],
                'created_at' => $c['last']->copy()->subDays(2),
                'updated_at' => $c['last'],
            ]);
        }

        // Messages (encrypted)
        $messages = [
            // Conv 1: Ali <-> Sarah
            [1, 1, 'Hey Sarah! Are we still on for the design review later?', true],
            [1, 2, 'Yes, I\'ll send the latest screens in a few minutes.', true],
            [1, 1, 'Great, thanks. I\'ll check the login and register flows first.', true],
            [1, 2, 'Perfect. I\'ll also share the updated profile screen.', true],
            [1, 1, 'Sounds good. Let me know if anything needs a quick edit.', true],
            [1, 2, 'Will do. See you soon.', false],

            // Conv 2: Ali <-> Ahmad
            [2, 1, 'Ahmad, dah siap ke migration untuk table messages?', true],
            [2, 3, 'Dah siap. Guna ULID untuk primary key, senang nak sort.', true],
            [2, 1, 'Bagus. Index apa yang awak letak?', true],
            [2, 3, 'Conversation_id + id untuk pagination, conversation_id + read_at untuk unread count.', true],
            [2, 1, 'Nice. Kita test esok pagi.', false],

            // Conv 3: Ali <-> Mei Ling
            [3, 4, 'Hi Ali, boleh tolong review PR untuk QR scanner?', true],
            [3, 1, 'Boleh, nanti saya tengok malam ni.', true],
            [3, 4, 'Terima kasih! Ada isu dengan camera permission tak?', true],
            [3, 1, 'Setakat ni ok. Tapi kena handle fallback kalau user reject permission.', false],

            // Conv 4: Sarah <-> Raj
            [4, 2, 'Raj, deployment dah settle?', true],
            [4, 5, 'Dah. Reverb jalan dengan Supervisor, SSL pun dah pasang.', true],
            [4, 2, 'Bagus. Queue worker macam mana?', true],
            [4, 5, 'Guna Redis, 2 process. Setakat ni ok.', false],

            // Conv 5: Ahmad <-> Mei Ling
            [5, 3, 'Mei, test feature typing indicator dah lulus?', true],
            [5, 4, 'Dah. Guna whisper, tak sentuh DB. Laju.', true],
            [5, 3, 'Ok, nanti saya update SDLC.', false],
        ];

        $offset = 30; // minit dari sekarang

        foreach ($messages as $i => $m) {
            [$convId, $userId, $body, $isRead] = $m;

            $createdAt = $now->copy()->subMinutes($offset - ($i * 2));
            $readAt = $isRead ? $createdAt->copy()->addMinutes(1) : null;

            DB::table('messages')->insert([
                'id' => (string) Str::ulid(),
                'conversation_id' => $convId,
                'user_id' => $userId,
                'body' => Crypt::encryptString($body),  // ← ENCRYPT
                'read_at' => $readAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        $this->command->info('✅ Dummy data berjaya dimasukkan!');
        $this->command->info('Login: ali@linkschat.test / password');
    }
}