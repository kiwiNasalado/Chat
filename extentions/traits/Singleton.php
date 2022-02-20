<?php

namespace app\extentions\traits;

trait Singleton
{
    private static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = self::_create();
        }
        return self::$instance;
    }

    protected static function _create()
    {
        return new static();
    }
}
