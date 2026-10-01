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
            $table->foreignId('user_a_id')
                ->comment('Sentiasa < user_b_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('user_b_id')
                ->comment('Sentiasa > user_a_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->comment('Untuk sort conversation list');
            $table->timestamps();

            $table->unique(['user_a_id', 'user_b_id']);
            $table->index(['user_a_id', 'last_message_at']);
            $table->index(['user_b_id', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->char('id', 26)->comment('ULID - sortable');
            $table->foreignId('conversation_id')
                ->constrained('conversations')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->comment('Sender')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('read_at')->nullable()->comment('Bila receiver baca');
            $table->timestamps();

            $table->primary('id');
            $table->index(['conversation_id', 'id']);
            $table->index(['conversation_id', 'read_at']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
    }
};