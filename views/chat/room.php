<?php

/**
 * @var string $chatPort
 */

use yii\helpers\Html;
use yii\web\View;

$this->registerJs("var chatPort = " . $chatPort . ";", View::POS_BEGIN);
$this->registerJsFile("/js/chat.js", ['position' => View::POS_END]);
?>
<div class="panel panel-default">
    <div class="panel-heading"><?php echo Html::encode($rooTitle ?? 'Default Name')?></div>
    <div class="panel-body" id="chat-body">
        <div class="container">
        </div>
        <div class="panel-footer">
            <div class="input-group">
                <input type="text" id="textHere" class="form-control">
                <span class="input-group-btn">
                    <button class="btn btn-success" type="button" onclick="sendMessage()">Send</button>
                  </span>
            </div>
        </div>
    </div>
</div>
