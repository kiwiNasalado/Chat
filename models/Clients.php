<?php

namespace app\models;

use app\extentions\traits\Singleton;
use yii\base\Model;

class Clients extends Model
{
    use Singleton;

    public const IDENTIFIER_SALT = 'Erf#34d#sdf321rwee';

    public function generateIdentifier(string $email): string
    {
        return (md5($email . self::IDENTIFIER_SALT));
    }
}
