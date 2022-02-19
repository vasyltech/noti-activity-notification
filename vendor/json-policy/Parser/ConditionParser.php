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
 * Condition parser
 *
 * @version 0.0.1
 */
class ConditionParser
{

    /**
     * Parse conditions block
     *
     * @param object $conditions
     *
     * @return array
     *
     * @access public
     * @version 0.0.1
     */
    public static function parse($conditions, Context $context)
    {
        foreach($conditions as $type => $group) {
            if ($type !== 'Operator') {
                $conditions->{$type} = self::tokenizeGroup($group, $context);
            }
        }

        return $conditions;
    }

    /**
     * Tokenize a single condition group
     *
     * @param object  $group
     * @param Context $context
     *
     * @return array
     *
     * @access protected
     * @version 0.0.1
     */
    protected static function tokenizeGroup($group, Context $context)
    {
        foreach($group as $left => $right) {
            if ($left !== 'Operator') {
                $group->{$left} = [
                    'left'  => ExpressionParser::parse($left, $context),
                    'right' => ExpressionParser::parse($right, $context)
                ];
            }
        }

        return $group;
    }

}