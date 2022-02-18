<?php

namespace app\components;

use app\models\activeRecord\Client;
use app\models\activeRecord\ClientRoomDetails;
use app\models\activeRecord\Message;
use app\models\activeRecord\Room;
use app\models\activeRecord\RoomAccess;
use app\models\ClientRoomDetails as CRDModel;
use app\models\Messages;
use app\models\RoomAccesses;
use app\models\Rooms;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface
{
    public const CHAT_PORT_REDIS_KEY = 'CHAT_PORT';

    protected $connections = 0;
    protected $rooms;
    protected $clients;

    public function onOpen(ConnectionInterface $conn) {
        $queryParams  = $this->getUriQueryParams($conn);
        $identifier   = $queryParams['identifier'] ?? '';
        if (empty($identifier)) {
            echo "No identifier founded...\n";
            return;
        }
        $client = Client::findOne(['identifier' => $identifier]);
        if (empty($client)) {
            echo "No client founded...\n";
            return;
        }
        $allowedClientRooms = array_column(Rooms::getInstance()->getAllowedRooms($identifier), 'id');

        $rooms = array_fill_keys($allowedClientRooms, []);

        foreach ($rooms as $roomId => $emptyArr) {
            if (!isset($this->rooms[$roomId])) {
                $this->rooms[$roomId] = $emptyArr;
            }
        }

        if (isset($this->clients[$identifier])) {
            $this->clients[$identifier]->close();
            unset($this->clients[$identifier]);
        }

        $this->clients[$identifier] = $conn;
        $this->connections++;
        echo "New connection! (" . $conn->resourceId . ":" . $client->email . ")\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $queryParams = $this->getUriQueryParams($from);
        $identifier = $queryParams['identifier'] ?? '';
        $message = json_decode($msg, true);

        if (empty($identifier)) {
            echo "No identifier founded...\n";
            return;
        }
        $client = Client::findOne(['identifier' => $identifier]);
        if (empty($client)) {
            echo "No client founded...\n";
            return;
        }
        $client = $client->toArray();

        if (isset($message['message'])) {
            $this->getMessageResponse($client, $message);
        } elseif (isset($message['command'])) {
            $this->processCommand($client, $message['command'], $message['params'] ?? []);
        }
    }

    protected function getMessageResponse(array $client, array $message)
    {
        $identifier = $client['identifier'];
        $conn = $this->clients[$identifier];
        if (empty($message['room']) || !Rooms::getInstance()->checkIsRoomAllowed($identifier, (int) $message['room'])) {
            echo "Room is not allowed for connection " . $conn->resourceId . "\n";
            return;
        }

        $room = Room::findOne(['id' => $message['room']]);

        if (null !== $room) {
            $room = $room->toArray();
        }
        echo sprintf(
            'Connection %d:%s sending message "%s" to all connections in room [%s]%s' . "\n",
            $conn->resourceId,
            $client['email'],
            $message['message'],
            $room['id'],
            $room['title']
        );

        $newNessage = new Message();
        $newNessage->sendAt = date('Y-m-d H:i:s');
        $newNessage->message = $message['message'];
        $newNessage->roomId = $room['id'];
        $newNessage->ownerId = $client['id'];
        $newNessage->save();
        $message = $newNessage->toArray();
        $message['email'] = $client['email'];
        $message['identifier'] = $client['identifier'];

        foreach ($this->rooms[$room['id']] as $identifier) {
            $connection = $this->clients[$identifier];
            $connection->send(json_encode([$message]));
        }
    }

    protected function processCommand(array $client, string $command, array $params = [])
    {
        $response = ['key' => '', 'value' => ''];
        $identifier = $client['identifier'];
        switch ($command) {
            case 'get-rooms-online':
                $notEmptyRooms = $this->getNotEmptyRooms($identifier);
                $this->setClientToRooms($identifier);
                $response = [
                    'key'   => 'roomsOnline',
                    'value' => $notEmptyRooms
                ];
                break;
            case 'get-unread-messages':
                $client = Client::findOne(['identifier' => $identifier]);
                $allowedRooms = array_column(Rooms::getInstance()->getAllowedRooms($identifier), 'id');

                foreach ($allowedRooms as $roomId) {
                    $lastVisitDate = CRDModel::getInstance()->getLastVisitDateByRoom($client->id, $roomId);
                    $unreadMessagesByRoom = Messages::getInstance()->getUnreadMessagesCount($client->id, $lastVisitDate, $roomId);
                    $unreadMessages[$roomId] = $unreadMessagesByRoom;
                }
                $response = [
                    'key'   => 'unreadMessages',
                    'value' => $unreadMessages ?? []
                ];
                break;
            case 'set-last-visit':
                $client = Client::findOne(['identifier' => $identifier]);
                if (null !== $client) {
                    CRDModel::getInstance()->setLastVisitDatetime((int) $client->id, [(int) $params['roomId']]);
                }
                break;
            case 'go-to-private-room':
                $client = Client::findOne(['identifier' => $identifier]);
                $secondClient = Client::findOne(['email' => $params['email']]);
                $room = Rooms::getInstance()->getPrivateRoomIfExists($client->email, $secondClient->email);
                $responseKey = 'goToPrivateRoom';
                if (false == $room) {
                    $responseKey = 'createAndGoPrivateRoom';
                    $room = Rooms::getInstance()->createPrivateRoom($client, $secondClient);
                }

                if (!in_array($identifier, $this->rooms[$room['id']])) {
                    $this->rooms[$room['id']][] = $identifier;
                }

                $responseData = $room;
                $responseData['isOnline'] = $this->rooms[$responseData['id']];

                $response = [
                    'key'   => $responseKey,
                    'value' => $responseData
                ];
                if ('createAndGoPrivateRoom' === $response['key'] && isset($this->clients[$secondClient->identifier])) {
                    $additionalResponse = $response;
                    $additionalResponse['key'] = 'appendPrivateRoom';
                    if (!in_array($secondClient->identifier, $this->rooms[$room['id']])) {
                        $this->rooms[$room['id']][] = $secondClient->identifier;
                    }
                    $this->clients[$secondClient->identifier]->send(json_encode(['systemResponse' => $additionalResponse]));
                }
                break;
            case 'set-room-settings':
                switch ($params['key']) {
                    case 'days':
                        $setting = 'historyDaysLimit';
                        break;
                    case 'messages':
                        $setting = 'historyMessagesLimit';
                        break;
                }
                if (isset($setting)) {
                    //todo validation on value
                    Room::updateAll([$setting => $params['value']], ['id' => $params['roomId']]);
                }
                break;
        }

        if (!empty($response['key'])) { //maybebug?
            $this->clients[$identifier]->send(json_encode(['systemResponse' => $response]));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $queryParams = $this->getUriQueryParams($conn);
        /**
         * @var $room Room|array
         */
        if (!empty($queryParams['identifier'])) {
            $conn = $this->clients[$queryParams['identifier']];
            $conn->close();
            $this->connections--;
            $this->removeClientFromRooms($queryParams['identifier']);
            unset($this->clients[$queryParams['identifier']]);
            echo "Connection {$conn->resourceId} has disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    private function getUriQueryParams(ConnectionInterface $connection): array
    {
        $query = $connection->httpRequest->getUri()->getQuery();
        if ('?' === $query[0]) {
            $query = substr($query, 1);
        }

        $params = explode('&', $query);
        foreach ($params as $param) {
            [$key, $value] = explode('=', $param);
            $result[$key] = $value;
        }

        return $result ?? [];
    }

    private function getNotEmptyRooms(string $identifier)
    {
        $result = [];
        foreach ($this->rooms as $roomId => $clients) {
            if (empty($clients) || (1 === count($clients) && in_array($identifier, $clients))) {
                continue;
            }
            $result[] = $roomId;
        }
        return $result;
    }

    private function setClientToRooms(string $identifier)
    {
        $allowedRooms = array_column(Rooms::getInstance()->getAllowedRooms($identifier), 'id');
        foreach ($this->rooms as $roomId => &$clients) {
            if(in_array($roomId, $allowedRooms) && !in_array($identifier, $clients)) {
                $clients[] = $identifier;
            }
        }
        unset($clients);
    }

    private function removeClientFromRooms(string $identifier)
    {
        foreach ($this->rooms as &$clients) {
            unset($clients[array_search($identifier, $clients)]);
        }
    }
}
