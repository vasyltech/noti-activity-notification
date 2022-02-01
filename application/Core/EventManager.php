<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

use Noti\Vendor\Parsedown as MarkdownManager;

class EventManager
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_markdown = null;

    /**
     * Undocumented function
     *
     * @param [type] $event
     * @param [type] $metadata
     * @param [type] $message
     *
     * @return void
     */
    public static function prepareEventStringMessage(
        $event, $metadata = null, $message = null
    ) {
        $response = null;

        if (is_null(self::$_markdown)) {
            self::$_markdown = new MarkdownManager;
        }

        if (is_null($metadata)) {
            $metadata = Repository::getEventMeta($event['id']);
        }

        $factory = EventPolicyFactory::getInstance();
        $type    = $factory->getEventTypeById($event['post_id']);

        if ($type) {
            $message = $message ?? $type->policy->MessageMarkdown;

            // Set default message
            if (!is_string($message) || empty($message)) {
                $message = 'The event **${EVENT_TYPE.post_title}** occurred';
            }

            $response = self::$_markdown->line($factory->hydrateString(
                $message,
                array(
                    'eventType' => $type->post,
                    'event'     => $event,
                    'metadata'  => $metadata
                )
            ));
        } else {
            $response  = 'Cannot for sure determine the type of event. ';
            $response .= 'Probably the event type was deleted';
        }

        return $response;
    }

}