<?php
/**
 * @var array $rooms
 */

use yii\helpers\Html;

?>
<div id="plist" class="people-list" onscroll="updateRoomList($(this))" data-page="0">
    <ul class="list-unstyled chat-list mt-2 mb-0">
        <?php
        foreach ($rooms as $room) {
            $message = '<li class="clearfix room" ';
            $message .= 'data-id="' . $room['id'] . '" data-page="0"><div class="about"><div class="name">';
            $message .= Html::encode($room['title'] ?? '');
            $message .= '<span class="badge badge-pill badge-primary pull-right"></span>';
            $message .= '</div><div class="status"><i class="fa fa-circle offline"></i></div></div></li>';
            echo $message;
        }
        ?>
    </ul>
</div>
