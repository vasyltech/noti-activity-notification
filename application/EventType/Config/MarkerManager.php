<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace ReactiveLog\EventType\Config;

use Vectorface\Whip\Whip,
    ReactiveLog\Core\ListenerManager;

/**
 * Marker manager
 *
 * @version 0.0.1
 */
class MarkerManager
{

    /**
     * Literal map token's type to the executable method that returns actual value
     *
     * @var array
     *
     * @access private
     * @version 0.0.2
     */
    private static $_map = array(
        'ARGS'              => __CLASS__ . '::getContextArgValue',
        'DATETIME'          => __CLASS__ . '::getDatetime',
        'ENV'               => __CLASS__ . '::getEnvVar',
        'HTTP_COOKIE'       => __CLASS__ . '::getHttpCookie',
        'HTTP_GET'          => __CLASS__ . '::getHttpGet',
        'HTTP_POST'         => __CLASS__ . '::getHttpPost',
        'HTTP_REQUEST'      => __CLASS__ . '::getHttpRequest',
        'FUNC'              => __CLASS__ . '::getCallbackReturn',
        'CONST'             => __CLASS__ . '::getConstant',
        'USER'              => __CLASS__ . '::getUserValue',
        'USER_OPTION'       => __CLASS__ . '::getUserOptionValue',
        'USER_META'         => __CLASS__ . '::getUserMetaValue',
        'WP_OPTION'         => __CLASS__ . '::getWPOption',
        'WP_SITE'           => __CLASS__ . '::getSiteParam',
        'PHP_GLOBAL'        => __CLASS__ . '::getGlobalVariable',
        'WP_NETWORK_OPTION' => __CLASS__ . '::getNetworkOption',
        'PLUGIN'            => __CLASS__ . '::getPlugin',
        'META'              => __CLASS__ . '::getConfigMetadata',
        'METADATA'          => __CLASS__ . '::getConfigMetadata',
        'LISTENER'          => __CLASS__ . '::getListenerData',
    );

    /**
     * Get value from provided source and path
     *
     * @param string  $source
     * @param string  $xpath
     * @param Context $context
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public static function getValue($source, $xpath, Context $context)
    {
       if (isset(self::$_map[$source])) {
            $value = call_user_func(self::$_map[$source], $xpath, $context);
        } else {
            $value = self::getValueByXPath($context, $xpath);
        }

        return $value;
    }

    /**
     * Get value from the context args
     *
     * @param string  $prop
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function getContextArgValue($prop, Context $context)
    {
        return self::getValueByXPath($context, 'args.' . $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     * @return void
     */
    protected static function getConfigMetadata($prop, Context $context)
    {
        return self::getValueByXPath($context, '__config.Metadata.' . $prop);
    }

    /**
     * Get current datetime
     *
     * @param string $format
     *
     * @return string
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function getDatetime($format)
    {
        return (new \DateTime('now', new \DateTimeZone('UTC')))->format($format);
    }

    /**
     * Get environment value
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function getEnvVar($prop)
    {
        return getenv($prop);
    }

    /**
     * Get value from $_COOKIE super-global
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.2
     */
    protected static function getHttpCookie($prop)
    {
        return self::getValueByXPath($_COOKIE, $prop);
    }

    /**
     * Get value from $_GET super-global
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.2
     */
    protected static function getHttpGet($prop)
    {
        return self::getValueByXPath($_GET, $prop);
    }

    /**
     * Get value from $_POST super-global
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.2
     */
    protected static function getHttpPost($prop)
    {
        return self::getValueByXPath($_POST, $prop);
    }

    /**
     * Get value from $_REQUEST super-global
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.2
     */
    protected static function getHttpRequest($prop)
    {
        return self::getValueByXPath($_REQUEST, $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $func
     * @return void
     */
    protected static function getCallbackReturn($func_exp, Context $context)
    {
        $value = null;
        $cb    = self::_parseFunction($func_exp, $context);

        if (!is_null($cb)) {
            $func = apply_filters(
                'noti_func_source', $cb['func'], $cb['args'], $context
            );

            if (is_callable($func) || function_exists($func)) {
                $result = call_user_func_array($func, $cb['args']);

                if (!empty($cb['xpath'])) {
                    $value = self::getValueByXPath($result, $cb['xpath']);
                } else {
                    $value = $result;
                }
            }
        }

        return $value;
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @return void
     */
    protected static function getConstant($prop)
    {
        return defined($prop) ? constant($prop) : null;
    }

    /**
     * Get USER's value
     *
     * @param string $prop
     *
     * @return mixed
     *
     * @since 6.3.0 Fixed bug that caused "Fatal error: Allowed memory size of XXX
     *              bytes exhausted"
     * @since 6.0.0 Initial implementation of the method
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getUserValue($prop)
    {
        static $whip = null;

        $user = wp_get_current_user();

        switch (strtolower($prop)) {
            case 'ip':
            case 'ipaddress':
                if (is_null($whip)) {
                    $whip = new Whip();
                }

                $value = $whip->getValidIpAddress();
                break;

            case 'authenticated':
            case 'isauthenticated':
                $value = is_user_logged_in();
                break;

            case 'capabilities':
            case 'caps':
                $allcaps = is_a($user, 'WP_User') ? (array)$user->allcaps : array();

                foreach ($allcaps as $cap => $effect) {
                    if (!empty($effect)) {
                        $value[] = $cap;
                    }
                }
                break;

            default:
                $value = (is_a($user, 'WP_User') ? $user->{$prop} : null);
                break;
        }

        return $value;
    }

    /**
     * Get user option value(s)
     *
     * @param string $option_name
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getUserOptionValue($option_name)
    {
        $value = null;
        $id    = get_current_user_id();

        if (!empty($id)) { // Only authenticated users have some sort of meta
            $value = get_user_option($option_name, $id);
        }

        return $value;
    }

    /**
     * Get user meta value(s)
     *
     * @param string $meta_key
     *
     * @return void
     *
     * @access protected
     * @version 6.0.0
     */
    protected static function getUserMetaValue($meta_key)
    {
        $value = null;
        $id    = get_current_user_id();

        if (!empty($id)) { // Only authenticated users have some sort of meta
            $meta = get_user_meta($id, $meta_key);

            // If $meta has only one value in the array, then extract it, otherwise
            // return the array of values
            if (count($meta) === 1) {
                $value = array_shift($meta);
            } else {
                $value = array_values($meta);
            }
        }

        return $value;
    }

    /**
     * Get database option
     *
     * @param string $option
     *
     * @return mixed
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getWPOption($option)
    {
        if (is_multisite()) {
            $result = get_blog_option(get_current_blog_id(), $option);
        } else {
            $result = get_option($option);
        }

        return $result;
    }

    /**
     * Get current blog details
     *
     * @param string $param
     *
     * @return mixed
     *
     * @access protected
     * @version 6.2.0
     */
    protected static function getSiteParam($param)
    {
        $result = null;

        if (is_multisite()) {
            $result = get_blog_details()->{$param};
        } elseif ($param === 'blog_id') {
            $result = get_current_blog_id();
        }

        return $result;
    }

    /**
     * Get global variable's value
     *
     * @param string $var
     *
     * @return mixed
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getGlobalVariable($var)
    {
        return (isset($GLOBALS[$var]) ? $GLOBALS[$var] : null);
    }

    /**
     * Get network option
     *
     * @param string $option
     *
     * @return mixed
     *
     * @access protected
     * @version 6.3.0
     */
    protected static function getNetworkOption($option)
    {
        return get_site_option($option, null);
    }

    /**
     * Get plugin details
     *
     * @param string  $prop
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function getPlugin($prop, Context $context)
    {
        $data = get_plugin_data(WP_PLUGIN_DIR . '/' . $context->args[0]);

        return self::getValueByXPath($data, $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     * @return void
     */
    protected static function getListenerData($prop, Context $context)
    {
        $parts = explode('.', $prop, 2);
        $data  = ListenerManager::getListenerData($context->scope, $parts[0]);

        return isset($parts[1]) ? self::getValueByXPath($data, $parts[1]) : $data;
    }

    /**
     * Undocumented function
     *
     * @param [type] $func_exp
     * @param Context $context
     * @return void
     */
    private static function _parseFunction($func_exp, Context $context)
    {
        $response = null;
        $regex    = '/^([a-z_\x80-\xff][a-z\d_\x80-\xff]*)\(([^)]*)\)(.*)$/i';

        if (preg_match($regex, $func_exp, $match)) {
            // The second part is the collection of arguments that we pass to
            // the function
            $markers = array_map('trim', explode(',', $match[2]));
            $args    = [];

            foreach($markers as $marker) {
                $parts = explode('.', $marker, 2);

                array_push(
                    $args,
                    self::getValue($parts[0], $parts[1] ?? null, $context)
                );
            }

            $response = array(
                'func'  => trim($match[1]),
                'args'  => $args,
                'xpath' => trim($match[3])
            );
        }

        return $response;
    }

    /**
     * Get value by xpath
     *
     * This method supports multiple different path
     *
     * @param mixed  $obj
     * @param string $xpath
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public static function getValueByXPath($obj, $xpath)
    {
        $value = $obj;
        $path  = trim(
            str_replace(
                array('["', '[', '"]', ']', '..'), '.', $xpath
            ),
            ' .' // white space is important!
        );

        foreach(explode('.', $path) as $l) {
            if (is_object($value)) {
                if (isset($value, $l)) {
                    $value = $value->{$l};
                } else {
                    $value = null;
                    break;
                }
            } else if (is_array($value)) {
                if (array_key_exists($l, $value)) {
                    $value = $value[$l];
                } else {
                    $value = null;
                    break;
                }
            }
        }

        return $value;
    }

}