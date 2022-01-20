<?php

namespace ReactiveLog\Core;

use ReactiveLog\Vendor\Parsedown as MarkdownManager;

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
     *
     * @return void
     */
    public static function prepareEventLogMessage($event, $metadata = null)
    {
        $response = null;

        if (is_null(self::$_markdown)) {
            self::$_markdown = new MarkdownManager;
        }

        if (is_null($metadata)) {
            $metadata = Repository::getEventMeta($event['id']);
        }

        $factory = EventPolicyFactory::getInstance();


        $type = $factory->getEventTypeById(intval($event['post_id']));

        if ($type) {
            $message = $type->policy->MessageMarkdown ?? null;

            // Set default message
            if (!is_string($message) || empty($message)) {
                $message = 'The event **${EVENT_TYPE.post_title}** occurred';
            }

            $response = self::$_markdown->text($factory->hydrateString(
                $message,
                array(
                    'eventType' => $type->post,
                    'event'     => $event,
                    'metadata'  => $metadata
                )
            ));
        } else {
            $response  = 'Cannot for sure determine the type of event.';
            $response .= 'Probably the event type was deleted';
        }

        return $response;
    }

}