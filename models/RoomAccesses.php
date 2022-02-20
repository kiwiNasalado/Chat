<?php

namespace app\models;

use app\extentions\traits\Singleton;
use app\models\activeRecord\Client;
use app\models\activeRecord\RoomAccess;
use yii\db\Query;

class RoomAccesses
{
    use Singleton;

    public function getPrivateRooms(string $identifier): array
    {
        return (new Query())
            ->select('ra.roomId')
            ->from(RoomAccess::tableName() . ' ra')
            ->innerJoin(
                Client::tableName() . ' c',
                'ra.emailId=c.id',
            )
            ->where(['c.identifier' => $identifier])
            ->all();
    }
}
