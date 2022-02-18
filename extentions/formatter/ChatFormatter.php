<?php


class ChatFormatter
{

    public static function formatRoom(int $id, string $title, int $online = 0)
    {
        $isOnline = $online > 0;
        $onlineMessage = 'Offline';
        if ($online > 0) {
            $onlineMessage = 'Online' . ($online > 1 ? '(' . $online . ')' : '');
        }
        $room = '<div class="about">' .
            '<div class="name">' . $title . '</div>' .
            '<div class="status"><i class="fa fa-circle ' . ($isOnline? 'online' : 'offline') . '"></i>' .
            $onlineMessage . '</div></div>';
        return $room;
    }
}
