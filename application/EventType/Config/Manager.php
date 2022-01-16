<?php

namespace ReactiveLog\EventType\Config;

class Manager
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private static $_instance = null;

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
     * Undocumented function
     *
     * @param array|string $config
     * @param Context      $properties
     *
     * @return object|string
     */
    public function hydrate($config, Context $context)
    {
        if (is_scalar($config)) {
            $response = ExpressionParser::parseToValue($config, $context);
        } else if (is_null($config)) {
            $response = $config;
        } else {
            $response = $this->iterate($config, $context, 0);
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param [type] $config
     * @param Context $context
     *
     * @return object
     */
    protected function iterate($config, Context $context, $depth = 0)
    {
        foreach($config as $key => $value) {
            if ($depth === 0 && $key === 'Condition') {
                $config->{$key} = ConditionParser::parse($value, $context);
            } else {
                $parsed_key = ExpressionParser::parseToValue($key, $context);

                if (is_scalar($value)) {
                    if (is_array($config)) {
                        $config[$key] = ExpressionParser::parseToValue(
                            $value, $context
                        );
                    } else {
                        $config->{$parsed_key} = ExpressionParser::parseToValue(
                            $value, $context
                        );
                    }
                } else {
                    if (is_array($config)) {
                        $config[$parsed_key] = $this->iterate(
                            $value, $context, $depth + 1
                        );
                    } else {
                        $config->{$parsed_key} = $this->iterate(
                            $value, $context, $depth + 1
                        );
                    }
                }
            }
        }

        return $config;
    }

    /**
     * Check if policy statement or param is applicable
     *
     * @param object  $conditions
     * @param Context $context
     *
     * @return boolean
     *
     * @access private
     * @version 0.0.1
     */
    public function isApplicable($conditions, Context $context)
    {
        $result = true;

        if (is_object($conditions)) {
            foreach ($conditions as $i => &$group) {
                if ($i !== 'Operator') {
                    foreach ($group as $j => &$row) {
                        if ($j !== 'Operator') {
                            $left = ExpressionParser::convertToValue(
                                $row['left'], $context
                            );

                            $right = ExpressionParser::convertToValue(
                                $row['right'], $context
                            );

                            $row = array(
                                // Left expression
                                'left' => $left,
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
     * Get context
     *
     * @return Context
     *
     * @access public
     * @version 0.0.1
     */
    public function getContext(array $properties = [])
    {
        return new Context(array_merge(
            [ 'manager' => $this ],
            $properties
        ));
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
            $this->_typecast_manager = new TypecastManager();
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
            $this->_condition_manager = new ConditionManager();
        }

        return $this->_condition_manager;
    }

     /**
     * Undocumented function
     *
     * @return void
     */
    public static function bootstrap()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    /**
     * Get single instance of the manager
     *
     * @return Manager
     *
     * @access public
     * @static
     */
    public static function getInstance()
    {
        return self::bootstrap();
    }

}