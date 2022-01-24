<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Parser;

use JsonPolicy\Core\Context;

/**
 * Policy parser
 *
 * @version 0.0.1
 */
class PolicyParser
{

    /**
     * Parse policies
     *
     * @param string                   $policy
     * @param \JsonPolicy\Core\Context $context
     *
     * @return object
     *
     * @access public
     * @version 0.0.1
     */
    public static function parse(string $policy, Context $context)
    {
        $response = null;
        $decoded  = json_decode($policy);

        if (is_object($decoded) || is_array($decoded)) { // Is valid JSON structure?
            if (is_scalar($decoded)) {
                $response = ExpressionParser::parseToValue($decoded, $context);
            } else if (is_null($decoded)) {
                $response = $decoded;
            } else {
                $response = self::iterate($decoded, $context);
            }
        } else {
            $response = ExpressionParser::parseToValue($policy, $context);
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param object  $config
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @static
     */
    protected static function iterate($config, Context $context)
    {
        foreach($config as $key => $value) {
            if ($key === 'Condition') {
                $config->{$key} = ConditionParser::parse($value, $context);
            } else {
                $parsed_key = ExpressionParser::parseToValue($key, $context);

                if (is_scalar($value)) {
                    $parsed_value = ExpressionParser::parseToValue(
                        $value, $context
                    );
                } else if (is_null($value)) {
                    $parsed_value = null;
                } else {
                    $parsed_value = self::iterate($value, $context);
                }

                if (is_array($config)) {
                    $config[$parsed_key] = $parsed_value;
                } else {
                    $config->{$parsed_key} = $parsed_value;
                }
            }
        }

        return $config;
    }

}