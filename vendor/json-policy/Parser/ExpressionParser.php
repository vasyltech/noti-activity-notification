<?php

/*
 * This file is a part of JsonPolicy.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace JsonPolicy\Parser;

use JsonPolicy\Core\Entity,
    JsonPolicy\Core\Context;

/**
 * Expression parser
 *
 * @version 0.0.1
 */
class ExpressionParser
{

    /**
     * Parse expression and return the metadata that represents it
     *
     * @param string $expression
     *
     * @return JsonPolicy\Core\Entity|array
     *
     * @access public
     * @version 0.0.1
     */
    public static function parse($expression)
    {
        if (is_scalar($expression) || is_null($expression)) {
            $response = new Entity(
                $expression,
                self::_parseExpression($expression)
            );
        } elseif (is_iterable($expression)) {
            $response = [];

            foreach($expression as $element) {
                $response[] = self::parse($element);
            }
        }

        return $response;
    }

    /**
     * Parse expression and covert it to value
     *
     * @param mixed                    $expression
     * @param \JsonPolicy\Core\Context $context
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public static function parseToValue($expression, Context $context)
    {
        $parsed = self::parse($expression);

        return self::convertToValue($parsed, $context);
    }

    /**
     * Convert entity to value
     *
     * @param \JsonPolicy\Core\Entity  $entity
     * @param \JsonPolicy\Core\Context $context
     *
     * @return mixed
     *
     * @access public
     * @version 0.0.1
     */
    public static function convertToValue($entity, Context $context)
    {
        if (is_array($entity)) {
            $response = [];

            foreach($entity as $e) {
                $response[] = self::convertToValue($e, $context);
            }
        } else {
            $response = $entity->convertToValue($context);
        }

        return $response;
    }

    /**
     * Parse expression and tokenize it
     *
     * @param mixed $expression
     *
     * @return array
     *
     * @access private
     * @version 0.0.1
     */
    private static function _parseExpression($expression)
    {
        $response = [];

        // First let's check if expression contains mapping operator and if so
        // extract essential parts from it
        if (preg_match('/^(.*)[\s]+(=>)[\s]+(.*)$/i', $expression, $match) === 1) {
            $response = array_merge(
                $response, // raw expression
                ['format' => $match[1]], // extracted format
                self::_parseExpression($match[3]) // collection of tokens
            );
        // Check if we have any type casting defined. If so, extract the type value
        // and then pass further to get marker(s) value(s).
        // Note! The typecast can be defined only one per expression as it does not
        // make much sense to have two or more typecasts
        } elseif (preg_match('/^\(\*([a-z\d\-_]+)\)(.*)/i', $expression, $match)) {
            $response = array_merge(
                $response, // raw expression
                ['typecast' => $match[1]], // extracted typecast
                self::_parseExpression($match[2]) // collection of tokens
            );
        // If there are any markers defined, tokenize them
        } elseif (preg_match_all('/(\$\{[^}]+\})/', $expression, $matches)) {
            $tokens = [];
            $length = 0;

            foreach($matches[1] as $marker) {
                $parts = explode(
                    '.', preg_replace('/^\$\{([^}]+)\}$/', '${1}', $marker), 2
                );

                $tokens[$marker] = [
                    'source' => $parts[0],
                    'xpath'  => $parts[1]
                ];

                $length += strlen($marker);
            }

            $response['tokens'] = $tokens;

            // When there are two or more markers within the same expression OR the
            // expression contain other characters outside of the marker definition,
            // then they marker values will be embedded into the expression
            if (count($matches[1]) > 1 || $length !== strlen($expression)) {
                $response['is_embedded'] = true;
            }
        // Finally just return the value as-is
        } else {
            $response['tokens'] = [
                [
                    'value' => $expression
                ]
            ];
        }

        return $response;
    }

}