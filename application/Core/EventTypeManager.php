<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Core;

class EventTypeManager
{

    /**
     *
     */
    const EVENT_TYPE = 'noti_event_type';

    /**
     *
     */
    const EVENT_TYPE_CATEGORY = 'noti_event_type_cat';

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
        // Register Event Type CPT
        register_post_type(self::EVENT_TYPE, array(
            'label'        => __('Event Type', NOTI_KEY),
            'labels'       => array(
                'name'          => __('Event Types', NOTI_KEY),
                'edit_item'     => __('Edit Event Type', NOTI_KEY),
                'singular_name' => __('Event Type', NOTI_KEY),
                'add_new_item'  => __('Add New Event Type', NOTI_KEY),
                'new_item'      => __('New Event Type', NOTI_KEY),
                'item_updated'  => __('Event Type Updated', NOTI_KEY)
            ),
            'description'  => __('Event type', NOTI_KEY),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
            'hierarchical' => false,
            'supports'     => array('title', 'excerpt'),
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

        register_taxonomy(self::EVENT_TYPE_CATEGORY, self::EVENT_TYPE, array(
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

        add_action('post_action_deactivate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === self::EVENT_TYPE) {
                if (current_user_can('publish_post', $post->ID)) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'deactivate-post_' . $post->ID)) {
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_status' => 'draft'
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=noti-types'));
                exit;
            }
        });

        add_action('post_action_activate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === self::EVENT_TYPE) {
                if (current_user_can('publish_post', $post->ID)) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'activate-post_' . $post->ID)) {
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_status' => 'publish'
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=noti-types'));
                exit;
            }
        });

        add_action('post_action_duplicate', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === self::EVENT_TYPE) {
                $redirect = admin_url('admin.php?page=noti-types');

                if (current_user_can('edit_posts')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'duplicate-post_' . $post->ID)) {
                        $post_id = wp_insert_post(array(
                            'post_title'     => __('Duplicate ', NOTI_KEY) . $post->post_title,
                            'post_type'      => $post->post_type,
                            'post_content'   => $post->post_content,
                            'post_excerpt'   => $post->post_excerpt,
                            'post_status'    => 'draft',
                            'comment_status' => 'closed',
                            'ping_status'    => 'closed'
                        ));

                        if (!is_wp_error($post_id)) {
                            $redirect = get_edit_post_link($post_id, 'jump');
                        }
                    }
                }

                wp_redirect($redirect);
                exit;
            }
        });

        add_action('post_action_subscribe', function ($id) {
            // Current, we allow to ONLY subscribe to event types in other sites
            Helper::switchToMainSite();
            $post = get_post($id);
            Helper::restoreCurrentSite();

            if (is_a($post, 'WP_Post') && $post->post_type === self::EVENT_TYPE) {
                if (current_user_can('administrator')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'subscribe-post_' . $post->ID)) {
                        Repository::updateSubscription(array(
                            'site_id'       => get_current_blog_id(),
                            'post_id'       => $post->ID,
                            'user_id'       => get_current_user_id(),
                            'is_subscribed' => 1
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=noti-types'));
                exit;
            }
        });

        add_action('post_action_unsubscribe', function ($id) {
            $post = get_post($id);

            if (is_a($post, 'WP_Post') && $post->post_type === self::EVENT_TYPE) {
                if (current_user_can('administrator')) {
                    $nonce = filter_input(INPUT_GET, '_wpnonce');

                    if (wp_verify_nonce($nonce, 'unsubscribe-post_' . $post->ID)) {
                        Repository::updateSubscription(array(
                            'site_id'       => get_current_blog_id(),
                            'post_id'       => $post->ID,
                            'user_id'       => get_current_user_id(),
                            'is_subscribed' => 0
                        ));
                    }
                }

                wp_redirect(admin_url('admin.php?page=noti-types'));
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
        if (isset($data['post_type']) && ($data['post_type'] === self::EVENT_TYPE)) {
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
        Helper::switchToMainSite();

        if (!empty($filters['category'])) {
            $filters['tax_query'] = array(
                array(
                    'taxonomy' => self::EVENT_TYPE_CATEGORY,
                    'terms'    => $filters['category']
                )
            );
            unset($filters['category']);
        }

        $types = get_posts(array_merge(array(
            'post_type'   => self::EVENT_TYPE,
            'numberposts' => $length,
            'post_status' => 'any',
            'offset'      => $offset
        ), $filters));

        Helper::restoreCurrentSite();

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
                $status = Repository::getUserSubscriptionStatus(
                    get_current_blog_id(), $type->ID, get_current_user_id()
                );

                if ($status === 1) {
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
                $required_version = __('Any', NOTI_KEY);
            }

            // Prepare description
            if (trim($type->post_excerpt)) {
                $description = $type->post_excerpt;
            } else {
                $description = __('No description provided', NOTI_KEY);
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
     * @param [type] $type
     * @param boolean $updateExisting
     * @return void
     */
    public function insertType($type, $updateExisting = true)
    {
        $existing = Repository::getPostTypeByGuid($type->guid);

        if (empty($existing->ID)) {
            $post_id = wp_insert_post(array(
                'post_type'      => self::EVENT_TYPE,
                'post_title'     => $type->title,
                'post_excerpt'   => $type->excerpt,
                'post_status'    => $type->status === 'active' ? 'publish' : 'draft',
                'post_content'   => json_encode($type->policy),
                'comment_status' => 'closed',
                'ping_status'    => 'closed'
            ));

            if (!is_wp_error($post_id)) {
                if (isset($type->category)) {
                    // Adding newly added post to event type category
                    $term_id = term_exists(
                        $type->category, self::EVENT_TYPE_CATEGORY
                    );

                    if (!$term_id) {
                        $term = wp_insert_term(
                            $type->category, self::EVENT_TYPE_CATEGORY
                        );

                        $term_id = !is_wp_error($term) ? $term['term_id'] : null;
                    }

                    if ($term_id) {
                        wp_set_post_terms(
                            $post_id,
                            $term_id,
                            self::EVENT_TYPE_CATEGORY,
                            true
                        );
                    }
                }

                // Also insert GUID
                add_post_meta($post_id, 'guid', $type->guid, true);
            }
        } else if ($updateExisting) {
            wp_update_post(array(
                'ID' => $existing->ID,
                'post_title'     => $type->title,
                'post_excerpt'   => $type->excerpt,
                'post_content'   => json_encode($type->policy),
            ));
        }
    }

    /**
     * Undocumented function
     *
     * @return EventTypeManager
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
     * @return EventTypeManager
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::bootstrap();
    }

}