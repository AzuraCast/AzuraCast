<?php

namespace App\Utilities;

class Arrays
{
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
                $array = self::objectToArray($array);
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

    /**
     * @param object $source
     *
     * @return mixed[]
     */
    public static function objectToArray(object $source): array
    {
        return json_decode(
            json_encode($source, JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }
}
