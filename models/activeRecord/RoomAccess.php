<?php

namespace app\models\activeRecord;

use yii\db\ActiveRecord;

class RoomAccess extends ActiveRecord
{

    public static function tableName()
    {
        return 'room_access';
    }

    public function rules()
    {
        return [[['id', 'emailId', 'roomId'], 'safe']];
    }

}
