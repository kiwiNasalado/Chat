<?php

namespace app\components;

use app\models\ClientRoomDetails as CRDModel;
use app\models\Messages;
use app\models\Rooms;
use Ratchet\ConnectionInterface;
use app\models\activeRecord\Client as ClientAR;

class Client
{
    private ConnectionInterface $connection;
    private string $identifier;
    private string $email;
    private int $id;
    private string $error;
    private array $allowedRooms;

    public function __construct(string $identifier, ConnectionInterface $connection)
    {
        $this->connection = $connection;
        $this->identifier = $identifier;
        $client = $this->checkIdentifier($identifier);
        if (empty($client)) {
            $this->closeConnectionOnError();
        }
        $this->email = $client['email'];
        $this->id    = $client['id'];
        $this->renewAllowedRooms();
    }

    private function closeConnectionOnError(): void
    {
        if (!empty($this->error)) {
            $this->connection->close();
        }
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function setRoomVisit(int $roomId): void
    {
        CRDModel::getInstance()->setLastVisitDatetime($this->getId(), [$roomId]);
    }

    public function getUnreadMessages(): array
    {
        foreach ($this->allowedRooms as $roomId) {
            $lastVisitDate = CRDModel::getInstance()->getLastVisitDateByRoom($this->getId(), $roomId);
            $unreadMessagesByRoom = Messages::getInstance()->getUnreadMessagesCount($this->getId(), $lastVisitDate, $roomId);
            $unreadMessages[$roomId] = $unreadMessagesByRoom;
        }
        return $unreadMessages ?? [];
    }

    public function renewAllowedRooms(): void
    {
        $this->allowedRooms = array_column(Rooms::getInstance()->getAllowedRooms($this->getIdentifier()), 'id');
    }

    public function hasError(): bool
    {
        return !empty($this->error);
    }

    private function checkIdentifier(string $identifier): array
    {
        $client = null;
        if (empty($identifier)) {
            $this->error =  "No identifier founded...\n";
        } else {
            $client = ClientAR::findOne(['identifier' => $identifier]);
            if (empty($client)) {
                $this->error = "No client founded...\n";
            }
            $client = $client->toArray();
        }

        return $client;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAllowedRooms(): array
    {
        return $this->allowedRooms;
    }
}
