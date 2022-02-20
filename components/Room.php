<?php

namespace app\components;

use app\models\activeRecord\Room as RoomAR;

class Room
{
    private array $clients = [];
    private int $id;
    private string $title;
    private int $isPublic;

    public const COMMAND_ME          = 'me';
    public const COMMAND_SHOWMEMBERS = 'showmembers';
    public const COMMAND_DATE        = 'date';

    public static array $commands = [
        self::COMMAND_ME,
        self::COMMAND_SHOWMEMBERS,
        self::COMMAND_DATE
    ];

    public function __construct(int $id)
    {
        $room = RoomAR::findOne(['id' => $id])->toArray();
        $this->id                   = $room['id'];
        $this->isPublic            = $room['isPublic'];
        $this->title                = $room['title'];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getIsPublic(): int
    {
        return $this->isPublic;
    }

    public function addClient(Client $client)
    {
        $this->clients[$client->getIdentifier()] = $client;
    }

    public function sendAll(array $message)
    {
        foreach ($this->clients as $client) {
            $client->getConnection()->send(json_encode($message));
        }
    }

    public function updateRoomHistoryLimit(string $key, int $limit): void
    {
        switch ($key) {
            case 'days':
                $setting = 'historyDaysLimit';
                break;
            case 'messages':
                $setting = 'historyMessagesLimit';
                break;
        }
        if (isset($setting)) {
            RoomAR::updateAll([$setting => $limit], ['id' => $this->getId()]);
            $this->$setting = $limit;
        }
    }

    public function removeClient(string $identifier)
    {
        unset($this->clients[$identifier]);
    }

    public function getClients(): array
    {
        return $this->clients;
    }

    public static function getCommandsRegex(): string
    {
        $regex = '/';

        foreach (static::$commands as $command) {
            if ('/' !== $regex) {
                $regex .= "|";
            }
            $regex .= "^\/" . $command;
        }
        return $regex . '/';
    }

}
