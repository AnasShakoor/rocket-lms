<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToSupportConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('support_conversations', function (Blueprint $table) {
            $table->foreign('support_id', 'fk_support_conversations_support')
                ->references('id')->on('supports')->onDelete('cascade');

            $table->foreign('sender_id', 'fk_support_conversations_sender')
                ->references('id')->on('users')->onDelete('cascade');
        });
    }
}
