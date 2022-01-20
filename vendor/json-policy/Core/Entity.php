<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Core;

/**
 * A single entity
 *
 * The "entity" is parsed representation of the policy expression
 *
 * @version 0.0.1
 */
class Entity
{

    /**
     * Raw expression
     *
     * @var string
     *
     * @access protected
     * @version 0.0.1
     */
    protected $raw;

    /**
     * Mapping format
     *
     * @var string
     *
     * @access protected
     * @version 0.0.1
     */
    protected $format;

    /**
     * Expression typecast
     *
     * @var string
     *
     * @access protected
     * @version 0.0.1
     */
    protected $typecast;

    /**
     * Array of expression tokens (parsed markers or raw values)
     *
     * @var array
     *
     * @access protected
     * @version 0.0.1
     */
    protected $tokens = [];

    /**
     * Indicator that markers are embedded into string
     *
     * @var boolean
     *
     * @access protected
     * @version 0.0.1
     */
    protected $is_embedded = false;

    /**
     * Constructor
     *
     * @param string $raw
     * @param array  $parsed_expression
     *
     * @return void
     *
     * @access public
     * @version 0.0.1
     */
    public function __construct($raw, array $parsed_expression)
    {
        $this->raw = $raw;

        foreach($parsed_expression as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    /**
     * Convert entity to a single value
     *
     * @param Context $context
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public function convertToValue(Context $context)
    {
        $value = ($this->is_embedded ? $this->raw : null);

        foreach($this->tokens as $id => $token) {
            $token_value = $this->convertTokenValue($token, $context);

            if (!empty($this->is_embedded)) {
                $value = str_replace($id, $token_value, $value);
            } else {
                $value = $token_value;
            }
        }

        // Typecast value if specified
        if (!empty($this->typecast)) {
            $value = $context->castValue($value, $this->typecast);
        }

        // Finally, if this is mapped entity, then map all the scalar values in the
        // value to the defined format
        if (!empty($this->format)) {
            $response = [];

            foreach((array)$value as $t) {
                $response[] = sprintf($this->format, $t);
            }
        } else {
            $response = $value;
        }

        return $response;
    }

    /**
     * Covert entity to array
     *
     * @return array
     *
     * @access public
     * @version 0.0.1
     */
    public function toArray()
    {
        $response = [];

        foreach($this as $key => $value) {
            if (!empty($value) || $key === 'raw') {
                $response[$key] = $value;
            }
        }

        return $response;
    }

    /**
     * Convert a single token to value
     *
     * @param array   $token
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @version 0.0.1
     */
    protected function convertTokenValue(array $token, Context $context)
    {
        $value = null;

        if (array_key_exists('value', $token)) {
            $value = $token['value'];
        } else if (array_key_exists('source', $token)) {
            $value = $context->getMarkerValue($token['source'], $token['xpath']);
        }

        return $value;
    }

}