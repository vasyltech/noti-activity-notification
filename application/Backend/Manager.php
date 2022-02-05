<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti\Backend;

use Noti\Core\OptionManager;

class Manager
{

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
    private $_pages = array(
        'noti',
        'noti-types',
        'noti-settings'
    );

    /**
     * Undocumented function
     */
    protected function __construct()
    {
        add_action('admin_menu', function () {
            global $submenu;

            $need_welcome = OptionManager::getOption('noti-welcome', true);

            if ($need_welcome) {
                add_menu_page(
                    __('Activity Log', NOTI_KEY),
                    __('Activity Log', NOTI_KEY),
                    'administrator',
                    'noti',
                    array($this, 'renderWelcomePage'),
                    file_get_contents(NOTI_MEDIA . '/active-menu.data')
                );
            } else {
                add_menu_page(
                    __('Activity Log', NOTI_KEY),
                    __('Activity Log', NOTI_KEY),
                    'administrator',
                    'noti',
                    array($this, 'renderLogPage'),
                    file_get_contents(NOTI_MEDIA . '/active-menu.data')
                );

                add_submenu_page(
                    'noti',
                    __('Event Types', NOTI_KEY),
                    __('Event Types', NOTI_KEY),
                    'administrator',
                    'noti-types',
                    array($this, 'renderEventTypesPage')
                );

                array_push(
                    $submenu['noti'],
                    array(
                        __( 'Categories' ),
                        'administrator',
                        'edit-tags.php?taxonomy=noti_event_type_cat'
                    )
                );

                add_submenu_page(
                    'noti',
                    __('Settings', NOTI_KEY),
                    __('Settings', NOTI_KEY),
                    'administrator',
                    'noti-settings',
                    array($this, 'renderSettingsPage')
                );
            }
        });

        add_filter('parent_file', function($parent_file) {
            global $post;

            if (is_a($post, 'WP_Post') && ($post->post_type === 'noti_event_type')) {
                $parent_file = 'noti';
            } elseif (
                $parent_file === 'edit.php'
                && filter_input(INPUT_GET, 'taxonomy') === 'noti_event_type_cat'
            ) {
                $parent_file = 'noti';
            }

            return $parent_file;
        });

        add_action('admin_print_styles', function() {
            if (in_array(filter_input(INPUT_GET, 'page'), $this->_pages)) {
                echo '<style>';
                echo file_get_contents(NOTI_MEDIA . '/styles.css');
                echo '</style>';
            }
        });

        add_filter('allowed_options', function($options) {
            return array_merge($options, array(
                'noti-settings' => array(
                    'noti-keep-logs',
                    'noti-cleanup-type',
                    'noti-notifications',
                    'noti-email-notification-tmpl'
                )
            ));
        });

        add_action('admin_print_footer_scripts', function() {
            if (in_array(filter_input(INPUT_GET, 'page'), $this->_pages)) {
                $locals = array(
                    'apiEntpoint'    => esc_url_raw(rest_url('noti/v1')),
                    'apiNonce'       => wp_create_nonce('wp_rest')
                );

                echo '<script type="text/javascript">';
                echo 'var NotiLocals = ' . wp_json_encode($locals) . "\n";
                echo file_get_contents(NOTI_MEDIA . '/dt.js') . "\n";
                echo file_get_contents(NOTI_MEDIA . '/noti.js');
                echo '</script>';
            }
        });

        add_action( 'admin_enqueue_scripts', function() {
            global $post;

            $field = null;

            if (filter_input(INPUT_GET, 'page') === 'noti-settings') {
                $field = 'noti-notifications';
            } elseif (
                is_a($post, 'WP_Post')
                && ($post->post_type === 'noti_event_type')
            ) {
                $field = 'event-type-content';
            }

            if (!is_null($field)) {
                // Enqueue code editor and settings for manipulating HTML.
                $settings = wp_enqueue_code_editor(
                    array('type' => 'application/json')
                );

                // Bail if user disabled CodeMirror.
                if ( false === $settings ) {
                    return;
                }

                wp_add_inline_script(
                    'code-editor',
                    sprintf(
                        'jQuery(() => wp.codeEditor.initialize("%s", %s));',
                        $field,
                        wp_json_encode($settings)
                    )
                );
            }
        } );

        //register custom access control metabox
        add_action('add_meta_boxes', array($this, 'registerMetaboxes'));

        add_action("in_admin_header", function() {
            global $post;

            if (is_a($post, 'WP_Post') && ($post->post_type === 'noti_event_type')) {
                remove_meta_box('submitdiv', 'noti_event_type', 'side');
            }

        }, 999);

        if (
            filter_input(INPUT_GET, 'post_type') === 'noti_event_type'
            && filter_input(INPUT_GET, 'trashed', FILTER_VALIDATE_INT) === 1
        ) {
            wp_redirect(admin_url('admin.php?page=noti-types')); exit;
        }
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderWelcomePage()
    {
        require __DIR__ . '/Templates/welcome.php';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderLogPage()
    {
        require __DIR__ . '/Templates/log.php';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderEventTypesPage()
    {
        require __DIR__ . '/Templates/event.php';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderCategoryPage()
    {
        require ABSPATH . 'wp-admin/edit-tags.php';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function renderSettingsPage()
    {
        require __DIR__ . '/Templates/settings.php';
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function registerMetaboxes()
    {
        global $post;

        if (is_a($post, 'WP_Post') && ($post->post_type === 'noti_event_type')) {
            add_meta_box(
                'raw-event-configuration',
                __('Raw Event Configuration', NOTI_KEY),
                function() {
                    require __DIR__ . '/Templates/event-editor.php';
                },
                null,
                'normal',
                'low'
            );

            add_meta_box(
                'event-type-publisher',
                __('Manage Status', NOTI_KEY),
                function() {
                    require __DIR__ . '/Templates/manage-status-metabox.php';
                },
                null,
                'side',
                'high'
            );
        }
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