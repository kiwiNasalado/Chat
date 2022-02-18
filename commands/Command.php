<?php

namespace app\commands;

use yii\console\Controller;

class Command extends Controller
{
    protected int $startTime;

    protected int $croneTimeout = 60; //seconds

    public function init()
    {
        $this->startTime = time();
        parent::init();
    }

    public function isShouldTerminate()
    {
        return time() > ($this->startTime + $this->croneTimeout);
    }

}
