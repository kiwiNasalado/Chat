<?php

namespace app\commands;

use app\models\activeRecord\Message;
use app\models\Messages;
use app\models\Rooms;
use yii\helpers\Console;

class HistoryCleanerController extends Command
{
    private int $iterationCount = 0;

    public function actionClean()
    {
        $this->stdout("History Cleaner started its work...\n");

        do {
            $rooms = Rooms::getInstance()->getRooms($this->iterationCount);
            if (!empty($rooms)) {
                $roomsCount = count($rooms);
                $this->iterationCount++;
                $this->stdout('Processing rooms of the ' . $this->iterationCount . " iteration:\n");
                Console::startProgress(0, $roomsCount);
                foreach ($rooms as $key => $roomParams) {
                    if ($this->isShouldTerminate()) {
                        $this->stdout("Timeout reached! Crone should terminate!\n");
                        break;
                    }
                    $this->deleteMessagesByDate($roomParams['id'], $roomParams['historyDaysLimit']);
                    $this->deleteMessagesByCount($roomParams['id'], $roomParams['historyMessagesLimit']);
                    Console::updateProgress($key + 1, $roomsCount);
                }
                $this->stdout("\n");
            }
        }while(!empty($rooms));

        $this->stdout("Cleaner finished its work successfully!\n");

    }

    private function deleteMessagesByDate(int $roomId, int $daysLimit)
    {
        $limitDate = date('Y-m-d H:i:s', (time() - ($daysLimit * 86400)));
        Message::deleteAll(['AND', ['roomId' => $roomId], ['<', 'sendAt', $limitDate]]);
    }

    private function deleteMessagesByCount(int $roomId, int $messagesLimit)
    {
        $lastMessage = Messages::getInstance()->getMessages($roomId, null, $messagesLimit - 1, 1, false);
        if(!empty($lastMessage)) {
            $message = $lastMessage[0];
            Message::deleteAll(['<', 'id', $message['id']]);
        }
    }
}
