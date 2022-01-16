<?php

namespace ReactiveLog\Backend;

class Manager
{

    private static $_instance = null;

    private $_pages = array(
        'reactivelog',
        'reactivelog-subscriber',
        'reactivelog-types'
    );

    protected function __construct()
    {
        add_action('admin_menu', function () {
            global $submenu;

            // add_menu_page(
            //     __('Events', REACTIVE_LOG_KEY),
            //     __('Events', REACTIVE_LOG_KEY),
            //     'administrator',
            //     'reactivelog',
            //     array($this, 'renderWelcomePage')
            // );

            add_menu_page(
                __('Events', REACTIVE_LOG_KEY),
                __('Events', REACTIVE_LOG_KEY),
                'administrator',
                'reactivelog',
                array($this, 'renderLogPage')
            );

            add_submenu_page(
                'reactivelog',
                __('Event Types', REACTIVE_LOG_KEY),
                __('Event Types', REACTIVE_LOG_KEY),
                'administrator',
                'reactivelog-types',
                array($this, 'renderEventTypesPage')
            );

            array_push(
                $submenu['reactivelog'],
                array( __( 'Categories' ), 'administrator', 'edit-tags.php?taxonomy=rl_event_type_category' )
            );

            add_submenu_page(
                'reactivelog',
                __('Settings', REACTIVE_LOG_KEY),
                __('Settings', REACTIVE_LOG_KEY),
                'administrator',
                'reactivelog-settings',
                array($this, 'renderSettingsPage')
            );
        });

        add_action('admin_print_styles', function() {
            if (in_array(filter_input(INPUT_GET, 'page'), $this->_pages)) {
                echo '<style>';
                echo file_get_contents(REACTIVE_LOG_MEDIA . '/styles.css');
                echo '</style>';
            }
        });

        add_filter('allowed_options', function($options) {
            return array_merge($options, array(
                'reactivelog-settings' => array(
                    'reactivelog-keep-logs',
                    'reactivelog-cleanup-type',
                    'reactivelog-notifications'
                )
            ));
        });

        add_action('admin_print_footer_scripts', function() {
            if (in_array(filter_input(INPUT_GET, 'page'), $this->_pages)) {
                $locals = array(
                    'apiEntpoint' => esc_url_raw(rest_url('reactivelog/v1')),
                    'apiNonce' => wp_create_nonce('wp_rest')
                );

                echo '<script type="text/javascript">';
                echo 'var ReactiveLogLocals = ' . wp_json_encode($locals) . "\n";
                echo file_get_contents(REACTIVE_LOG_MEDIA . '/dt.js') . "\n";
                echo file_get_contents(REACTIVE_LOG_MEDIA . '/reactivelog.js');
                echo '</script>';
            }
        });

        //register custom access control metabox
        add_action('add_meta_boxes', array($this, 'registerMetaboxes'));

        add_action("in_admin_header", function() {
            global $post;

            if (is_a($post, 'WP_Post') && ($post->post_type === 'rl_event_type')) {
                remove_meta_box('submitdiv', 'rl_event_type', 'side');
            }

        }, 999);

        if (
            filter_input(INPUT_GET, 'post_type') === 'rl_event_type'
            && filter_input(INPUT_GET, 'trashed', FILTER_VALIDATE_INT) === 1
        ) {
            wp_redirect(admin_url('admin.php?page=reactivelog-types')); exit;
        }
    }

    public function renderWelcomePage()
    {
        require __DIR__ . '/Templates/welcome.php';
    }

    public function renderLogPage()
    {
        require __DIR__ . '/Templates/log.php';
    }

    public function renderEventTypesPage()
    {
        require __DIR__ . '/Templates/event.php';
    }

    public function renderCategoryPage()
    {
        global $taxnow;

       // $taxnow = 'rl_event_type_category';

        require ABSPATH . 'wp-admin/edit-tags.php';
    }

    public function renderSettingsPage()
    {
        require __DIR__ . '/Templates/settings.php';
    }

    public function registerMetaboxes()
    {
        global $post;

        if (is_a($post, 'WP_Post') && ($post->post_type === 'rl_event_type')) {
            add_meta_box(
                'raw-event-configuration',
                __('Raw Event Configuration', REACTIVE_LOG_KEY),
                function() {
                    require __DIR__ . '/Templates/event-editor.php';
                },
                null,
                'normal',
                'low'
            );

            add_meta_box(
                'event-type-publisher',
                __('Manage Status', REACTIVE_LOG_KEY),
                function() {
                    require __DIR__ . '/Templates/manage-status-metabox.php';
                },
                null,
                'side',
                'high'
            );
        }
    }

    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
}
