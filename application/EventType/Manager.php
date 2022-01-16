<?php

namespace ReactiveLog\EventType;

use ReactiveLog\EventType\Config\Manager as ConfigManager;

class Manager
{

     /**
     *
     */
    const HOOK_TYPES = array (
        'action', 'filter'
    );

    /**
     *
     */
    const FUNC_REGEXP = '/^([a-z_\x80-\xff][a-z\d_\x80-\xff]*)\(([^)]+)\)(.*)$/i';

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_instance = null;


    /**
     * Undocumented variable
     *
     * @var array
     */
    private $_events = array(
        'Triggered' => array(),
        'Skipped'   => array()
    );

    /**
     * Undocumented function
     */
    protected function __construct()
    {
        // Register Event Type CPT
        register_post_type('rl_event_type', array(
            'label'        => __('Event Type', REACTIVE_LOG_KEY),
            'labels'       => array(
                'name'          => __('Event Types', REACTIVE_LOG_KEY),
                'edit_item'     => __('Edit Event Type', REACTIVE_LOG_KEY),
                'singular_name' => __('Event Type', REACTIVE_LOG_KEY),
                'add_new_item'  => __('Add New Event Type', REACTIVE_LOG_KEY),
                'new_item'      => __('New Event Type', REACTIVE_LOG_KEY),
                'item_updated'  => __('Event Type Updated', REACTIVE_LOG_KEY)
            ),
            'description'  => __('Event type', REACTIVE_LOG_KEY),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'hierarchical' => false,
            'supports'     => array(
                'title', 'excerpt'
            ),
            'delete_with_user' => false,
            'capabilities' => array(
                'edit_post'         => 'administrator',
                'read_post'         => 'administrator',
                'delete_post'       => 'administrator',
                'delete_posts'      => 'administrator',
                'edit_posts'        => 'administrator',
                'edit_others_posts' => 'administrator',
                'publish_posts'     => 'administrator',
            )
        ));

        register_taxonomy('rl_event_type_category', 'rl_event_type', array(
            'hierarchical'      => true,
            'rewrite'           => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_menu'      => true,
            'show_in_rest'      => true,
            'capabilities'      => array(
                'manage_terms'  => 'administrator',
                'edit_terms'    => 'administrator',
                'delete_terms'  => 'administrator',
                'assign_terms'  => 'administrator'
            )
        ));

        register_taxonomy('rl_subscriber', 'rl_event_type', array(
            'hierarchical'      => false,
            'rewrite'           => true,
            'public'            => false,
            'show_ui'           => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'capabilities'      => array(
                'manage_terms'  => 'administrator',
                'edit_terms'    => 'administrator',
                'delete_terms'  => 'administrator',
                'assign_terms'  => 'administrator'
            ),
            'labels' => array (
                'name'          => 'Subscribers',
                'singular_name' => 'Subscriber',
                'separate_items_with_commas' => 'Separate User IDs with comma'
            )
        ));

        add_action('post_action_deactivate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === 'rl_event_type') {
                if (current_user_can('publish_post', $post->ID)) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'deactivate-post_' . $post->ID)) {
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_status' => 'draft'
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=reactivelog-types'));
                exit;
            }
        });

        add_action('post_action_activate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === 'rl_event_type') {
                if (current_user_can('publish_post', $post->ID)) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'activate-post_' . $post->ID)) {
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_status' => 'publish'
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=reactivelog-types'));
                exit;
            }
        });

        add_action('post_action_duplicate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === 'rl_event_type') {
                if (current_user_can('edit_posts')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'duplicate-post_' . $post->ID)) {
                        wp_insert_post(array(
                            'post_title'   => __('Duplicate ', REACTIVE_LOG_KEY) . $post->post_title,
                            'post_type'    => $post->post_type,
                            'post_content' => $post->post_content,
                            'post_excerpt' => $post->post_excerpt,
                            'post_status'  => 'draft'
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=reactivelog-types'));
                exit;
            }
        });

        add_action('post_action_subscribe', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === 'rl_event_type') {
                if (current_user_can('administrator')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'subscribe-post_' . $post->ID)) {
                        wp_set_post_terms(
                            $id,
                            get_current_user_id(),
                            'rl_subscriber',
                            true
                        );
                    }
                }

                wp_redirect(admin_url('admin.php?page=reactivelog-types'));
                exit;
            }
        });

        add_action('post_action_unsubscribe', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === 'rl_event_type') {
                if (current_user_can('administrator')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'unsubscribe-post_' . $post->ID)) {
                        $term = get_term_by(
                            'slug', get_current_user_id(), 'rl_subscriber'
                        );

                        if (is_a($term, 'WP_Term')) {
                            wp_remove_object_terms(
                                $id,
                                $term->term_id,
                                'rl_subscriber'
                            );
                        }
                    }
                }

                wp_redirect(admin_url('admin.php?page=reactivelog-types'));
                exit;
            }
        });

        add_filter('wp_insert_post_data', array($this, 'manageEventTypeContent'));
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     *
     * @return void
     */
    public function manageEventTypeContent($data)
    {
        if (isset($data['post_type']) && ($data['post_type'] === 'rl_event_type')) {
            $content = filter_input(INPUT_POST, 'event-type-content');

            if (empty($content)) {
                if (empty($data['post_content'])) {
                    $data['post_content'] = '{}';
                }
            } else {
                $data['post_content'] = $content;
            }
        }

        return $data;
    }

    /**
     * Undocumented function
     *
     * @param [type] $filters
     * @param integer $length
     * @param integer $offset
     *
     * @return array
     */
    public function getEventTypes($filters, $length = 10, $offset = 0)
    {
        if (is_multisite()) {
            // All event types are global and live in the main site
            switch_to_blog($this->getMainSiteId());
        }

        if (!empty($filters['category'])) {
            $filters['tax_query'] = array(
                array(
                    'taxonomy' => 'rl_event_type_category',
                    'terms'    => $filters['category']
                )
            );
            unset($filters['category']);
        }

        $types = get_posts(array_merge(array(
            'post_type'   => 'rl_event_type',
            'numberposts' => $length,
            'post_status' => 'any',
            'offset'      => $offset
        ), $filters));

        if (is_multisite()) {
            restore_current_blog();
        }

        // Preparing the list by checking permissions
        $response = [];

        foreach ($types as $type) {
            $actions = [];

            $json             = json_decode($type->post_content);
            $post_type_object = get_post_type_object($type->post_type);

            if (current_user_can('edit_post', $type->ID)) {
                $actions['edit'] = get_edit_post_link($type->ID);
                $actions['duplicate'] = wp_nonce_url(
                    admin_url(
                        sprintf(
                            $post_type_object->_edit_link . '&amp;action=duplicate',
                            $type->ID
                        )
                    ),
                    'duplicate-post_' . $type->ID
                );
            }

            if (current_user_can('delete_post', $type->ID)) {
                if ($type->post_status !== 'trash') {
                    $actions['trash'] = get_delete_post_link($type->ID);
                } else if ($type->post_status === 'trash') {
                    $actions['restore'] = wp_nonce_url(
                        admin_url(
                            sprintf(
                                $post_type_object->_edit_link . '&amp;action=untrash',
                                $type->ID
                            )
                        ),
                        'untrash-post_' . $type->ID
                    );
                    $actions['delete'] = get_delete_post_link($type->ID, '', true);
                }
            }

            if (current_user_can('publish_post', $type->ID)) {
                if ($type->post_status === 'publish') {
                    $actions['deactivate'] = wp_nonce_url(
                        admin_url(
                            sprintf(
                                $post_type_object->_edit_link . '&amp;action=deactivate',
                                $type->ID
                            )
                        ),
                        'deactivate-post_' . $type->ID
                    );
                } else {
                    $actions['activate'] = wp_nonce_url(
                        admin_url(
                            sprintf(
                                $post_type_object->_edit_link . '&amp;action=activate',
                                $type->ID
                            )
                        ),
                        'activate-post_' . $type->ID
                    );
                }
            }

            if (current_user_can('administrator')) {
                $terms = wp_get_object_terms(
                    $type->ID,
                    'rl_subscriber',
                    array('fields' => 'slugs')
                );
                if (in_array(get_current_user_id(), $terms)) {
                    $actions['unsubscribe'] = wp_nonce_url(
                        admin_url(
                            sprintf(
                                $post_type_object->_edit_link . '&amp;action=unsubscribe',
                                $type->ID
                            )
                        ),
                        'unsubscribe-post_' . $type->ID
                    );
                } else {
                    $actions['subscribe'] = wp_nonce_url(
                        admin_url(
                            sprintf(
                                $post_type_object->_edit_link . '&amp;action=subscribe',
                                $type->ID
                            )
                        ),
                        'subscribe-post_' . $type->ID
                    );
                }
            }

            // Check if any required version is specified
            if (isset($json->RequiredVersion)) {
                $required_version = $json->RequiredVersion;
            } else {
                $required_version = __('Any', REACTIVE_LOG_KEY);
            }

            // Prepare description
            if (trim($type->post_excerpt)) {
                $description = $type->post_excerpt;
            } else {
                $description = __('No description provided', REACTIVE_LOG_KEY);
            }

            $response[] = array(
                'id'               => $type->ID,
                'title'            => $type->post_title,
                'description'      => $description,
                'required_version' => $required_version,
                'actions'          => $actions,
                'status'           => $type->post_status
            );
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function getAllActiveEventTypes()
    {
        $response = [];

        if (is_multisite()) {
            // All event types are global and live in the main site
            switch_to_blog($this->getMainSiteId());
        }

        $types = get_posts(array(
            'post_type'   => 'rl_event_type',
            'numberposts' => -1,
            'post_status' => 'publish',
        ));

        if (is_multisite()) {
            restore_current_blog();
        }

        foreach($types as $type) {
            $event = $this->prepareEventType($type);

            if (!is_null($event)) {
                array_push($response, $event);
            }
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $type
     * @return void
     */
    public function prepareEventType($type)
    {
        $response = null;

        if (is_a($type, 'WP_Post')) {
            $json = json_decode($type->post_content);

            if (is_object($json)) {
                $event = $this->parseEvent($json->Event ?? null);

                if (!is_null($event)) {
                    $response = array(
                        'post_id'   => $type->ID,
                        'type'      => $event['type'],
                        'hook'      => $event['hook'],
                        'config'    => $json,
                        'event'     => $type,
                        'listeners' => array()
                    );

                    // Also parse all the listeners if defined
                    if (isset($json->Listener)) {
                        if(is_array($json->Listener)) {
                            $listeners = $json->Listener;
                        } else {
                            $listeners = [$json->Listener];
                        }
                    } else {
                        $listeners = array();
                    }

                    foreach($listeners as $listener) {
                        $event = $this->parseEvent($listener->Event ?? null);

                        if (!is_null($event)) {
                            array_push($response['listeners'], array(
                                'post_id' => $type->ID,
                                'type'    => $event['type'],
                                'hook'    => $event['hook'],
                                'config'  => $listener
                            ));
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $config
     * @param array $properties
     *
     * @return object
     */
    public function evaluateConfig($config, array $properties)
    {
        $manager = ConfigManager::getInstance();

        // Let's deep clone the $config
        $clone   = $this->cloneConfig($config);
        $context = $manager->getContext(array_merge(
            $properties, array('__config' => $clone, '__story')
        ));

        $response = $manager->hydrate($clone, $context);

        if (isset($response->Condition)) {
            if (!$manager->isApplicable($response->Condition, $context)) {
                $response = null; // Nope, let's do nothing more
            }
        }

        // Capture the history of the events execution
        if (is_null($response)) {
            array_push($this->_events['Skipped'], $config->Event);
        } else {
            array_push($this->_events['Triggered'], $config->Event);
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $config
     *
     * @return void
     */
    protected function cloneConfig($config)
    {
        $clone = is_array($config) ? [] : (object)[];

        foreach($config as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                if (is_array($clone)) {
                    $clone[$key] = $value;
                } else {
                    $clone->{$key} = $value;
                }
            } else {
                if (is_array($clone)) {
                    $clone[$key] = $this->cloneConfig($value);
                } else {
                    $clone->{$key} = $this->cloneConfig($value);
                }
            }
        }

        return $clone;
    }

    /**
     * Undocumented function
     *
     * @param string $event_str
     *
     * @return array|null
     *
     * @access protected
     */
    protected function parseEvent($event_str)
    {
        // Making sure that it is always string
        $event_str = is_string($event_str) ? $event_str : '';

        if (strpos($event_str, 'wp:::') === 0) {
            $event_str = substr($event_str, 5); // Remove the "wp:::" prefix
        }

        $details = explode(':', $event_str);

        // Verifying that the type of hook is valid. Can be only "action" or "filter"
        if (isset($details[0]) && in_array($details[0], self::HOOK_TYPES, true)) {
            $type = $details[0];
        }

        if (isset($details[1])) {
            $hook = $details[1];
        }

        return $type && $hook ? array('type' => $type, 'hook' => $hook) : null;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function setup()
    {
        if (is_multisite()) {
            // All event types are global and live in the main site
            switch_to_blog($this->getMainSiteId());

            restore_current_blog();
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getMainSiteId()
    {
        if (function_exists('get_main_site_id')) {
            $id = get_main_site_id();
        } elseif (is_multisite()) {
            $network = get_network();
            $id      = ($network ? $network->site_id : 0);
        } else {
            $id = get_current_blog_id();
        }

        return $id;
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
