<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Manager;

/**
 * Typecast manager
 *
 * @version 0.0.1
 */
class TypecastManager
{

    /**
     * Collection of additional types
     *
     * @var array
     *
     * @access private
     * @version 0.0.1
     */
    private $_map = [];

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
     * Execute type casting
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public function cast($value, $type = 'string')
    {
        return $this->_typecast($value, $type);
    }

    /**
     * Cast value to specific type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    private function _typecast($value, $type)
    {
        switch ($type) {
            case 'string':
                $value = (string) $value;
                break;

            case 'ip':
                $value = inet_pton($value);
                break;

            case 'int':
                $value = (int) $value;
                break;

            case 'float':
                $value = (float) $value;
                break;

            case 'boolean':
            case 'bool':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;

            case 'json':
                $value = json_decode($value, true);
                break;

            case 'array':
                $value = (array) $value;
                break;

            case 'null':
                if (is_scalar($value) || is_null($value)) {
                    $value = (is_null($value) || $value === ''  ? null : $value);
                } else if (is_array($value)) {
                    $value = (count($value) === 0 ? null : $value);
                }
                break;

            case 'date':
                $value = new \DateTime($value, new \DateTimeZone('UTC'));
                break;

            default:
                if (isset($this->_map[$type])) {
                    if (is_callable($this->_map[$type])) {
                        $value = call_user_func($this->_map[$type], $value);
                    }
                }
                break;
        }

        return $value;
    }

}