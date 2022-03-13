<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

class Manager
{

    /**
     *
     */
    const PLUGIN_SLUG = 'noti-activity-notification/noti-activity-notification.php';

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_instance = null;

    /**
     * Undocumented function
     */
    protected function __construct()
    {
        // Bootstrap the Event Type manager
        EventTypeManager::bootstrap();

        // Bootstrap the Event policy factory
        $factory = EventPolicyFactory::bootstrap();

        // Confirming that upgrade is not needed
        if (OptionManager::getOption('noti-version') !== NOTI_VERSION) {
            $this->upgrade();
        }

        // Building the tree of active events and hooking them to the system
        foreach ($factory->getActiveEventTypes() as $type) {
            $this->registerEventType($type);
        }

        add_filter('noti_func_source', function ($func, $args) {
            if ($func === 'get_plugin_data') {
                $func = function () use ($args) {
                    return get_plugin_data(WP_PLUGIN_DIR . '/' . $args[0]);
                };
            }

            return $func;
        }, 10, 2);

        add_filter('cron_schedules', function($schedules) {
            return array_merge($schedules, array('noti_interval' => array(
                'interval' => 60,
                'display'  => __('Every Minute')
            )));
        });

        add_action('noti_cleanup_log', function() {
            $type = OptionManager::getOption('noti-cleanup-type', 'soft');

            if ($type === 'soft') {
                Repository::deleteEventsStatusAfter(
                    OptionManager::getOption('noti-keep-logs', 60),
                    Repository::STATUS_DELETED
                );
            } else {
                $ids = Repository::getEventIdsOlderThen(
                    OptionManager::getOption('noti-keep-logs', 60)
                );

                if (count($ids)) {
                    Repository::purgeEvents($ids);
                    Repository::purgeEventMetaByEventIds($ids);
                }
            }
        });

        add_action('noti_send_notifications', function () {
            NotificationManager::trigger();
        });

    }

    /**
     * Undocumented function
     *
     * @param object $eventType
     *
     * @return void
     *
     * @access protected
     */
    protected function registerEventType($eventType)
    {
        $scope    = uniqid('', true);
        $callback = function () use ($eventType, $scope) {
            // Get all hook attributes
            $args = func_get_args();

            // Parse the event policy and determine if we need to persist this event
            $manager = EventPolicyFactory::getInstance()->getPolicyManager(
                json_encode($eventType->policy),
                array(
                    'args'      => $args,
                    'scope'     => $scope,
                    'eventType' => $eventType->post
                )
            );

            // Seal group at
            $now = time();

            if ($manager->isApplicable()) {
                // If there is aggregation, let's calculate the unique group
                $group  = array();
                $sealed = $now;

                if (isset($manager->Aggregate)) {
                    $aggregate = $manager->Aggregate;

                    if (isset($aggregate->ByAttributes)) {
                        array_push($group, ...$aggregate->ByAttributes);
                    }

                    $ts    = $aggregate->ByTimeSegment ?? null;
                    $ts    = is_string($ts) ? strtolower(trim($ts)) : null;
                    $by_ts = 0;

                    if ($ts && preg_match('/^([\d]+)(y|m|d|h|i|s)$/i', $ts, $m)) {
                        if ($m[2] === 'y') {
                            $by_ts = intval($m[1]) * 31536000;
                        } elseif ($m[2] === 'm') {
                            $by_ts = intval($m[1]) * 2592000;
                        } elseif ($m[2] === 'd') {
                            $by_ts = intval($m[1]) * 86400;
                        } elseif ($m[2] === 'h') {
                            $by_ts = intval($m[1]) * 3600;
                        } elseif ($m[2] === 'i') {
                            $by_ts = intval($m[1]) * 60;
                        } elseif ($m[2] === 's') {
                            $by_ts = intval($m[1]);
                        }
                    }

                    if ($by_ts > 0) {
                        $group[] = floor($now / $by_ts);
                        $sealed  = $now + $by_ts;
                    }
                } else {
                    $group[] = $now;
                }

                // Let's generate the group hash
                if (in_array('crc32', hash_algos(), true)) {
                    $hash = hash('crc32', implode('', $group));
                } elseif (function_exists('crc32')) {
                    $hash = str_pad(
                        dechex(crc32(implode('', $group))), 8, '0', STR_PAD_LEFT
                    );
                } else {
                    $hash = substr(md5(implode('', $group)), 0, 8);
                }

                // Now, let's store the event
                Repository::insertEvent(array(
                    'site_id'    => get_current_blog_id(),
                    'post_id'    => $eventType->post_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'sealed_at'  => date('Y-m-d H:i:s', $sealed),
                    'hash'      => $hash
                ), $this->prepareEventMetadata($manager));
            }

            return array_shift($args);
        };

        // Now register the hook
        $this->registerHook($eventType->type, $eventType->hook, $callback);

        foreach ($eventType->listeners as $i => $listener) {
            $listener_cb = function () use ($i, $listener, $scope, $eventType) {
                // Get all hook attributes
                $args = func_get_args();

                // Parse the event policy and determine if we need to trigger the
                // listener
                $manager = EventPolicyFactory::getInstance()->getPolicyManager(
                    json_encode($listener->policy),
                    array(
                        'args'      => $args,
                        'eventType' => $eventType->post
                    )
                );

                if ($manager->isApplicable()) {
                    ListenerManager::addToScope($scope, $i, $manager->Metadata);
                }

                return array_shift($args);
            };

            $this->registerHook($listener->type, $listener->hook, $listener_cb);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $type
     * @param [type] $hook
     * @param [type] $cb
     * @return void
     */
    protected function registerHook($type, $hook, $cb)
    {
        if ($type === 'action') {
            add_action($hook, $cb, 1, 10);
        } else if ($type === 'filter') {
            add_filter($hook, $cb, 1, 10);
        }
    }

    /**
     * Undocumented function
     *
     * @param \JsonPolicy\Manager $manager
     *
     * @return void
     */
    protected function prepareEventMetadata($manager)
    {
        $response = (object) [];

        if (!isset($manager->Metadata) || !is_object($manager->Metadata)) {
            $metadata = (object) [];
        } else {
            $metadata = $manager->Metadata;
        }

        if (empty($metadata->level)) {
            $metadata->level = $manager->Level ?? 'info';
        }

        // Trim & prune all empty values
        foreach ($metadata as $key => $value) {
            $n = is_string($value) ? trim($value) : $value;

            if (!empty($n)) {
                $response->{$key} = $value;
            }
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function upgrade()
    {
        // Updating all official event types
        $auto_update = OptionManager::getOption('noti-auto-update', true);

        if ($auto_update) {
            $registry = json_decode(
                file_get_contents(NOTI_BASEDIR . '/setup/registry.json')
            );

            foreach ($registry as $item) {
                $item->policy = json_decode(file_get_contents(
                    NOTI_BASEDIR . '/setup/event-types/' . $item->guid . '.json'
                ));

                EventTypeManager::getInstance()->insertType($item);
            }
        }

        OptionManager::updateOption('noti-version', NOTI_VERSION);
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of the manager
     *
     * @return Manager
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::bootstrap();
    }

}