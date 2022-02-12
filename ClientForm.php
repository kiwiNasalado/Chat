<?php


class ClientForm extends \yii\base\Model
{
    public string $email;

    public function rules()
    {
        return [
            ['email', 'email']
        ];
    }
}
