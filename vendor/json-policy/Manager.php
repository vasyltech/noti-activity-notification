<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy;

use JsonPolicy\Core\Context,
    JsonPolicy\Parser\PolicyParser,
    JsonPolicy\Manager\MarkerManager,
    JsonPolicy\Parser\ExpressionParser,
    JsonPolicy\Manager\TypecastManager,
    JsonPolicy\Manager\ConditionManager;

/**
 * Main policy manager
 *
 * @version 0.0.1
 */
class Manager
{

    /**
     * Policy manager settings
     *
     * @var array
     *
     * @access private
     * @version 0.0.1
     */
    private $_settings = [];

    /**
     * Marker manager
     *
     * @var JsonPolicy\Manager\MarkerManager
     *
     * @access private
     * @version 0.0.1
     */
    private $_marker_manager = null;

    /**
     * Typecast manager
     *
     * @var JsonPolicy\Manager\TypecastManager
     *
     * @access private
     * @version 0.0.1
     */
    private $_typecast_manager = null;

    /**
     * Condition manager
     *
     * @var JsonPolicy\Manager\ConditionManager
     *
     * @access private
     * @version 0.0.1
     */
    private $_condition_manager = null;

    /**
     * Parsed policy tree
     *
     * @var object
     *
     * @access private
     * @version 0.0.1
     */
    private $_tree = null;

    /**
     * Context
     *
     * @var Context
     *
     * @access private
     * @version 0.0.1
     */
    private $_context = null;

    /**
     * Bootstrap constructor
     *
     * Initialize the JSON policy framework.
     *
     * @param array $settings
     *
     * @return void
     *
     * @access protected
     * @version 0.0.1
     */
    protected function __construct(array $settings)
    {
        $this->_settings = $settings;
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @return void
     */
    public function __get($prop)
    {
        return property_exists($this->_tree, $prop) ? $this->_tree->{$prop} : null;
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     *
     * @return boolean
     */
    public function __isset($prop)
    {
        return property_exists($this->_tree, $prop);
    }

    /**
     * Get policy manager settings
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public function getSetting($name, $as_iterable = true)
    {
        $setting = null;

        if ($as_iterable) {
            $setting = $this->_getSettingIterator($name);
        } else if (isset($this->_settings[$name])) {
            $setting = $this->_settings[$name];
        }

        return $setting;
    }

    /**
     * Get marker manager
     *
     * @return JsonPolicy\Manager\MarkerManager
     *
     * @access public
     * @version 0.0.1
     */
    public function getMarkerManager()
    {
        if (is_null($this->_marker_manager)) {
            $this->_marker_manager = new MarkerManager(
                $this->getSetting('custom_markers')
            );
        }

        return $this->_marker_manager;
    }

    /**
     * Get typecast manager
     *
     * @return JsonPolicy\Manager\TypecastManager
     *
     * @access public
     * @version 0.0.1
     */
    public function getTypecastManager()
    {
        if (is_null($this->_typecast_manager)) {
            $this->_typecast_manager = new TypecastManager(
                $this->getSetting('custom_types')
            );
        }

        return $this->_typecast_manager;
    }

    /**
     * Get condition manager
     *
     * @return JsonPolicy\Manager\ConditionManager
     *
     * @access public
     * @version 0.0.1
     */
    public function getConditionManager()
    {
        if (is_null($this->_condition_manager)) {
            $this->_condition_manager = new ConditionManager(
                $this->getSetting('custom_conditions')
            );
        }

        return $this->_condition_manager;
    }

    /**
     * Get context
     *
     * If $properties array is not empty, then create a new context, however, keep
     * the original untouched
     *
     * @param array $properties
     *
     * @return Context
     *
     * @access public
     * @version 0.0.1
     */
    public function getContext(array $properties = [])
    {
        if (!empty($properties)) {
            $context = new Context(array_merge(
                [ 'manager' => $this ],
                $this->getSetting('context'),
                $properties
            ));
        } else {
            $context = $this->_context;
        }

        return $context;
    }

    /**
     * Check if policy statement or param is applicable
     *
     * @param Context|null $context
     * @param object|null  $object
     *
     * @return boolean
     *
     * @access public
     * @version 0.0.1
     */
    public function isApplicable(Context $context = null, object $object = null)
    {
        $target  = is_null($object) ? $this->_tree : $object;
        $context = is_null($context) ? $this->_context : $context;

        $result = true;

        if (!empty($target->Condition) && is_object($target->Condition)) {
            $conditions = $target->Condition;

            foreach ($conditions as $i => &$group) {
                if ($i !== 'Operator') {
                    foreach ($group as $j => &$row) {
                        if ($j !== 'Operator') {
                            $left  = ExpressionParser::convertToValue(
                                $row['left'], $context
                            );
                            $right = ExpressionParser::convertToValue(
                                $row['right'], $context
                            );

                            $row = array(
                                // Left expression
                                'left'  => $left,
                                // Right expression
                                'right' => is_array($right) ? $right : [$right]
                            );
                        }
                    }
                }
            }

            $result = $this->getConditionManager()->evaluate($conditions);
        }

        return $result;
    }

    /**
     * Hydrate the JSON policy
     *
     * Basically replace all the markers with values and parse Conditions to be
     * evaluated in futher steps
     *
     * @param string        $policy
     * @param Context|array $context
     *
     * @return object|string|null
     */
    public function hydrate(string $policy, $context = null)
    {
        if (!is_a($context, 'JsonPolicy\Core\Context')){
            $context = new Context(array_merge(
                [ 'manager' => $this ],
                is_array($context) ? $context : []
            ));
        }

        return PolicyParser::parse($policy, $context);
    }

    /**
     * Initialize the policy manager
     *
     * @return void
     *
     * @access protected
     * @version 0.0.1
     */
    protected function initialize()
    {
        $this->_context = new Context(array_merge(
            [ 'manager' => $this ],
            $this->getSetting('context')
        ));

        // Parse the collection of policies
        $this->_tree = $this->hydrate(
            $this->getSetting('policy', false), $this->_context
        );
    }

    /**
     * Get setting's iterator
     *
     * The idea is that some settings (e.g. `repository` or `markers`) that are
     * passed to the Manager, contain iterable collection. In case, certain setting
     * is not explicitly defined or is not an iterable value, then return just empty
     * array
     *
     * @param string $name Setting name
     *
     * @return array|Traversable
     *
     * @access private
     * @version 0.0.1
     */
    private function _getSettingIterator($name)
    {
        $iterator = null;

        if (isset($this->_settings[$name])) {
            $setting = $this->_settings[$name];

            if (is_a($setting, 'Closure')) {
                $iterator = call_user_func($setting, $this);
            } else {
                $iterator = $setting;
            }
        }

        if (is_null($iterator) || !is_iterable($iterator)) {
            $iterator = [];
        }

        return $iterator;
    }

    /**
     * Bootstrap the framework
     *
     * @param array $settings Manager settings
     *
     * @return JsonPolicy\Manager
     *
     * @access public
     * @static
     * @version 0.0.1
     */
    public static function bootstrap(array $settings = [], bool $init = true): Manager
    {
        $instance = new self($settings);

        // Initialize the manager if there is policy provided
        if ($init) {
            $instance->initialize();
        }

        return $instance;
    }

}