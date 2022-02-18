<?php

/**
 * @var string $chatPort
 * @var string $identifier
 * @var array $rooms
 */

use yii\helpers\Html;
use yii\web\View;
//$this->registerJsFile("/js/chat.js", ['position' => View::POS_END]);
?>
<!--<div class="panel panel-default" id="chatWindow" data-identifier="--><?php //echo $identifier?><!--" data-port="--><?php //echo $chatPort ?><!--?">-->
<!--    <div class="panel-heading">--><?php //echo Html::encode($rooTitle ?? 'Default Name')?><!--</div>-->
<!--    <div class="panel-body" id="chat-body">-->
<!--        <div class="container">-->
<!--        </div>-->
<!--        <div class="panel-footer">-->
<!--            <div class="input-group" id="input-interface">-->
<!--                <input type="text" id="textHere" class="form-control">-->
<!--                <span class="input-group-btn">-->
<!--                    <button class="btn btn-success" type="button" onclick="enterChat()">Connect</button>-->
<!--                  </span>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</div>-->

<div class="row" style="display: block">
    <div class="col-lg-12">

        <div class="card chat-app" data-identifier="<?php echo $identifier?>" data-port="<?php echo $chatPort ?>">

            <div class=" hidden-sm text-right">
                <a href="javascript:void(0);" class="btn btn-sign-out" onclick="signOut()"><i class="fa fa-sign-out"></i></a>
            </div>
            <?php echo $this->render('//chat/rooms', ['rooms' => $rooms])?>
            <?php echo $this->render('//chat/chat_empty')?>
        </div>
    </div>
</div>
