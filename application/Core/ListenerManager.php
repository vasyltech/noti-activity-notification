<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

class ListenerManager
{

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected static $scopes = [];

    /**
     * Undocumented function
     *
     * @param [type] $scope
     * @param [type] $listener
     * @param [type] $data
     * @return void
     */
    public static function addToScope($scope, $listener, $data)
    {
        if (!isset(self::$scopes[$scope])) {
            self::$scopes[$scope] = array();
        }

        self::$scopes[$scope][$listener] = $data;
    }

    /**
     * Undocumented function
     *
     * @param [type] $scope
     * @param [type] $listener
     * @return void
     */
    public static function getListenerData($scope, $listener)
    {
        return self::$scopes[$scope][$listener] ?? null;
    }

}