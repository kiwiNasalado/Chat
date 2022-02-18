<?php

namespace app\models;

use app\extentions\traits\Cache;
use app\extentions\traits\Singleton;
use app\models\activeRecord\Client;
use app\models\activeRecord\Room;
use app\models\activeRecord\RoomAccess;
use yii\db\Query;

class Rooms
{
    use Singleton;

    public const IS_PUBLIC = 1;
    public const IS_PRIVATE = 0;

    public const DEFAULT_HISTORY_DAYS_LIMIT = 30;
    public const DEFAULT_HISTORY_MESSAGES_LIMIT = 5000;

    public const IDENTIFIER_SALT = 'f51Dsd3f#23351e';


    public function getAllowedRooms(string $identifier, int $page = 0, int $limit = 15): array
    {
        $privateRooms = array_column(
            RoomAccesses::getInstance()->getPrivateRooms($identifier),
            'roomId'
        );

        $query = (new Query())
            ->select('*')
            ->from(Room::tableName());
        if (empty($privateRooms)) {
            $query->where(['isPublic' => self::IS_PUBLIC]);
        } else {
            $query->where([
                    'or',
                    [
                        'isPublic' => self::IS_PUBLIC
                    ],
                    [
                        'id' => $privateRooms
                    ]
                ]
            );
        }
        if (!empty($page)) {
            $query->offset($page * $limit);
        }
        $query->limit($limit);
        return $query->all();
    }

    public function getRooms(int $page = 0, int $limit = 100): array
    {
        return (new Query())
            ->select('*')
            ->from(Room::tableName())
            ->offset($page * $limit)
            ->limit($limit)
            ->all();
    }

    public function checkIsRoomAllowed(string $identifier, int $roomId): bool
    {
        $allowedRooms = array_column($this->getAllowedRooms($identifier), 'id');
        return in_array($roomId, $allowedRooms);
    }

    public function createPrivateRoom(Client $client1, Client $client2): array
    {
        $room = new Room();
        $room->title = $client1->email . ', ' . $client2->email;
        $room->identifier = $this->generateIdentifier($client1->email, $client2->email);
        $room->isPublic = self::IS_PRIVATE;
        $room->historyDaysLimit = self::DEFAULT_HISTORY_DAYS_LIMIT;
        $room->historyMessagesLimit = self::DEFAULT_HISTORY_MESSAGES_LIMIT;
        $room->save();
        foreach ([$client1->id, $client2->id] as $emailId) {
            $roomAccess = new RoomAccess();
            $roomAccess->emailId = $emailId;
            $roomAccess->roomId = $room->id;
            $roomAccess->save();
        }
        return $room->toArray();
    }

    public function getPrivateRoomIfExists(string $email1, string $email2)
    {
        return (new Query())
            ->select('*')
            ->from(Room::tableName())
            ->where(['identifier' => $this->generateIdentifier($email1, $email2)])
            ->limit(1)
            ->one();
    }

    public function generateIdentifier(string $email1, string $email2)
    {
        $emailsArray = [$email1, $email2];
        sort($emailsArray);
        return (md5($emailsArray[0] . self::IDENTIFIER_SALT . $emailsArray[1]));
    }
}
