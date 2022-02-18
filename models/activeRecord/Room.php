<?php

namespace app\models\activeRecord;

use yii\db\ActiveRecord;

class Room extends ActiveRecord
{
    public static function tableName()
    {
        return 'room';
    }

    public function rules()
    {
        return [
            [
                ['id', 'title', 'isPublic', 'historyDaysLimit', 'historyMessagesLimit'], 'safe'
            ]
        ];
    }
}
