<?php

namespace ReactiveLog\Core;

use ReactiveLog\EventType\Manager as EventTypeManager;

class Manager
{

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
        // Building the tree of active events and hooking them to the system
        $event_types = EventTypeManager::getInstance()->getAllActiveEventTypes();

        foreach ($event_types as $type) {
            $this->registerEvent($type);
        }

        add_filter('noti_func_source', function ($func, $args) {
            if ($func === 'get_plugin_data') {
                $func = function () use ($args) {
                    return get_plugin_data(WP_PLUGIN_DIR . '/' . $args[0]);
                };
            }

            return $func;
        }, 10, 2);

        add_action('noti_cleanup_log', function() {
            Repository::trashOldLogs(get_option('reactivelog-keep-logs'));
        });

        if (!wp_next_scheduled('noti_cleanup_log')) {
            wp_schedule_event(time(), 'twicedaily', 'noti_cleanup_log');
        }
    }

    /**
     * Undocumented function
     *
     * @param array $event
     *
     * @return void
     *
     * @access protected
     */
    protected function registerEvent($event)
    {
        $scope    = uniqid('', true);
        $callback = function () use ($event, $scope) {
            // Get all hook attributes
            $args = func_get_args();

            $config = EventTypeManager::getInstance()->evaluateConfig(
                $event['config'],
                ['args' => $args, 'scope' => $scope]
            );

            if ($config !== null) {
                // If there is aggregation, let's calculate the unique group
                $group = array($event['post_id']);

                if (isset($config->Aggregate)) {
                    $aggregate = $config->Aggregate;

                    if (isset($aggregate->ByAttributes)) {
                        array_push($group, ...$aggregate->ByAttributes);
                    }

                    if (isset($aggregate->ByTimespan)) {
                        $group[] = time() - time() % intval($aggregate->ByTimespan);
                    }
                } else {
                    $group[] = time();
                }

                // Now, let's store the event
                Repository::insertEvent(array(
                    'post_id'    => $event['post_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'group'      => md5(implode('', $group))
                ), $this->prepareEventMetadata($config));
            }
        };

        // Now register the hook
        $this->registerHook($event['type'], $event['hook'], $callback);

        if (count($event['listeners'])) {
            foreach ($event['listeners'] as $i => $listener) {
                $listener_callback = function () use ($i, $listener, $scope) {
                    // Get all hook attributes
                    $args = func_get_args();

                    $config = EventTypeManager::getInstance()->evaluateConfig(
                        $listener['config'],
                        ['args' => $args]
                    );

                    if ($config !== null) {
                        ListenerManager::addToScope($scope, $i, $config->Data);
                    }
                };

                $this->registerHook(
                    $listener['type'],
                    $listener['hook'],
                    $listener_callback
                );
            }
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
     * @param [type] $config
     *
     * @return void
     */
    protected function prepareEventMetadata($config)
    {
        $response = (object) [];

        if (!isset($config->Metadata) || !is_object($config->Metadata)) {
            $metadata = (object) [];
        } else {
            $metadata = $config->Metadata;
        }

        if (empty($metadata->level)) {
            $metadata->level = $config->Level ?? 'info';
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
