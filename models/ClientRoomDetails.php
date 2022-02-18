<?php

namespace app\models;

use app\extentions\traits\Singleton;
use app\models\activeRecord\Client;
use yii\db\Query;
use app\models\activeRecord\ClientRoomDetails as CRDActiveRecord;

class ClientRoomDetails
{
    use Singleton;

    public function getLastVisitDateByRoom(int $emailId, int $roomId)
    {
        return (new Query())
            ->select(['lastVisitDatetime'])
            ->from(CRDActiveRecord::tableName())
            ->where(['emailId' => $emailId])
            ->andWhere(['roomId' => $roomId])
            ->limit(1)
            ->one()['lastVisitDatetime'] ?? null;
    }

    public function setLastVisitDatetime(int $emailId, array $roomIds)
    {
        CRDActiveRecord::deleteAll(['emailId' => $emailId, 'roomId' => $roomIds]);
        foreach ($roomIds as $roomId) {
            $clientRoomDetails = new CRDActiveRecord();
            $clientRoomDetails->emailId = $emailId;
            $clientRoomDetails->roomId = $roomId;
            $clientRoomDetails->lastVisitDatetime = date('Y-m-d H:i:s', time());
            $clientRoomDetails->save();
        }
    }
}
