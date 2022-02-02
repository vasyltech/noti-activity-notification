<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Manager;

use JsonPolicy\Core\Context;

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
    private $_map = array(
        'ARGS'         => __CLASS__ . '::getContextArgValue',
        'DATETIME'     => __CLASS__ . '::getDatetime',
        'ENV'          => __CLASS__ . '::getEnvVar',
        'HTTP_COOKIE'  => __CLASS__ . '::getHttpCookie',
        'HTTP_GET'     => __CLASS__ . '::getHttpGet',
        'HTTP_POST'    => __CLASS__ . '::getHttpPost',
        'HTTP_REQUEST' => __CLASS__ . '::getHttpRequest',
        'CONTEXT'      => __CLASS__ . '::getContextValue'
    );

    /**
     * Construct the marker parser
     *
     * @param array $map Collection of additional markers
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public function __construct(array $map = [])
    {
        $this->_map = array_merge($this->_map, $map);
    }

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
    public function getValue($source, $xpath, Context $context)
    {
        if (isset($this->_map[$source])) {
            $value = call_user_func($this->_map[$source], $xpath, $context);
        } else {
            $value = self::getValueByXPath($context, "{$source}.{$xpath}");
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
     * Get value from the context
     *
     * @param string  $prop
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function getContextValue($prop, Context $context)
    {
        return self::getValueByXPath($context, $prop);
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
                if (isset($value->{$l})) {
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