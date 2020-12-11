<?php

namespace App\Utilities;

class Arrays
{
    /**
     * Sort a supplied array (the first argument) by one or more indices, specified in this format:
     * arrayOrderBy($data, [ 'index_name', SORT_ASC, 'index2_name', SORT_DESC ])
     *
     * Internally uses array_multisort().
     *
     * @param array $data
     * @param array $args
     *
     * @return mixed
     */
    public static function arrayOrderBy($data, array $args = [])
    {
        if (empty($args)) {
            return $data;
        }

        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = [];
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        array_multisort(...$args);

        return array_pop($args);
    }

    /**
     * Flatten an array from format:
     * [
     *   'user' => [
     *     'id' => 1,
     *     'name' => 'test',
     *   ]
     * ]
     *
     * to format:
     * [
     *   'user.id' => 1,
     *   'user.name' => 'test',
     * ]
     *
     * This function is used to create replacements for variables in strings.
     *
     * @param array|object $array
     * @param string $separator
     * @param null $prefix
     *
     * @return mixed[]
     */
    public static function flattenArray($array, $separator = '.', $prefix = null): array
    {
        if (!is_array($array)) {
            if (is_object($array)) {
                // Quick and dirty conversion from object to array.
                $array = json_decode(json_encode($array, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            } else {
                return $array;
            }
        }

        $return = [];

        foreach ($array as $key => $value) {
            $return_key = $prefix ? $prefix . $separator . $key : $key;
            if (is_array($value)) {
                $return = array_merge($return, self::flattenArray($value, $separator, $return_key));
            } else {
                $return[$return_key] = $value;
            }
        }

        return $return;
    }
}
