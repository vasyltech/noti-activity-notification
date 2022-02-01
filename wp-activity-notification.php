<?php

/**
 * Plugin Name: WP Activity Notification
 * Description: Track any activity and notify them in the near-to-realtime
 * Version: 0.0.1
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

use Noti\Core\Manager as CoreManager,
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
    }

}

if (defined('ABSPATH')) {
    // Define some global constants
    define('NOTI_KEY', 'noti');
    define('NOTI_BASEDIR', __DIR__);
    define('NOTI_MEDIA', __DIR__ . '/media');

    // Register autoloader
    require(__DIR__ . '/autoloader.php');
    Noti\Autoloader::register();

    // On WP core initialization hook
    add_action('init', 'NotiActivityNotification::onInit');

    //activation & deactivation hooks
    register_activation_hook(__FILE__, array('NotiActivityNotification', 'activate'));
    register_uninstall_hook(__FILE__, array('NotiActivityNotification', 'uninstall'));
}