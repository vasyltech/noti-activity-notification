<?php

namespace Noti\Core;

class NotificationManager
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_globalPolicy = null;

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function trigger()
    {
        $factory = EventPolicyFactory::getInstance();

        // Get the list of sealed events
        $events     = Repository::getPendingForNotificationEvents();
        $packages   = array();
        $discharged = array();

        // Aggregating the notifications by type prior to sending them
        foreach($events as $event) {
            $type = $factory->getEventTypeById($event['post_id']);

            if ($type) {
                $notifications = self::getActiveNotificationTypes(
                    $type->policy->Notification
                );

                if (count($notifications)) { // Ok, do we even need to send anything?
                    self::preparePackages(
                        $packages, $notifications, $event, $type
                    );
                } else { // No notifications? Discharge from attempts to send event
                    array_push($discharged, $event['id']);
                }
            }
        }

        $success = array();
        $failure = array();

        // Send all the packages
        foreach($packages as $package) {
            if (self::sendPackage($package)) { // Updating status
                array_push($success, ...$package->Events);
            } else {
                array_push($failure, ... $package->Events);
            }
        }

        // Finally update event(s) status accordingly based on the following rules:
        // - If event has multiple types of notifications and at least one was success
        //   fully emitted, update event's status as "Notified";
        // - If all type of notifications failed, update event's status to either
        //   X + 1 attempts failed (after certain number of failed attempts, no more
        //   attempts will be conducted to send an event)
        $successIds = array_unique(
            array_map(function($e){ return $e['id']; }, $success)
        );

        if (count($successIds)) {
            Repository::updateEventsStatus($successIds, Repository::STATUS_NOTIFIED);
        }

        $maxFailure = apply_filters('noti_max_notification_attempts', 3);
        $failureIds = array();

        if (count($failure)) {
            foreach($failure as $event) {
                if (!in_array($event['id'], $successIds, true)) {
                    if ($event['attempt'] >= $maxFailure) {
                        array_push($discharged, $event['id']);
                    } else {
                        array_push($failureIds, $event['id']);
                    }
                }
            }
        }

        if (count($discharged)) {
            Repository::updateEventsStatus(
                $discharged, Repository::STATUS_DISCHARGED
            );
        }

        if (count($failureIds)) {
            Repository::updateEventsAttempts($failureIds);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $notifications
     *
     * @return array
     */
    protected static function getActiveNotificationTypes($notifications)
    {
        $response = array();

        if (is_array($notifications)) {
            // Get global notification configurations
            $global = self::_getGlobalPolicy();

            foreach($notifications as $notification) {
                $final = clone $notification;

                if ($final->Status === 'active') {
                    // Let's find the same notification type in global settings &
                    // merge them with event type specific settings
                    foreach($global as $globalNotification) {
                        if ($globalNotification->Type === $final->Type) {
                            foreach($globalNotification as $key => $value) {
                                if (!property_exists($final, $key)) {
                                    $final->{$key} = $value;
                                }
                            }
                        }
                    }

                    array_push($response, $final);
                }
            }
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $packages
     * @param [type] $notifications
     * @param [type] $event
     * @return void
     */
    protected static function preparePackages(&$packages, $notifications, $event)
    {
        $factory = EventPolicyFactory::getInstance();

        foreach($notifications as $notification) {
            if (!isset($packages[$notification->Type])) {
                $packages[$notification->Type] = $notification;

                // Also add container for the list of message
                $packages[$notification->Type]->Messages = array();
                $packages[$notification->Type]->Events = array();
            }

            if ($notification->Type === 'email') {
                $subscribers = $factory->getEventTypeSubscribers(
                    $event['post_id']
                );
                $packages[$notification->Type]->Receivers = array();

                foreach($subscribers as $subscriber) {
                    $user = get_user_by('id', $subscriber);

                    if (is_a($user, 'WP_User')) {
                        array_push(
                            $packages[$notification->Type]->Receivers,
                            $user->user_email
                        );
                    }
                }
            }

            if ($notification->Type === 'webhook') {
                array_push(
                    $packages[$notification->Type]->Messages,
                    $factory->hydrate(
                        $notification->Payload ? json_encode($notification->Payload) : '{}',
                        array(
                            'eventType' => get_post($event['post_id']),
                            'event'     => $event,
                            'metadata'  => Repository::getEventMeta($event['id'])
                        )
                    )->getPolicyTree()
                );
            } else {
                array_push(
                    $packages[$notification->Type]->Messages,
                    EventManager::prepareEventStringMessage(
                        $event, null, $notification->MessageMarkdown
                    )
                );
            }

            array_push($packages[$notification->Type]->Events, $event);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $package
     * @return void
     */
    protected static function sendPackage($package)
    {
        $result = false;

        if ($package->Type === 'email') {
            $result = wp_mail(
                $package->Receivers,
                $package->Subject ?? __('Noti: Website Activity Notifications'),
                implode('<br/>', $package->Messages),
                array('Content-Type: text/html; charset=UTF-8')
            );
        } elseif ($package->Type === 'file') {
            $dirname = dirname($package->Filepath);

            if ($dirname && is_writable($dirname)) {
                $result = file_put_contents(
                    $package->Filepath,
                    implode(PHP_EOL, $package->Messages),
                    FILE_APPEND
                );
            }
        } elseif ($package->Type === 'webhook') {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $package->Url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

            if (property_exists($package, 'Headers') && is_array($package->Headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $package->Headers);
            }

            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $package->Method ?? 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($package->Messages));

            curl_exec($ch);

            $code   = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $result = $code >= 200 && $code < 299;

            curl_close($ch);
        }

        return $result;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    private static function _getGlobalPolicy()
    {
        if (is_null(self::$_globalPolicy)) {
            $json = get_option('noti-notifications', '[]');

            if (is_string($json)) {
                $manager = EventPolicyFactory::getInstance()->hydrate($json);

                self::$_globalPolicy = $manager->getPolicyTree();
            } else {
                self::$_globalPolicy = [];
            }
        }

        return self::$_globalPolicy;
    }

}