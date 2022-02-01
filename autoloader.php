<?php

/**
 * ======================================================================
 * LICENSE: This file is subject to the terms and conditions defined in *
 * file 'LICENSE', which is part of this source code package.           *
 * ======================================================================
 */

namespace Noti;

class Autoloader
{

    /**
     * Absolute path to the vendor directory
     */
    const BASEDIR = __DIR__ . '/vendor';

    /**
     * Manually mapped classes to their physical locations
     *
     * @var array
     *
     * @access protected
     * @static
     */
    protected static $classmap = array(
        'Noti\Vendor\Parsedown' => self::BASEDIR . '/parsedown/Parsedown.php',
        'Noti\Vendor\TemplateEngine\Manager' => self::BASEDIR . '/template-engine/Manager.php',
        'Psr\Http\Message\MessageInterface' => self::BASEDIR . '/psr-http-message/MessageInterface.php',
        'Psr\Http\Message\RequestInterface' => self::BASEDIR . '/psr-http-message/RequestInterface.php',
        'Psr\Http\Message\ResponseInterface' => self::BASEDIR . '/psr-http-message/ResponseInterface.php',
        'Psr\Http\Message\ServerRequestInterface' => self::BASEDIR . '/psr-http-message/ServerRequestInterface.php',
        'Psr\Http\Message\StreamInterface' => self::BASEDIR . '/psr-http-message/StreamInterface.php',
        'Psr\Http\Message\UploadedFileInterface' => self::BASEDIR . '/psr-http-message/UploadedFileInterface.php',
        'Psr\Http\Message\UriInterface' => self::BASEDIR . '/psr-http-message/UriInterface.php',
        'Vectorface\Whip\IpRange\IpRange' => self::BASEDIR . '/whip/IpRange/IpRange.php',
        'Vectorface\Whip\IpRange\IpWhitelist' => self::BASEDIR . '/whip/IpRange/IpWhitelist.php',
        'Vectorface\Whip\IpRange\Ipv4Range' => self::BASEDIR . '/whip/IpRange/Ipv4Range.php',
        'Vectorface\Whip\IpRange\Ipv6Range' => self::BASEDIR . '/whip/IpRange/Ipv6Range.php',
        'Vectorface\Whip\Request\Psr7RequestAdapter' => self::BASEDIR . '/whip/Request/Psr7RequestAdapter.php',
        'Vectorface\Whip\Request\RequestAdapter' => self::BASEDIR . '/whip/Request/RequestAdapter.php',
        'Vectorface\Whip\Request\SuperglobalRequestAdapter' => self::BASEDIR . '/whip/Request/SuperglobalRequestAdapter.php',
        'Vectorface\Whip\Whip' => self::BASEDIR . '/whip/Whip.php',
        'JsonPolicy\Manager' => self::BASEDIR . '/json-policy/Manager.php',
        'JsonPolicy\Core\Context' => self::BASEDIR . '/json-policy/Core/Context.php',
        'JsonPolicy\Core\Entity' => self::BASEDIR . '/json-policy/Core/Entity.php',
        'JsonPolicy\Manager\ConditionManager' => self::BASEDIR . '/json-policy/Manager/ConditionManager.php',
        'JsonPolicy\Manager\MarkerManager' => self::BASEDIR . '/json-policy/Manager/MarkerManager.php',
        'JsonPolicy\Manager\TypecastManager' => self::BASEDIR . '/json-policy/Manager/TypecastManager.php',
        'JsonPolicy\Parser\ConditionParser' => self::BASEDIR . '/json-policy/Parser/ConditionParser.php',
        'JsonPolicy\Parser\ExpressionParser' => self::BASEDIR . '/json-policy/Parser/ExpressionParser.php',
        'JsonPolicy\Parser\PolicyParser' => self::BASEDIR . '/json-policy/Parser/PolicyParser.php'
    );

    /**
     * Auto-loader for project
     *
     * @param string $class_name
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public static function load($class_name)
    {
        $chunks = explode('\\', $class_name);
        $prefix = array_shift($chunks);

        if (isset(self::$classmap[$class_name])) {
            $filename = self::$classmap[$class_name];
        } elseif ($prefix === 'Noti') {
            $base_path = __DIR__ . '/application';
            $filename  = $base_path . '/' . implode('/', $chunks) . '.php';
        }

        if (!empty($filename) && file_exists($filename)) {
            require($filename);
        }
    }

    /**
     * Register auto-loader
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public static function register()
    {
        spl_autoload_register('\Noti\Autoloader::load');
    }

}