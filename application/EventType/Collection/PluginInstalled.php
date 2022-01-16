<?php

namespace ReactiveLog\EventType\Collection;

use ReactiveLog\EventType\TypeAbstract;

class PluginActivated extends TypeAbstract
{

    const UUID = '6035d67b-184f-4cae-9e40-6925d4cdcbae';
    const STATUS = 'active';
    const TITLE  = 'Plugin Activation';
    const DESCRIPTION = 'Track all plugins activation';
    const JSON_FILE = __DIR__ . '/plugin-installed.json';

}

if (defined('REACTIVE_LOG_KEY')) {
    return new PluginActivated();
}
