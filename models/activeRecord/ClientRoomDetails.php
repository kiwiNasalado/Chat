<?php

namespace app\models\activeRecord;

use yii\db\ActiveRecord;

class ClientRoomDetails extends ActiveRecord
{

    public static function tableName()
    {
        return 'client_room_details';
    }

    public function rules()
    {
        return [
            [['id', 'emailId', 'roomId', 'lastVisitDatetime'], 'safe']
        ];
    }

}
