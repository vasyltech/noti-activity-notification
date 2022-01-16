<?php

/**
 * Plugin Name: Reactive Log
 * Description: Track any activity and notify them in the near-to-realtime
 * Version: 0.0.1
 * Author: Vasyl Martyniuk <vasyl@vasyltech.com>
 * Author URI: https://vasyltech.com
 * Text Domain: reactive-log
 * Domain Path: /lang/
 *
 * -------
 * LICENSE: This file is subject to the terms and conditions defined in
 * file 'license.txt', which is part of Advanced Access Manager source package.
 *
 **/

use ReactiveLog\Core\Manager as CoreManager,
    ReactiveLog\Backend\Manager as BackendManager,
    ReactiveLog\Restful\Manager as RestfulManager,
    ReactiveLog\EventType\Manager as EventTypeManager;

/**
 * Main plugin's class
 *
 * @package ReactiveLog
 * @author Vasyl Martyniuk <vasyl@vasyltech.com>
 * @version 0.0.1
 */
class ReactiveLog
{

    /**
     * Single instance of itself
     *
     * @var ReactiveLog
     *
     * @access private
     * @version 0.0.1
     */
    private static $_instance = null;

    /**
     * Initialize the ReactiveLog Object
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

        // Initialize the Event Type manager
        EventTypeManager::bootstrap();

        // Initialize the core manager
        CoreManager::bootstrap();

        // Initialize the RESTful API manager
        RestfulManager::bootstrap();
    }

    /**
     * Initialize the ReactiveLog plugin
     *
     * @return ReactiveLog
     *
     * @access public
     * @version 0.0.1
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;

            // Load ReactiveLog internationalization
            load_plugin_textdomain(REACTIVE_LOG_KEY, false, 'reactive-log/lang');
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
            exit(__('PHP 7.0.0 or higher is required.', REACTIVE_LOG_KEY));
        } elseif (version_compare($wp_version, '4.7.0') === -1) {
            exit(__('WP 4.7.0 or higher is required.', REACTIVE_LOG_KEY));
        }

        // Register all default event types
        EventTypeManager::getInstance()->setup();
    }

    /**
     * Deactivate hook
     *
     * Remove all leftovers from ReactiveLog execution
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
    define('REACTIVE_LOG_KEY', 'reactive-log');
    define('REACTIVE_LOG_MEDIA', __DIR__ . '/media');

    // Register autoloader
    require(__DIR__ . '/autoloader.php');
    \ReactiveLog\Autoloader::register();

    // On WP core initialization hook
    add_action('init', 'ReactiveLog::onInit');

    //activation & deactivation hooks
    register_activation_hook(__FILE__, array('ReactiveLog', 'activate'));
    register_uninstall_hook(__FILE__, array('ReactiveLog', 'uninstall'));
}
