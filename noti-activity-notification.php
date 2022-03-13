<?php

/**
 * Plugin Name: Noti - Activity Notification
 * Description: Totally free, infinitely configurable, and powerful website activity monitoring and alerting plugin for WordPress projects of any scale.
 * Version: 0.1.0
 * Author: Vasyl Martyniuk <vasyl@vasyltech.com>
 * Author URI: https://vasyltech.com
 * Text Domain: noti
 * Domain Path: /lang/
 *
 * -------
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'LICENSE', which is part of this source code package.
 *
 **/

use Noti\Core\OptionManager,
    Noti\Core\EventTypeManager,
    Noti\Core\Manager as CoreManager,
    Noti\Backend\Manager as BackendManager,
    Noti\Restful\Manager as RestfulManager;

/**
 * Main plugin's class
 *
 * @package Noti
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 0.0.1
 */
class NotiActivityNotification
{

    /**
     * Single instance of itself
     *
     * @var NotiActivityNotification
     *
     * @access private
     * @version 0.0.1
     */
    private static $_instance = null;

    /**
     * Initialize the plugin
     *
     * @return void
     *
     * @access protected
     * @version 0.0.1
     */
    protected function __construct()
    {
    }

    /**
     * Hook on WP core init
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public static function onInit()
    {
        if (is_admin()) {
            BackendManager::bootstrap();
        }

        // Initialize the core manager
        CoreManager::bootstrap();

        // Initialize the RESTful API manager
        RestfulManager::bootstrap();
    }

    /**
     * Initialize the plugin
     *
     * @return NotiActivityNotification
     *
     * @access public
     * @version 0.0.1
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Load the internationalization
            load_plugin_textdomain(NOTI_KEY, false, 'noti/lang');
        }

        return self::$_instance;
    }

    /**
     * Activation hook
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public static function activate()
    {
        global $wp_version;

        // Check the minimum required versions
        if (version_compare(PHP_VERSION, '7.0.0') === -1) {
            exit(__('PHP 7.0.0 or higher is required.', NOTI_KEY));
        } elseif (version_compare($wp_version, '4.7.0') === -1) {
            exit(__('WP 4.7.0 or higher is required.', NOTI_KEY));
        }
    }

    /**
     * Deactivate hook
     *
     * Remove all plugin's leftovers
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public static function uninstall()
    {
        global $wpdb;

        // Delete all related to the plugin DB tables
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}noti_eventmeta`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}noti_events`");
		$wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}noti_subscribers`");

        // Making sure that all necessary taxonoies and post types are registered
        // prior to deletion
        EventTypeManager::bootstrap();

        // Delete all the event types
        $types = get_posts(array(
            'post_type'   => EventTypeManager::EVENT_TYPE,
            'numberposts' => -1,
            'post_status' => 'any',
        ));

        foreach($types as $type) {
            wp_delete_post($type->ID, true);
        }

        // Deleting all categories
        $terms = get_terms(array(
            'taxonomy'   => EventTypeManager::EVENT_TYPE_CATEGORY,
            'hide_empty' => false,
            'fields'     =>'ids'
        ));

        foreach($terms as $id) {
            wp_delete_term($id, EventTypeManager::EVENT_TYPE_CATEGORY);
        }

        // Delete all the options associated with the plugin
        OptionManager::reset();

        // Deleting scheduled jobs
        if (wp_next_scheduled('noti_cleanup_log')) {
            wp_unschedule_hook('noti_cleanup_log');
        }

        if (!wp_next_scheduled('noti_send_notifications')) {
            wp_unschedule_hook('noti_send_notifications');
        }
    }

}

if (defined('ABSPATH')) {
    // Define some global constants
    define('NOTI_KEY', 'noti');
    define('NOTI_BASEDIR', __DIR__);
    define('NOTI_MEDIA', __DIR__ . '/media');
    define('NOTI_VERSION', '0.1.0');

    // Register autoloader
    require(__DIR__ . '/autoloader.php');
    Noti\Autoloader::register();

    // On WP core initialization hook
    add_action('init', 'NotiActivityNotification::onInit');

    //activation & deactivation hooks
    register_activation_hook(__FILE__, array('NotiActivityNotification', 'activate'));
    register_uninstall_hook(__FILE__, array('NotiActivityNotification', 'uninstall'));
}