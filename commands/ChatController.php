<?php

namespace app\commands;

use app\components\Chat;
use Ratchet\Server\IoServer;
use yii\console\Controller;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use yii\redis\Connection;

class ChatController extends Controller
{
    public function actionStart()
    {
        $port = $this->getFreePort();
        $redis = new Connection();
        /*3rd parameter expireTime depends on how command will work*/
        $redis->set(Chat::CHAT_PORT_REDIS_KEY, $port);
        try {
            $server = IoServer::factory(
                new HttpServer(
                    new WsServer(
                        new Chat()
                    )
                ),
                $port
            );
            $this->stdout("Server is up and running on port:" . $port . "\n");
            $server->run();
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n");
            $redis->close();
        }
    }

    private function getFreePort()
    {
        $sock = socket_create_listen(0);
        socket_getsockname($sock, $addr, $port);
        socket_close($sock);

        return $port;
    }
}
