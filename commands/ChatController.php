<?php

namespace app\commands;

use app\components\Chat;
use Ratchet\Server\IoServer;
use yii\console\Controller;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChatController extends Controller
{
    public function actionStart()
    {
        if (Chat::getIsPortFree()) {
            $server = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        new Chat()
                    )
                ),
                Chat::CHAT_PORT
            );
            $this->stdout("Server is up and running on port:" . Chat::CHAT_PORT . "\n");
            $server->run();
        } else {
            $this->stdout("Server is already up and running!\n");
        }
    }
}
