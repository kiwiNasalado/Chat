<?php

use app\models\LoginForm;
use yii\bootstrap4\ActiveForm;
use yii\helpers\Html;

/**
 * @var $form LoginForm
 * @var $isChatOnline bool
 */

$this->registerCssFile('/css/login.css');

?>
<div class="container d-flex justify-content-center">
    <div class="d-flex flex-column justify-content-between">
        <div class="card mt-3 p-5 login">
            <div>
                <div class="logo mb-3">SimpleChat</div>

                <p class="mb-1">Enter your email</p>
                <h4 class="mb-5 text-white">and start messaging!</h4>
            </div>
        </div>
        <div class="card two bg-white px-5 py-4 mb-3">
            <?php if ($isChatOnline) {
                $builder = ActiveForm::begin();

            echo$builder->field(
                    $form,
                    'email',
            ) ?>

            <div class="form-group">
                <?= Html::submitButton('Get Started', ['class' => 'btn btn-primary btn-block btn-lg mt-1 mb-2']) ?>
            </div>

            <?php
                ActiveForm::end();
            } else { ?>
                <p>Chat is currently offline</p>
            <?php }?>
        </div>
    </div>
</div>