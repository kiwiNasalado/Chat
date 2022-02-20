<?php

namespace app\models\activeRecord;

use yii\db\ActiveRecord;

class Client extends ActiveRecord
{

    public static function tableName()
    {
        return 'client';
    }

    public function rules()
    {
        return [
            [
                ['id', 'email', 'identifier'], 'safe'
            ]
        ];
    }

}
