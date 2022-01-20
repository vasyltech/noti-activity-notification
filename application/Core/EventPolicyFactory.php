<?php

namespace ReactiveLog\Core;

use Vectorface\Whip\Whip,
    JsonPolicy\Core\Context,
    JsonPolicy\Manager\MarkerManager,
    JsonPolicy\Manager as JsonPolicyManager;

class EventPolicyFactory
{
    /**
     *
     */
    const HOOK_TYPES = array (
        'action', 'filter'
    );

    /**
     * Undocumented variable
     *
     * @var array
     */
    private $_eventTypes = array();

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
        // First, let's fetch all the active policies and build a collection of
        // "shell" event types (not hydrated)
        $this->prepareEventTypeList();
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function getActiveEventTypes()
    {
        return array_filter($this->_eventTypes, function($type) {
            return $type->post->post_status === 'publish';
        });
    }

    /**
     * Undocumented function
     *
     * @param int $id
     *
     * @return object
     */
    public function getEventTypeById($id)
    {
        return array_pop(array_filter($this->_eventTypes, function($type) use ($id) {
            return $type->post->ID === $id;
        }));
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function prepareEventTypeList()
    {
        $this->_eventTypes = [];

        if (is_multisite()) {
            // All event types are global and live in the main site
            switch_to_blog($this->getMainSiteId());
        }

        $types = get_posts(array(
            'post_type'   => 'rl_event_type',
            'numberposts' => -1,
            'post_status' => array('publish', 'draft'),
        ));

        if (is_multisite()) {
            restore_current_blog();
        }

        foreach($types as $type) {
            $event = $this->prepareEventType($type);

            if (!is_null($event)) {
                array_push($this->_eventTypes, $event);
            }
        }
    }

     /**
     * Undocumented function
     *
     * @param WP_Post $type
     *
     * @return object
     */
    protected function prepareEventType($type)
    {
        $response = null;

        if (is_a($type, 'WP_Post')) {
            $json = json_decode($type->post_content);

            if (is_object($json)) {
                $event = $this->parseEvent($json->Event ?? null);

                if (!is_null($event)) {
                    $response = (object) array(
                        'post_id'   => $type->ID,
                        'type'      => $event['type'],
                        'hook'      => $event['hook'],
                        'policy'    => $json,
                        'post'      => $type,
                        'listeners' => array()
                    );

                    // Also parse all the listeners if defined
                    if (isset($json->Listener)) {
                        if(is_array($json->Listener)) {
                            $listeners = $json->Listener;
                        } else {
                            $listeners = [$json->Listener];
                        }
                    } else {
                        $listeners = array();
                    }

                    foreach($listeners as $listener) {
                        $event = $this->parseEvent($listener->Event ?? null);

                        if (!is_null($event)) {
                            array_push($response->listeners, (object) array(
                                'post_id' => $type->ID,
                                'type'    => $event['type'],
                                'hook'    => $event['hook'],
                                'policy'  => $listener
                            ));
                        }
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param string $event_str
     *
     * @return array|null
     *
     * @access protected
     */
    protected function parseEvent($event_str)
    {
        // Making sure that it is always string
        $event_str = is_string($event_str) ? $event_str : '';

        if (strpos($event_str, 'wp:::') === 0) {
            $event_str = substr($event_str, 5); // Remove the "wp:::" prefix
        }

        $details = explode(':', $event_str);

        // Verifying that the type of hook is valid. Can be only "action" or "filter"
        if (isset($details[0]) && in_array($details[0], self::HOOK_TYPES, true)) {
            $type = $details[0];
        }

        if (isset($details[1])) {
            $hook = $details[1];
        }

        return $type && $hook ? array('type' => $type, 'hook' => $hook) : null;
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function getMainSiteId()
    {
        if (function_exists('get_main_site_id')) {
            $id = get_main_site_id();
        } elseif (is_multisite()) {
            $network = get_network();
            $id      = ($network ? $network->site_id : 0);
        } else {
            $id = get_current_blog_id();
        }

        return $id;
    }

    /**
     * Undocumented function
     *
     * @return EventPolicyFactory
     */
    public static function getInstance()
    {
        return self::bootstrap();
    }

    /**
     * Undocumented function
     *
     * @return EventPolicyFactory
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Undocumented function
     *
     * @param string $policy
     * @param array  $context
     *
     * @return JsonPolicyManager
     */
    public function hydrate(string $policy = null, array $context = [])
    {
        $settings = array(
            'policy'         => $policy,
            'context'        => $context,
            'custom_markers' => array(
                'FUNC'              => __CLASS__ . '::getCallbackReturn',
                'CONST'             => __CLASS__ . '::getConstant',
                'USER'              => __CLASS__ . '::getUserValue',
                'USER_OPTION'       => __CLASS__ . '::getUserOptionValue',
                'USER_META'         => __CLASS__ . '::getUserMetaValue',
                'WP_OPTION'         => __CLASS__ . '::getWPOption',
                'WP_SITE'           => __CLASS__ . '::getSiteParam',
                'PHP_GLOBAL'        => __CLASS__ . '::getGlobalVariable',
                'WP_NETWORK_OPTION' => __CLASS__ . '::getNetworkOption',
                'LISTENER'          => __CLASS__ . '::getListenerData',
                'EVENT_META'        => __CLASS__ . '::getEventMetadata',
                'EVENT'             => __CLASS__ . '::getEventData',
                'EVENT_TYPE'        => __CLASS__ . '::getEventTypeData',
                'EVENT_TYPE_META'   => __CLASS__ . '::getEventTypeMetaValue'
            )
        );

        return JsonPolicyManager::bootstrap($settings, !is_null($policy));
    }

    /**
     * Undocumented function
     *
     * @param string $str
     * @param array $context
     * @return void
     */
    public function hydrateString(string $str, array $context = [])
    {
        $container = (object) [
            'str' => $str
        ];

        return $this->hydrate(json_encode($container), $context)->str;
    }

    /**
     * Undocumented function
     *
     * @param string  $func_exp
     * @param Context $context
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    public static function getCallbackReturn($func_exp, Context $context)
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
                    $value = MarkerManager::getValueByXPath($result, $cb['xpath']);
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
    public static function getConstant($prop)
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
     * @access public
     * @version 6.3.0
     */
    public static function getUserValue($prop)
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
     * @access public
     * @version 6.0.0
     */
    public static function getUserOptionValue($option_name)
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
     * @access public
     * @version 6.0.0
     */
    public static function getUserMetaValue($meta_key)
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
     * @access public
     * @version 6.3.0
     */
    public static function getWPOption($option)
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
     * @access public
     * @version 6.2.0
     */
    public static function getSiteParam($param)
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
     * @access public
     * @version 6.3.0
     */
    public static function getGlobalVariable($var)
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
     * @access public
     * @version 6.3.0
     */
    public static function getNetworkOption($option)
    {
        return get_site_option($option, null);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     *
     * @return void
     */
    public static function getListenerData($prop, Context $context)
    {
        $parts = explode('.', $prop, 2);
        $data  = ListenerManager::getListenerData($context->scope, $parts[0]);

        if (isset($parts[1])) {
            $value = MarkerManager::getValueByXPath($data, $parts[1]);
        } else {
            $value = $data;
        }

        return $value;
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     *
     * @return void
     */
    public static function getEventMetadata($prop, Context $context)
    {
        return MarkerManager::getValueByXPath($context, 'metadata.' . $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     *
     * @return void
     */
    public static function getEventData($prop, Context $context)
    {
        return MarkerManager::getValueByXPath($context, 'event.' . $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     *
     * @return void
     */
    public static function getEventTypeData($prop, Context $context)
    {
        return MarkerManager::getValueByXPath($context, 'eventType.' . $prop);
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @param Context $context
     *
     * @return void
     */
    public static function getEventTypeMetaValue($prop, Context $context)
    {
        $data = get_post_meta(
            MarkerManager::getValueByXPath($context, 'eventType.ID'),
            $prop
        );

        return is_array($data) && count($data) === 1 ? array_pop($data) : $data;
    }

    /**
     * Undocumented function
     *
     * @param [type] $func_exp
     * @param Context $context
     *
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
                    $context->getMarkerValue($parts[0], $parts[1] ?? null)
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

}