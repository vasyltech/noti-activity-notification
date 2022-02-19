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
     * @param object                   $policy
     * @param \JsonPolicy\Core\Context $context
     *
     * @return object
     *
     * @access public
     * @version 0.0.1
     */
    public static function parse($policy, Context $context)
    {
        $response = null;

        if (is_object($policy) || is_array($policy)) { // Is valid JSON structure?
            $response = self::iterate($policy, $context);
        } elseif (is_scalar($policy) || is_null($policy)) {
            $response = ExpressionParser::parseToValue($policy, $context);
        }

        return $response;
    }

    /**
     * Undocumented function
     *
     * @param object $conditions
     * @param Context $context
     * @return void
     */
    public static function parseCondition($conditions, Context $context)
    {
        return ConditionParser::parse($conditions, $context);
    }

    /**
     * Undocumented function
     *
     * @param object  $policy
     * @param Context $context
     *
     * @return mixed
     *
     * @access protected
     * @static
     */
    protected static function iterate($policy, Context $context)
    {
        foreach($policy as $key => $value) {
            if ($key !== 'Condition') {
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

                if (is_array($policy)) {
                    $policy[$parsed_key] = $parsed_value;
                } else {
                    $policy->{$parsed_key} = $parsed_value;
                }
            }
        }

        return $policy;
    }

}