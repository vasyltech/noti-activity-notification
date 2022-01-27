<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Core;

use JsonPolicy\Manager;

/**
 * Context
 *
 * @version 0.0.1
 */
class Context
{

    /**
     * Constructor
     *
     * @param array $data
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public function __construct(array $data = [])
    {
        // Making sure that main Json Policy manager is always present
        // If none provide, at least get the last initialized instance
        if (array_key_exists('manager', $data) === false) {
            $data['manager'] = Manager::bootstrap();
        }

        foreach($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $prop
     * @return boolean
     */
    public function __isset($prop)
    {
        return property_exists($this, $prop);
    }

    /**
     * Get marker value
     *
     * @param string $source
     * @param string $xpath
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public function getMarkerValue($source, $xpath)
    {
        return $this->manager->getMarkerManager()->getValue($source, $xpath, $this);
    }

    /**
     * Cast value to new type
     *
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public function castValue($value, $type)
    {
        return $this->manager->getTypecastManager()->cast($value, $type);
    }

}