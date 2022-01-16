<?php

namespace ReactiveLog\Core;

class ListenerManager
{

    protected static $scopes = [];


    public static function addToScope($scope, $listener, $data)
    {
        if (!isset(self::$scopes[$scope])) {
            self::$scopes[$scope] = array();
        }

        self::$scopes[$scope][$listener] = $data;
    }

    public static function getListenerData($scope, $listener)
    {
        return self::$scopes[$scope][$listener] ?? null;
    }

}