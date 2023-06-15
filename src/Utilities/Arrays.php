<?php

declare(strict_types=1);

namespace App\Utilities;

final class Arrays
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
     * @param object|array $array
     * @param string $separator
     * @param string|null $prefix
     *
     * @return mixed[]
     */
    public static function flattenArray(object|array $array, string $separator = '.', ?string $prefix = null): array
    {
        if (is_object($array)) {
            // Quick and dirty conversion from object to array.
            $array = self::objectToArray($array);
        }

        $return = [];

        foreach ($array as $key => $value) {
            $returnKey = (string)($prefix ? $prefix . $separator . $key : $key);
            if (is_array($value)) {
                $return = array_merge($return, self::flattenArray($value, $separator, $returnKey));
            } else {
                $return[$returnKey] = $value;
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

    /**
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     *
     * @return mixed[]
     *
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection
     */
    public static function arrayMergeRecursiveDistinct(array &$array1, array &$array2): array
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursiveDistinct($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @template T of object
     *
     * @param array<array-key, T> $objects
     * @param callable $keyFunction
     * @return array<array-key, T>
     */
    public static function keyByCallable(array $objects, callable $keyFunction): array
    {
        $newArray = [];
        foreach ($objects as $object) {
            $newArray[$keyFunction($object)] = $object;
        }

        return $newArray;
    }
}
