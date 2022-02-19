<?php

/**
 * @var string $chatPort
 * @var string $identifier
 * @var array $rooms
 */
?>

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
