<?php

namespace App\Models;

use CodeIgniter\Model;

class LiveChatMessageModel extends Model
{

    protected $table='live_chat_messages';

    protected $primaryKey='id';

    protected $returnType='array';

    protected $allowedFields=[

        'chat_id',

        'sender',

        'message'

    ];

    public function addMessage(int $chatId, string $sender, string $message): array
    {

        $this->insert([

            'chat_id' => $chatId,

            'sender'  => $sender,

            'message' => $message

        ]);

        return $this->find($this->getInsertID());

    }

    public function getMessages(int $chatId): array
    {

        return $this

            ->where('chat_id', $chatId)

            ->orderBy('id', 'ASC')

            ->findAll();

    }

    public function getNewMessages(int $chatId, int $afterId): array
    {

        return $this

            ->where('chat_id', $chatId)

            ->where('id >', $afterId)

            ->orderBy('id', 'ASC')

            ->findAll();

    }

}