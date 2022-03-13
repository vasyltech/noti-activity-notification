<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Restful;

use WP_REST_Request,
    Noti\Core\Repository,
    Noti\Core\EventManager,
    Noti\Core\OptionManager,
    Noti\Core\EventTypeManager;

/**
 * Undocumented class
 */
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
        add_action('rest_api_init', function () {
            register_rest_route('noti/v1', '/events', array(
                'methods' => 'GET',
                'callback' => array($this, 'getEvents'),
                'permission_callback' => function () {
                    return current_user_can('edit_others_posts');
                }
            ));

            register_rest_route('noti/v1', '/event-types', array(
                'methods' => 'GET',
                'callback' => array($this, 'getEventTypes'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            ));

            register_rest_route('noti/v1', '/bulk/event-type', array(
                'methods' => 'PUT',
                'callback' => array($this, 'updateEventTypes'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            ));

            register_rest_route('noti/v1', '/setup', array(
                'methods' => 'POST',
                'callback' => array($this, 'setup'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                },
                'args' => array(
                    'installTypes' => array('type' => 'boolean')
                )
            ));

            register_rest_route('noti/v1', '/event-types', array(
                'methods' => 'POST',
                'callback' => array($this, 'installPostTypes'),
                'permission_callback' => function () {
                    return current_user_can('administrator');
                }
            ));
        });
    }

    /**
     * Undocumented function
     *
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public function getEvents(WP_REST_Request $request)
    {
        $filters = array(
            'search'      => trim($request->get_param('search')),
            'event_type'  => $request->get_param('event_type')
        );

        // Convert the date range to date time
        $range = $request->get_param('date_range');

        if ($range) {
            $filters['since'] = date('Y-m-d 00:00:00', strtotime($range));
        }

        // Add event level if selected any specific
        $event_level = trim($request->get_param('event_level'));

        if (!empty($event_level) && ($event_level !== 'all')) {
            $filters['event_level'] = $event_level;
        }

        $response = array(
            'recordsTotal'    => Repository::getTotalEventCount(),
            'recordsFiltered' => Repository::getTotalEventCount($filters),
            'data'            => array()
        );

        // If we have at least some events to fetch, let's do it
        if ($response['recordsFiltered'] > 0) {
            $length = intval($request->get_param('length'));
            $offset = intval($request->get_param('offset'));

            $events = Repository::getEvents($filters, $length, $offset);

            foreach ($events as $event) {
                $meta = Repository::getEventMeta($event['id']);
                $time = $event['time'];

                $response['data'][] = array(
                    EventManager::prepareEventStringMessage($event, $meta),
                    $time . '<br/><b>' . $this->timestampToAgo($time) . '</b>',
                    isset($meta['user_ip']) ? $meta['user_ip'] : '----',
                    $event['counter']
                );
            }
        }


        return $response;
    }

    /**
     * Undocumented function
     *
     * @param WP_REST_Request $request
     *
     * @return array
     */
    public function getEventTypes(WP_REST_Request $request)
    {
        $filters = array(
            's'        => $request->get_param('search'),
            'category' => $request->get_param('category')
        );

        // Determine the post status
        $post_status = $request->get_param('status');

        if ($post_status === 'any') {
            $filters['post_status'] = array('draft', 'publish');
        } else {
            $filters['post_status'] = $post_status;
        }

        $num_posts = wp_count_posts('noti_event_type', 'readable');

        if ($post_status === 'trash') {
            $total_posts = $num_posts->trash;
        } else {
            $total_posts = $num_posts->publish + $num_posts->draft;
        }

        // Getting total number of filtered items
        $ids = get_posts(array_merge(array(
            'post_type'   => 'noti_event_type',
            'numberposts' => -1,
            'fields'      => 'ids',
            'post_status' => 'any'
        ), $filters));

        $response = array(
            'recordsTotal'    => $total_posts,
            'recordsFiltered' => count($ids),
            'data'            => array()
        );

        // Fetch all registered event types
        $types = EventTypeManager::getInstance()->getEventTypes(
            $filters,
            $request->get_param('length'),
            $request->get_param('offset')
        );

        foreach ($types as $type) {
            $response['data'][] = array(
                $type['id'],
                htmlspecialchars($type['title']),
                htmlspecialchars($type['description']),
                $type['status'],
                $type['actions'],
                htmlspecialchars($type['required_version'])
            );
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function updateEventTypes(WP_REST_Request $request)
    {
        $ids    = $request->get_param('ids');
        $action = $request->get_param('action');

        foreach ($ids as $id) {
            if ($action === 'activate') {
                if (current_user_can('publish_post', $id)) {
                    wp_update_post(array(
                        'ID'          => $id,
                        'post_status' => 'publish'
                    ));
                }
            } else if ($action === 'deactivate') {
                if (current_user_can('publish_post', $id)) {
                    wp_update_post(array(
                        'ID'          => $id,
                        'post_status' => 'draft'
                    ));
                }
            } else if ($action === 'trash') {
                if (current_user_can('delete_post', $id)) {
                    wp_trash_post($id);
                }
            } else if ($action === 'delete') {
                if (current_user_can('delete_post', $id)) {
                    wp_delete_post($id, true);
                }
            } else if ($action === 'subscribe') {
                if (current_user_can('administrator')) {
                    Repository::updateSubscription(array(
                        'site_id'       => get_current_blog_id(),
                        'post_id'       => $id,
                        'user_id'       => get_current_user_id(),
                        'is_subscribed' => 1
                    ));
                }
            } else if ($action === 'unsubscribe') {
                if (current_user_can('administrator')) {
                    Repository::updateSubscription(array(
                        'site_id'       => get_current_blog_id(),
                        'post_id'       => $id,
                        'user_id'       => get_current_user_id(),
                        'is_subscribed' => 0
                    ));
                }
            }
        }

        return $ids;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function setup(WP_REST_Request $request)
    {
        global $wpdb;

        if (!function_exists('dbDelta')) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        }

        // Prepare the base directory to all setup assets
        $basedir = NOTI_BASEDIR . '/setup';

        $sql = str_replace(
            '%prefix%',
            $wpdb->prefix,
            file_get_contents($basedir . '/install.sql')
        );

        dbDelta($sql);

        OptionManager::updateOption('noti-welcome', 0, true);

        // Also insert the default settings
        OptionManager::updateOption(
            'noti-notifications',
            file_get_contents($basedir . '/notifications.json'),
            true
        );
        OptionManager::updateOption(
            'noti-email-notification-tmpl',
            file_get_contents($basedir . '/notification-email-tmpl.txt'),
            true
        );

        // Registering two scheduled jobs
        if (!wp_next_scheduled('noti_cleanup_log')) {
            wp_schedule_event(time(), 'twicedaily', 'noti_cleanup_log');
        }

        if (!wp_next_scheduled('noti_send_notifications')) {
            wp_schedule_event(time(), 'noti_interval', 'noti_send_notifications');
        }

        // Finally, if user chose to also install available event types, do so
        if ($request->get_param('installTypes')) {
            $types    = array();
            $registry = json_decode(
                file_get_contents(NOTI_BASEDIR . '/setup/registry.json')
            );

            foreach ($registry as $item) {
                $item->policy = json_decode(file_get_contents(
                    NOTI_BASEDIR . '/setup/event-types/' . $item->guid . '.json'
                ));

                array_push($types, $item);
            }

            $this->installPostTypes($types);

            OptionManager::updateOption('noti-auto-update', 1, true);
        } else {
            OptionManager::updateOption('noti-auto-update', 0, true);
        }

        return true;
    }

    /**
     * Undocumented function
     *
     * @param array $types
     *
     * @return void
     */
    protected function installPostTypes($types)
    {
        foreach ($types as $type) {
            EventTypeManager::getInstance()->insertType($type);
        }

        return true;
    }

    /**
     * Undocumented function
     *
     * @param [type] $timestamp
     * @return void
     */
    protected function timestampToAgo($timestamp)
    {
        $response = null;

        $time_ago        = strtotime($timestamp);
        $current_time    = time();
        $time_difference = $current_time - $time_ago;
        $seconds         = $time_difference;

        $minutes = round($seconds / 60); // value 60 is seconds
        $hours   = round($seconds / 3600); //value 3600 is 60 minutes * 60 sec
        $days    = round($seconds / 86400); //86400 = 24 * 60 * 60;
        $weeks   = round($seconds / 604800); // 7*24*60*60;
        $months  = round($seconds / 2629440); //((365+365+365+365+366)/5/12)*24*60*60
        $years   = round($seconds / 31553280); //(365+365+365+365+366)/5 * 24 * 60 * 60

        if ($seconds <= 60) {
            $response = 'Just Now';
        } else if ($minutes <= 60) {
            $response = $minutes === 1 ? 'one minute ago' : "$minutes minutes ago";
        } else if ($hours <= 24) {
            $response = $hours === 1 ? 'an hour ago' : "$hours hours ago";
        } else if ($days <= 7) {
            $response = $days === 1 ? 'yesterday' : "$days days ago";
        } else if ($weeks <= 4.3) {
            $response = $weeks === 1 ? 'a week ago' : "$weeks weeks ago";
        } else if ($months <= 12) {
            $response = $months === 1 ? 'a month ago' : "$months months ago";
        } else {
            $response = $years === 1 ? 'a year ago' : "$years years ago";
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

}