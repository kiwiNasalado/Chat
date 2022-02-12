<?php

namespace app\controllers;

use app\models\Chat;
use yii\redis\Connection;
use yii\web\Controller;

class ChatController extends Controller
{
    private ?string $chatPort = null;

    public function beforeAction($action)
    {
        $action = parent::beforeAction($action);

        $redis = new Connection();
        $this->chatPort = $redis->get(Chat::CHAT_PORT_REDIS_KEY);
        if (null === $this->chatPort) {
            return $this->redirect('/');
        }
        return $action;
    }

    public function actionRoomFirst(): string
    {
        return $this->render('//chat/room', ['chatPort' => $this->chatPort]);
    }

    public function actionRoomSecond(): string
    {
        return $this->render('//chat/room', ['chatPort' => $this->chatPort]);
    }

}
