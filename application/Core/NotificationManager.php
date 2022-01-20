<?php

namespace ReactiveLog\Core;

class NotificationManager
{

    public static function trigger()
    {
        // Get the list of sealed events
        $events   = Repository::getSealedNewEvents();
        $messages = array();

        // Aggregating the notifications by type prior to sending them
        foreach($events as $event) {
            // $messages[] = EventTypeManager::getInstance()->prepareEventLogMessage(
            //     $event
            // );
        }

       // file_put_contents(__DIR__ . '/log.txt', print_r($messages, 1), FILE_APPEND);
    }
}