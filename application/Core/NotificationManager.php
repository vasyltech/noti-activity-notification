<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

use Noti\Vendor\TemplateEngine\Manager as TemplateEngineManager;

class NotificationManager
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_globalPolicy = null;

    /**
     * Undocumented variable
     *
     * @var array
     */
    private static $_cache = array();

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function trigger()
    {
        // Get the list of sealed events
        $events     = Repository::getPendingForNotificationEvents();
        $packages   = array();
        $discharged = array();

        // Aggregating the notifications by type prior to sending them
        foreach($events as $event) {
            $notifications = self::getActiveNotificationTypesForEvent($event);

            if (!empty($notifications)) { // Ok, do we even need to send anything?
                self::preparePackages($packages, $notifications, $event);
            } else { // No notifications? Discharge from attempts to send event
                array_push($discharged, $event['id']);
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
     * @param array $event
     *
     * @return array
     */
    protected static function getActiveNotificationTypesForEvent(array $event)
    {
        $factory = EventPolicyFactory::getInstance();
        $typeId  = $event['post_id'];

        if (!isset(self::$_cache[$typeId])) {
            self::$_cache[$typeId] = array();

            $type = $factory->getEventTypeById($typeId);

            if ($type) {
                $candidates = array();

                // Check if event type has any notification types enabled and if
                // so, get all of them. However, that does not mean that notification
                // will be sent because it also may depend on type of type of
                // notification and if it requires subscribers
                if (property_exists($type->policy, 'Notifications')) {
                    array_push(
                        $candidates,
                        ...self::hydrateActiveNotificationTypes(
                            $type->policy->Notifications
                        )
                    );
                }

                // Now, if we have some notification types defined, let's also
                // grab the list of subscribers for those that expect receivers
                foreach($candidates as $candidate) {
                    if ($candidate->Type === 'email') {
                        $subscribers = $factory->getEventTypeSubscribers(
                            $event['post_id'], $event['site_id']
                        );

                        if (count($subscribers)) {
                            $candidate->Receivers = array();

                            foreach($subscribers as $subscriber) {
                                $user = get_user_by('id', $subscriber);

                                if (is_a($user, 'WP_User')) {
                                    array_push(
                                        $candidate->Receivers, $user->user_email
                                    );
                                }
                            }

                            array_push(self::$_cache[$typeId], $candidate);
                        }
                    } else {
                        array_push(self::$_cache[$typeId], $candidate);
                    }
                }
            }
        }

        return self::$_cache[$typeId];
    }

    /**
     * Undocumented function
     *
     * @param [type] $notifications
     *
     * @return array
     */
    protected static function hydrateActiveNotificationTypes($notifications)
    {
        $response = array();

        if (is_array($notifications)) {
            // Get global notification configurations
            $global = self::_getGlobalPolicy();

            foreach($notifications as $notification) {
                $merged = clone $notification;

                // Let's find the same notification type in global settings &
                // merge them with event type specific settings
                foreach($global as $globalNotification) {
                    if ($globalNotification->Type === $merged->Type) {
                        foreach($globalNotification as $key => $value) {
                            if (!property_exists($merged, $key)) {
                                $merged->{$key} = $value;
                            }
                        }
                    }
                }

                // Hydrate the config
                $manager = EventPolicyFactory::getInstance()->getPolicyManager(
                    json_encode($merged)
                );

                if (isset($manager->Status) && $manager->Status === 'active') {
                    array_push($response, $manager);
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
                $packages[$notification->Type] = clone $notification;

                // Also add container for the list of message
                $packages[$notification->Type]->Messages = array();
                $packages[$notification->Type]->Events = array();
            }

            if ($notification->Type === 'webhook') {
                array_push(
                    $packages[$notification->Type]->Messages,
                    $factory->getPolicyManager(
                        $notification->Payload ? json_encode($notification->Payload) : '{}',
                        array(
                            'eventType' => get_post($event['post_id']),
                            'event'     => $event,
                            'metadata'  => Repository::getEventMeta($event['id'])
                        )
                    )
                );
            } else {
                array_push(
                    $packages[$notification->Type]->Messages,
                    EventManager::prepareEventStringMessage(
                        $event, null, $notification->MessageMarkdown ?? null
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
            if ($package->SendAsHTML === true) {
                $body = TemplateEngineManager::getInstance()->render(
                    str_replace(PHP_EOL, '<br/>', $package->BodyTemplate),
                    array('messages' => $package->Messages)
                );
            } else {
                $body = TemplateEngineManager::getInstance()->render(
                    $package->BodyTemplate, array('messages' => $package->Messages)
                );
            }

            $result = wp_mail(
                $package->Receivers,
                $package->Subject ?? __('Noti: Activity Notifications'),
                $body,
                array_merge(
                    is_array($package->Headers) ? $package->Headers : array(),
                    array('Content-Type: text/html; charset=UTF-8')
                )
            );
        } elseif ($package->Type === 'file') {
            $filepath = EventPolicyFactory::getInstance()->hydrateString(
                $package->Filepath ?? ''
            );

            $dirname = dirname($filepath);

            if (is_dir($dirname) && is_writable($dirname)) {
                $result = file_put_contents(
                    $filepath,
                    implode(PHP_EOL, $package->Messages),
                    FILE_APPEND
                );
            }
        } elseif ($package->Type === 'webhook') {
            $args = array(
                'body'   => json_encode($package->Messages),
                'method' => $package->Method ?? 'POST'
            );

            if (property_exists($package, 'Headers') && is_array($package->Headers)) {
                $args['headers'] = $package->Headers;
            }

            $response = wp_remote_request($package->Url, $args);
            $result   = !is_wp_error($response);
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
            $json = OptionManager::getOption('noti-notifications', '[]');

            if (is_string($json)) {
                $decoded = json_decode($json);

                self::$_globalPolicy = is_array($decoded) ? $decoded : [];
            } else {
                self::$_globalPolicy = [];
            }
        }

        return self::$_globalPolicy;
    }

}