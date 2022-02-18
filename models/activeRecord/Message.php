<?php

namespace app\models\activeRecord;

use yii\db\ActiveRecord;

class Message extends ActiveRecord
{
    public static function tableName()
    {
        return 'message';
    }

    public function rules()
    {
        return [
            [
                [
                    'id',
                    'sendAt',
                    'message',
                    'roomId',
                    'ownerId'
                ],
                'safe'
            ]
        ];
    }
}
