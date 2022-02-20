<?php

namespace app\models;

use app\extentions\traits\Singleton;
use app\models\activeRecord\Client;
use app\models\activeRecord\Message;
use app\models\activeRecord\Room;
use app\models\activeRecord\ClientRoomDetails as crdAR;
use yii\db\Query;

class Messages
{
    use Singleton;

    public function getMessages(int $roomId, ?int $clientId = null, int $page = 0, int $limit = 20, bool $useDateLimit = true): array
    {
        $room = Room::findOne(['id' => $roomId]);
        if (null !== $room) {
            $room = $room->toArray();
        }
        if ($useDateLimit) {
            $roomSecLimit = $room['historyDaysLimit'] * 86400;
            $limitTimestamp = time() - $roomSecLimit;
            $limitDate = date('Y-m-d H:i:s', $limitTimestamp);
        }

        $select = [
            'm.*',
            'c.email',
            'c.identifier',
        ];

        if (!empty($clientId)) {
            $lastVisit = ClientRoomDetails::getInstance()->getLastVisitDateByRoom($clientId, $roomId);
            if (empty($lastVisit)) {
                $lastVisit = date('Y-m-d H:i:s', time());
            }
            $select[] = "IF('" . $lastVisit . "' < m.sendAt, 0, 1) isOpened";
        }
        $query = (new Query())
            ->select($select
            )
            ->from(Message::tableName() . ' m')
            ->leftJoin(
                Client::tableName() . ' c',
                'm.ownerId=c.id'
            )
            ->where(['m.roomId' => $roomId]);
        if (isset($limitDate)) {
            $query->andWhere(['>', 'm.sendAt', $limitDate]);
        }
        if (!empty($page)) {
            $query->offset($page * $limit);
        }
        $data = $query
            ->orderBy('m.sendAt DESC')
            ->limit($limit)
            ->all();

        $sortBy = array_column($data, 'sendAt');
        array_multisort($sortBy, SORT_ASC, $data);
        return $data;
    }

    public function getUnreadMessagesCount(int $emailId, ?string $lastVisitDatetime, int $roomId)
    {
        return (new Query())
            ->select([
                'm.id'
            ])
            ->from(Message::tableName() . ' m')
            ->leftJoin(
                crdAR::tableName() . ' crd',
                'crd.roomId=m.roomId AND crd.emailId=:emailId',
                [':emailId' => $emailId]
            )
            ->where(['m.roomId' => $roomId])
            ->andWhere([
                'or' ,
                [
                    '>',
                    'm.sendAt',
                    $lastVisitDatetime
                ],
                'crd.lastVisitDatetime is NULL'
            ])
            ->andWhere(['<>', 'm.ownerId', $emailId])
            ->count();
    }

    public function addMessage(array $data): array
    {
        $newNessage            = new Message();
        $newNessage->sendAt    = date('Y-m-d H:i:s');
        $newNessage->message   = $data['message'];
        $newNessage->roomId    = $data['roomId'];
        $newNessage->ownerId   = $data['ownerId'];
        $newNessage->isCommand = $data['isCommand'] ?? 0;
        $newNessage->save();
        return $newNessage->toArray();
    }
}
