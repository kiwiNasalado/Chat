<?php

namespace app\components;

use app\models\activeRecord\Client as ClientAr;
use app\models\Messages;
use app\models\Rooms;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface
{
    public const CHAT_PORT_REDIS_KEY = 'CHAT_PORT';
    public const SYSTEM_RESPONSE_KEY = 'systemResponse';
    public const CONNECTIONS_LIMIT   = 1000;

    /**
     * @var $rooms array[Room]
     * @var $clients array[Client]
     */
    protected array $rooms;
    protected array $clients;
    protected int $connections = 0;

    public function onOpen(ConnectionInterface $conn) {
        if (self::CONNECTIONS_LIMIT <= $this->connections) {
            echo "Connections limit was reached!\n";
            $conn->send(json_encode([
                self::SYSTEM_RESPONSE_KEY => [
                    'key'   => 'connectionsLimitReached',
                    'value' => 'Connection limit reached!'
                ]
            ]));
            $conn->close();
            return;
        }
        $queryParams  = $this->getUriQueryParams($conn);
        $identifier   = $queryParams['identifier'] ?? '';
        $this->initClient($identifier, $conn);
        $this->initRooms($identifier);
        echo "New connection! (" . $conn->resourceId . ":" . $this->clients[$identifier]->getEmail() . ")\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $queryParams = $this->getUriQueryParams($from);
        $identifier = $queryParams['identifier'] ?? '';
        $message = json_decode($msg, true);

        if (!isset($this->clients[$identifier])) {
            $from->close();
        }

        if (isset($message['message'])) {
            $this->getMessageResponse($this->clients[$identifier], $message);
        } elseif (isset($message['command'])) {
            $this->processSystemCommand($this->clients[$identifier], $message['command'], $message['params'] ?? []);
        }
    }

    protected function initRooms(string $identifier): void
    {
        /**
         * @var $client Client
         */
        $client = $this->clients[$identifier];
        $rooms = array_fill_keys($client->getAllowedRooms(), []);
        foreach ($rooms as $roomId => $emptyArr) {
            if (!isset($this->rooms[$roomId])) {
                $room = new Room($roomId);
                $this->rooms[$roomId] = $room;
            }
        }
    }

    protected function initClient(string $identifier, ConnectionInterface $conn): void
    {
        if (isset($this->clients[$identifier])) {
            $oldConnection = $this->clients[$identifier]->getConnection();
            unset($this->clients[$identifier]);
            $oldConnection->send(json_encode([
                self::SYSTEM_RESPONSE_KEY => [
                    'key'   => 'newConnection',
                    'value' => 'New connection founded...Connection is about to close!',
                ]
            ]));
            $oldConnection->close();
            $this->connections--;
            foreach ($this->rooms as $room) {
                $room->removeClient($identifier);
            }
        }
        $client = new Client($identifier, $conn);
        if (!$client->hasError()) {
            $this->clients[$identifier] = $client;
            $this->connections++;
        } else {
            echo $client->getError();
            $conn->close();
        }
    }

    protected function getMessageResponse(Client $client, array $message)
    {
        $connection = $client->getConnection();
        if (empty($message['room']) || !isset($this->rooms[$message['room']]) || !isset($this->rooms[$message['room']]->getClients()[$client->getIdentifier()])) {
            echo 'Room is not allowed for connection ' . $connection->resourceId;
            return;
        }
        /**
         * @var $room Room
         */
        $room = $this->rooms[$message['room']];

        if (!empty($message['message'])) {
            if (preg_match(Room::getCommandsRegex(), $message['message'])) {
                $this->processClientCommand($message['message'], $client, $room);
            } else {
                $this->processCommonMessage($message['message'], $client, $room);
            }
        }
    }

    protected function processCommonMessage(string $message, Client $client, Room $room)
    {
        echo sprintf(
            'Connection %d:%s sending message "%s" to all connections in room [%s]%s' . "\n",
            $client->getConnection()->resourceId,
            $client->getEmail(),
            $message,
            $room->getId(),
            $room->getTitle()
        );

        $message = Messages::getInstance()->addMessage([
            'message' => $message,
            'roomId'  => $room->getId(),
            'ownerId' => $client->getId()
        ]);
        $message['email']      = $client->getEmail();
        $message['identifier'] = $client->getIdentifier();

        $room->sendAll([$message]);
    }

    protected function processClientCommand(string $message, Client $client, Room $room)
    {
        echo sprintf(
            'Connection %d:%s sending clientCommand "%s" to in room [%s]%s' . "\n",
            $client->getConnection()->resourceId,
            $client->getEmail(),
            $message,
            $room->getId(),
            $room->getTitle()
        );

        $clientCommand = '';
        $commands = Room::$commands;
        foreach ($commands as $command) {
            if (preg_match('/^\/' . $command . '/', $message)) {
                $clientCommand = $command;
                break;
            }
        }

        switch ($clientCommand) {
            case Room::COMMAND_ME:
                $this->clientCommandMe($message, $client, $room);
                break;
            case Room::COMMAND_SHOWMEMBERS:
                $this->clientCommandShowmembers($client, $room);
                break;
            case Room::COMMAND_DATE:
                $this->clientCommandDate($client);
                break;
        }
    }

    protected function clientCommandDate(Client $client): void
    {
        $client->getConnection()->send(json_encode([
            self::SYSTEM_RESPONSE_KEY => [
                'key'   => 'clientCommandDate',
                'value' => date('Y-m-d', time())
            ]
        ]));
    }

    protected function clientCommandShowmembers(Client $currentClient, Room $room): void
    {
        if (empty($room->getIsPublic())) {
            return;
        }
        $clients = $room->getClients();
        $clientsEmails = [];
        foreach ($clients as $client) {
            $clientsEmails[] = $client->getEmail();
        }

        $currentClient->getConnection()->send(json_encode([
            self::SYSTEM_RESPONSE_KEY => [
                'key'   => 'clientCommandShowmembers',
                'value' => implode(', ', $clientsEmails)
            ]
        ]));
    }

    protected function clientCommandMe(string $message, Client $client, Room $room): void
    {
        $message = str_replace('/' . Room::COMMAND_ME, '', $message);
        $message = Messages::getInstance()->addMessage([
            'message'   => $client->getEmail() . ' ' . $message,
            'roomId'    => $room->getId(),
            'ownerId'   => $client->getId(),
            'isCommand' => 1
        ]);

        $room->sendAll([$message]);
    }

    protected function processSystemCommand(Client $client, string $command, array $params = [])
    {
        $response = ['key' => '', 'value' => ''];
        $identifier = $client->getIdentifier();
        /**
         * @var $client Client
         */
        $client = $this->clients[$identifier];
        switch ($command) {
            case 'get-rooms-online':
                $response = $this->commandGetRoomsOnline($identifier);
                break;
            case 'get-unread-messages':
                $response = $this->commandGetUnreadMessages($client);
                break;
            case 'set-last-visit':
                $this->commandSetLastVisit($client, (int) $params['roomId']);
                break;
            case 'go-to-private-room':
                $response = $this->commandGoToPrivateRoom($client, $params['email']);
                break;
            case 'set-room-settings':
                $this->commandSetRoomSettings($params['roomId'], $params['key'], $params['value']);
                break;
        }

        if (!empty($response['key'])) {
            $this->clients[$identifier]->getConnection()->send(json_encode([self::SYSTEM_RESPONSE_KEY => $response]));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $queryParams = $this->getUriQueryParams($conn);
        if (!empty($queryParams['identifier']) && isset($this->clients[$queryParams['identifier']])) {
            /**
             * @var $client Client
             */
            $client = $this->clients[$queryParams['identifier']];
            $conn = $client->getConnection();
            $conn->close();
            $this->connections--;
            $this->removeClientFromRooms($queryParams['identifier']);
            unset($this->clients[$queryParams['identifier']]);
            echo "Connection {$conn->resourceId} has disconnected\n";
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function commandGetRoomsOnline(string $identifier): array
    {
        $notEmptyRooms = $this->getNotEmptyRooms($identifier);
        $this->setClientToRooms($identifier);
        return [
            'key'   => 'roomsOnline',
            'value' => $notEmptyRooms
        ];
    }

    protected function commandGetUnreadMessages(Client $client): array
    {
        return [
            'key'   => 'unreadMessages',
            'value' => $client->getUnreadMessages()
        ];
    }

    protected function commandSetLastVisit(Client $client, int $roomId): void
    {
        $client->setRoomVisit($roomId);
    }

    protected function commandGoToPrivateRoom(Client $client, string $secondEmail): array
    {
        $secondClient = ClientAr::findOne(['email' => $secondEmail]);
        if (empty($secondClient)) {
            return ['key' => '', 'value' => ''];
        }
        $room = Rooms::getInstance()->getPrivateRoomIfExists($client->getEmail(), $secondClient->email);
        $responseKey = 'goToPrivateRoom';
        if (false == $room) {
            $responseKey = 'createAndGoPrivateRoom';
            $room = Rooms::getInstance()->createPrivateRoom($client, $secondClient);
            $client->renewAllowedRooms();
            $this->rooms[$room['id']] = new Room($room['id']);
        }

        if (!in_array($client, $this->rooms[$room['id']]->getClients())) {
            $this->rooms[$room['id']]->addClient($client);
        }
        $responseData = $room;
        $responseData['isOnline'] = $this->rooms[$responseData['id']]->getClients();

        $response = [
            'key'   => $responseKey,
            'value' => $responseData
        ];
        if ('createAndGoPrivateRoom' === $response['key'] && isset($this->clients[$secondClient->identifier])) {
            $additionalResponse = $response;
            $additionalResponse['key'] = 'appendPrivateRoom';
            $this->rooms[$room['id']]->addClient($this->clients[$secondClient->identifier]);
            $this->clients[$secondClient->identifier]->renewAllowedRooms();
            $this->clients[$secondClient->identifier]->getConnection()->send(json_encode([self::SYSTEM_RESPONSE_KEY => $additionalResponse]));
        }

        return $response;
    }

    protected function commandSetRoomSettings(int $roomId, string $settingName, string $settingValue): void
    {
        if (isset($this->rooms[$roomId])) {
            /**
             * @var $room Room
             */
            $room = $this->rooms[$roomId];
            $room->updateRoomHistoryLimit($settingName, $settingValue);
        }
    }

    protected function getUriQueryParams(ConnectionInterface $connection): array
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

    private function getNotEmptyRooms(string $identifier): array
    {
        $result = [];
        foreach ($this->rooms as $roomId => $room) {
            /**
             * @var $room Room
             */
            $clients = $room->getClients();
            if (empty($clients) || (1 === count($clients) && isset($clients[$identifier]))) {
                continue;
            }
            $result[] = $roomId;
        }
        return $result;
    }

    private function setClientToRooms(string $identifier)
    {
        /**
         * @var $client Client
         */
        $client = $this->clients[$identifier];
        $allowedRooms = $client->getAllowedRooms();
        foreach ($this->rooms as $roomId => &$room) {
            if (in_array($roomId, $allowedRooms) && !isset($room->getClients()[$identifier])) {
                /**
                 * @var $room Room
                 */
                $room->addClient($this->clients[$identifier]);
            }
        }
        unset($room);
    }

    private function removeClientFromRooms(string $identifier)
    {
        foreach ($this->rooms as $room) {
            /**
             * @var $room Room
             */
            $room->removeClient($identifier);
        }
    }
}
