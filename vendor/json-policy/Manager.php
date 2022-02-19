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
     * Undocumented variable
     *
     * @var boolean
     */
    private $_isHydrated = false;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $_policy = null;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $_condition = null;

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
     * @access public
     * @version 0.0.1
     */
    public function __construct(array $settings)
    {
        $this->_settings = $settings;

        if (!empty($settings['context'])) {
            $this->_context = new Context(array_merge(
                $settings['context'],
                [ 'manager' => $this ]
            ));
        } else {
            $this->_context = new Context(['manager' => $this]);
        }

        if (!empty($settings['policy'])) {
            $this->_policy = json_decode($settings['policy']);

            if (isset($this->_policy->Condition)) {
                $this->_condition = PolicyParser::parseCondition(
                    $this->_policy->Condition,
                    $this->_context
                );
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @return void
     */
    public function __get($prop)
    {
        if (!$this->_isHydrated) {
            $this->hydrate();
        }

        return property_exists($this->_policy, $prop) ? $this->_policy->{$prop} : null;
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
        if (!$this->_isHydrated) {
            $this->hydrate();
        }

        return property_exists($this->_policy, $prop);
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
     * @return boolean
     *
     * @access public
     * @version 0.0.1
     */
    public function isApplicable()
    {
        $result = true;

        if (!is_null($this->_condition)) {
            $conditions = $this->_condition;

            foreach ($conditions as $i => &$group) {
                if ($i !== 'Operator') {
                    foreach ($group as $j => &$row) {
                        if ($j !== 'Operator') {
                            $left  = ExpressionParser::convertToValue(
                                $row['left'], $this->_context
                            );
                            $right = ExpressionParser::convertToValue(
                                $row['right'], $this->_context
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
     * evaluated in further steps
     *
     * @return Manager
     */
    public function hydrate()
    {
        $this->_policy     = PolicyParser::parse($this->_policy, $this->_context);
        $this->_isHydrated = true;

        return $this;
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

}