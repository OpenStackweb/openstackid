<?php namespace utils;
/**
 * Copyright 2015 OpenStack Foundation
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 **/

/**
 * Class OrderParser
 * @package utils
 */
final class OrderParser
{
    /**
     * @param string $orders
     * @param array $allowed_fields
     * @return Order
     * @throws OrderParserException
     */
    public static function parse($orders, $allowed_fields = [])
    {
        $res    = [];
        $orders = explode(',', trim($orders));
        //default ordering is asc
        foreach($orders as $field)
        {
            $field = trim($field);
            $element = null;
            if(strpos($field, '+') === 0)
            {
                $field = trim($field,'+');
                if(!in_array($field, $allowed_fields))
                    throw new OrderParserException(sprintf("order by field %s is not allowed", $field));
                $element = OrderElement::buildAscFor($field);
            }
            else if(strpos($field, '-') === 0)
            {
                $field = trim($field,'-');
                if(!in_array($field, $allowed_fields))
                    throw new OrderParserException(sprintf("order by field %s is not allowed", $field));
                $element = OrderElement::buildDescFor($field);
            }
            else
            {
                if(!in_array($field, $allowed_fields))
                    throw new OrderParserException(sprintf("order by field %s is not allowed", $field));
                $element = OrderElement::buildAscFor($field);
            }
            array_push($res, $element);
        }
        return new Order($res);
    }
}