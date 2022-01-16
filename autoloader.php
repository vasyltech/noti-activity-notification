<?php

namespace ReactiveLog;

class Autoloader
{

    /**
     *
     */
    const BASEDIR = __DIR__ . '/vendor';

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected static $classmap = array(
        'ReactiveLog\Vendor\Parsedown' => self::BASEDIR . '/parsedown/Parsedown.php',
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
    );

    /**
     * Auto-loader for project ReactiveLog
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
        } elseif ($prefix === 'ReactiveLog') {
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
        spl_autoload_register('\ReactiveLog\Autoloader::load');
    }
}
