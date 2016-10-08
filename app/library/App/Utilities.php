<?php
/**
 * Miscellaneous Utilities Class
 **/

namespace App;

use App\Cache;
use Zend\Feed\Reader\Reader;

class Utilities
{
    /**
     * Pretty print_r
     *
     * @param $var
     * @param bool $return
     * @return string
     */
    public static function print_r($var, $return = FALSE)
    {
        $return_value = '<pre style="font-size: 13px; font-family: Consolas, Courier New, Courier, monospace; color: #000; background: #EFEFEF; border: 1px solid #CCC; padding: 5px;">';
        $return_value .= print_r($var, TRUE);
        $return_value .= '</pre>';
        
        if ($return)
        {
            return $return_value;
        }
        else
        {
            echo $return_value;
        }
    }

    /**
     * Replacement for money_format that places the negative sign ahead of the dollar sign.
     *
     * @param $number
     * @return string
     */
    public static function money_format($number)
    {
        if ($number < 0)
            return '-$'.number_format(abs($number), 2);
        else
            return '$'.number_format($number, 2);
    }

    /**
     * Generate a randomized password of specified length.
     *
     * @param $char_length
     * @return string
     */
    public static function generatePassword($char_length = 8)
    {
        // String of all possible characters. Avoids using certain letters and numbers that closely resemble others.
        $numeric_chars = str_split('234679');
        $uppercase_chars = str_split('ACDEFGHJKLMNPQRTWXYZ');
        $lowercase_chars = str_split('acdefghjkmnpqrtwxyz');
        
        $chars = array($numeric_chars, $uppercase_chars, $lowercase_chars);
        
        $password = '';
        for($i = 1; $i <= $char_length; $i++)
        {
            $char_array = $chars[$i % 3];
            $password .= $char_array[mt_rand(0, count($char_array)-1)];
        }
        
        return str_shuffle($password);
    }

    /**
     * Convert a specified number of seconds into a date range.
     *
     * @param $timestamp
     * @return string
     */
    public static function timeToText($timestamp)
    {
        return self::timeDifferenceText(0, $timestamp);
    }

    /**
     * Get the textual difference between two strings.
     *
     * @param $timestamp1
     * @param $timestamp2
     * @param int $precision
     * @return string
     */
    public static function timeDifferenceText($timestamp1, $timestamp2, $precision = 1)
    {
        $time_diff = abs($timestamp1 - $timestamp2);
        
        if ($time_diff < 60)
        {
            $time_num = intval($time_diff);
            return sprintf(ngettext("%d second", "%d seconds", $time_num), $time_num);
        }
        else if ($time_diff >= 60 && $time_diff < 3600)
        {
            $time_num = round($time_diff / 60, $precision);
            return sprintf(ngettext("%d minute", "%d minutes", $time_num), $time_num);
        }
        else if ($time_diff >= 3600 && $time_diff < 216000)
        {
            $time_num = round($time_diff / 3600, $precision);
            return sprintf(ngettext("%d hour", "%d hours", $time_num), $time_num);
        }
        else if ($time_diff >= 216000 && $time_diff < 10368000)
        {
            $time_num = round($time_diff / 86400);
            return sprintf(ngettext("%d day", "%d days", $time_num), $time_num);
        }
        else
        {
            $time_num = round($time_diff / 2592000);
            return sprintf(ngettext("%d month", "%d months", $time_num), $time_num);
        }
    }

    /**
     * Forced-GMT strtotime alternative.
     *
     * @param $time
     * @param null $now
     * @return int
     */
    public static function gstrtotime($time, $now = NULL)
    {
        $prev_timezone = @date_default_timezone_get();
        @date_default_timezone_set('UTC');

        $timestamp = strtotime($time, $now);

        @date_default_timezone_set($prev_timezone);
        return $timestamp;
    }

    /**
     * Truncate text (adding "..." if needed)
     *
     * @param $text
     * @param int $limit
     * @param string $pad
     * @return string
     */
    public static function truncateText($text, $limit = 80, $pad = '...')
    {
        mb_internal_encoding('UTF-8');

        if (mb_strlen($text) <= $limit)
        {
            return $text;
        }
        else
        {
            $wrapped_text = self::wordWrapMultiByte($text, $limit, "{N}", TRUE);
            $shortened_text = mb_substr($wrapped_text, 0, strpos($wrapped_text, "{N}"));
            
            // Prevent the padding string from bumping up against punctuation.
            $punctuation = array('.',',',';','?','!');
            if (in_array(mb_substr($shortened_text, -1), $punctuation))
            {
                $shortened_text = mb_substr($shortened_text, 0, -1);
            }
            
            return $shortened_text.$pad;
        }
    }

    /**
     * UTF-8 capable replacement for wordwrap function.
     *
     * @param $str
     * @param int $width
     * @param string $break
     * @param bool $cut
     * @return string
     */
    public static function wordWrapMultiByte($str, $width = 75, $break = "\n", $cut = false)
    {
        $lines = explode($break, $str);
        foreach ($lines as &$line) {
            $line = rtrim($line);
            if (mb_strlen($line) <= $width)
                continue;
            $words = explode(' ', $line);
            $line = '';
            $actual = '';
            foreach ($words as $word) {
                if (mb_strlen($actual.$word) <= $width)
                    $actual .= $word.' ';
                else {
                    if ($actual != '')
                        $line .= rtrim($actual).$break;
                    $actual = $word;
                    if ($cut) {
                        while (mb_strlen($actual) > $width) {
                            $line .= mb_substr($actual, 0, $width).$break;
                            $actual = mb_substr($actual, $width);
                        }
                    }
                    $actual .= ' ';
                }
            }
            $line .= trim($actual);
        }
        return implode($break, $lines);
    }

    /**
     * Truncate URL in text-presentable format (i.e. "http://www.example.com" becomes "example.com")
     *
     * @param $url
     * @param int $length
     * @return string
     */
    public static function truncateUrl($url, $length=40)
    {
        $url = str_replace(array('http://', 'https://', 'www.'), array('', '', ''), $url);
        return self::truncateText(rtrim($url, '/'), $length);
    }

    /**
     * Join one or more items into an array.
     *
     * @param array $items
     * @return string
     */
    public static function joinCompound(array $items)
    {
        $count = count($items);

        if ($count == 0)
            return '';

        if ($count == 1)
            return $items[0];

        return implode(', ', array_slice($items, 0, -1)) . ' and ' . end($items);
    }

    /**
     * Create an array where the keys and values match each other.
     *
     * @param $array
     * @return array
     */
    public static function pairs($array)
    {
        return array_combine($array, $array);
    }

    /**
     * Split an array into "columns", typically for display purposes.
     *
     * @param $array
     * @param int $num_cols
     * @param bool $preserve_keys
     * @return array
     */
    public static function columns($array, $num_cols = 2, $preserve_keys = true)
    {
        $items_total = (int)count($array);
        $items_per_col = ceil($items_total / $num_cols);
        return array_chunk($array, $items_per_col, $preserve_keys);
    }

    /**
     * Split an array into "rows", typically for display purposes.
     *
     * @param $array
     * @param int $num_per_row
     * @param bool $preserve_keys
     * @return array
     */
    public static function rows($array, $num_per_row = 3, $preserve_keys = true)
    {
        return array_chunk($array, $num_per_row, $preserve_keys);
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
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    public static function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;
        foreach ($array2 as $key => &$value)
        {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key]))
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            else
                $merged[$key] = $value;
        }

        return $merged;
    }

    /**
     * Return all keys in a multi-dimensional array.
     * Useful for getting all possible values in an optgroup-stacked select dropdown.
     *
     * @param $array
     * @return array The keys found.
     */
    public static function array_keys_recursive($array)
    {
        $keys = array();

        foreach((array)$array as $key => $value)
        {
            if (is_array($value))
                $keys = array_merge($keys, self::array_keys_recursive($value));
            else
                $keys[] = $key;
        }

        return $keys;
    }

    /**
     * Identity function, returns its argument unmodified.
     *
     * This is useful almost exclusively as a workaround to an oddity in the PHP
     * grammar -- this is a syntax error:
     *
     *    COUNTEREXAMPLE
     *    new Thing()->doStuff();
     *
     * ...but this works fine:
     *
     *    id(new Thing())->doStuff();
     *
     * @param   wild Anything.
     * @return  wild Unmodified argument.
     */
    public static function id($x)
    {
        return $x;
    }

    /**
     * Access an array index, retrieving the value stored there if it exists or
     * a default if it does not. This function allows you to concisely access an
     * index which may or may not exist without raising a warning.
     *
     * @param   array   Array to access.
     * @param   scalar  Index to access in the array.
     * @param   wild    Default value to return if the key is not present in the
     *                  array.
     * @return  wild    If `$array[$key]` exists, that value is returned. If not,
     *                  $default is returned without raising a warning.
     */
    public static function idx(array $array, $key, $default = null)
    {
        // isset() is a micro-optimization - it is fast but fails for null values.
        if (isset($array[$key])) {
            return $array[$key];
        }

        // Comparing $default is also a micro-optimization.
        if ($default === null || array_key_exists($key, $array)) {
            return null;
        }

        return $default;
    }

    /**
     * Call a method on a list of objects. Short for "method pull", this function
     * works just like @{function:ipull}, except that it operates on a list of
     * objects instead of a list of arrays. This function simplifies a common type
     * of mapping operation:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($objects as $key => $object) {
     *      $names[$key] = $object->getName();
     *    }
     *
     * You can express this more concisely with mpull():
     *
     *    $names = mpull($objects, 'getName');
     *
     * mpull() takes a third argument, which allows you to do the same but for
     * the array's keys:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($objects as $object) {
     *      $names[$object->getID()] = $object->getName();
     *    }
     *
     * This is the mpull version():
     *
     *    $names = mpull($objects, 'getName', 'getID');
     *
     * If you pass ##null## as the second argument, the objects will be preserved:
     *
     *    COUNTEREXAMPLE
     *    $id_map = array();
     *    foreach ($objects as $object) {
     *      $id_map[$object->getID()] = $object;
     *    }
     *
     * With mpull():
     *
     *    $id_map = mpull($objects, null, 'getID');
     *
     * See also @{function:ipull}, which works similarly but accesses array indexes
     * instead of calling methods.
     *
     * @param   list          Some list of objects.
     * @param   string|null   Determines which **values** will appear in the result
     *                        array. Use a string like 'getName' to store the
     *                        value of calling the named method in each value, or
     *                        ##null## to preserve the original objects.
     * @param   string|null   Determines how **keys** will be assigned in the result
     *                        array. Use a string like 'getID' to use the result
     *                        of calling the named method as each object's key, or
     *                        `null` to preserve the original keys.
     * @return  dict          A dictionary with keys and values derived according
     *                        to whatever you passed as `$method` and `$key_method`.
     */
    public static function mpull(array $list, $method, $key_method = null)
    {
        $result = array();
        foreach ($list as $key => $object) {
            if ($key_method !== null) {
                $key = $object->$key_method();
            }
            if ($method !== null) {
                $value = $object->$method();
            } else {
                $value = $object;
            }
            $result[$key] = $value;
        }

        return $result;
    }


    /**
     * Access a property on a list of objects. Short for "property pull", this
     * function works just like @{function:mpull}, except that it accesses object
     * properties instead of methods. This function simplifies a common type of
     * mapping operation:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($objects as $key => $object) {
     *      $names[$key] = $object->name;
     *    }
     *
     * You can express this more concisely with ppull():
     *
     *    $names = ppull($objects, 'name');
     *
     * ppull() takes a third argument, which allows you to do the same but for
     * the array's keys:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($objects as $object) {
     *      $names[$object->id] = $object->name;
     *    }
     *
     * This is the ppull version():
     *
     *    $names = ppull($objects, 'name', 'id');
     *
     * If you pass ##null## as the second argument, the objects will be preserved:
     *
     *    COUNTEREXAMPLE
     *    $id_map = array();
     *    foreach ($objects as $object) {
     *      $id_map[$object->id] = $object;
     *    }
     *
     * With ppull():
     *
     *    $id_map = ppull($objects, null, 'id');
     *
     * See also @{function:mpull}, which works similarly but calls object methods
     * instead of accessing object properties.
     *
     * @param   list          Some list of objects.
     * @param   string|null   Determines which **values** will appear in the result
     *                        array. Use a string like 'name' to store the value of
     *                        accessing the named property in each value, or
     *                        `null` to preserve the original objects.
     * @param   string|null   Determines how **keys** will be assigned in the result
     *                        array. Use a string like 'id' to use the result of
     *                        accessing the named property as each object's key, or
     *                        `null` to preserve the original keys.
     * @return  dict          A dictionary with keys and values derived according
     *                        to whatever you passed as `$property` and
     *                        `$key_property`.
     */
    public static function ppull(array $list, $property, $key_property = null)
    {
        $result = array();
        foreach ($list as $key => $object) {
            if ($key_property !== null) {
                $key = $object->$key_property;
            }
            if ($property !== null) {
                $value = $object->$property;
            } else {
                $value = $object;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Choose an index from a list of arrays. Short for "index pull", this function
     * works just like @{function:mpull}, except that it operates on a list of
     * arrays and selects an index from them instead of operating on a list of
     * objects and calling a method on them.
     *
     * This function simplifies a common type of mapping operation:
     *
     *    COUNTEREXAMPLE
     *    $names = array();
     *    foreach ($list as $key => $dict) {
     *      $names[$key] = $dict['name'];
     *    }
     *
     * With ipull():
     *
     *    $names = ipull($list, 'name');
     *
     * See @{function:mpull} for more usage examples.
     *
     * @param   array         Some list of arrays.
     * @param   string|null   Determines which **values** will appear in the result
     *                        array. Use a scalar to select that index from each
     *                        array, or null to preserve the arrays unmodified as
     *                        values.
     * @param   string|null   Determines which **keys** will appear in the result
     *                        array. Use a scalar to select that index from each
     *                        array, or null to preserve the array keys.
     * @return  array          A dictionary with keys and values derived according
     *                        to whatever you passed for `$index` and `$key_index`.
     */
    public static function ipull(array $list, $index, $key_index = null) {
        $result = array();
        foreach ($list as $key => $array) {
            if ($key_index !== null) {
                $key = $array[$key_index];
            }
            if ($index !== null) {
                $value = $array[$index];
            } else {
                $value = $array;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Group a list of objects by the result of some method, similar to how
     * GROUP BY works in an SQL query. This function simplifies grouping objects
     * by some property:
     *
     *    COUNTEREXAMPLE
     *    $animals_by_species = array();
     *    foreach ($animals as $animal) {
     *      $animals_by_species[$animal->getSpecies()][] = $animal;
     *    }
     *
     * This can be expressed more tersely with mgroup():
     *
     *    $animals_by_species = mgroup($animals, 'getSpecies');
     *
     * In either case, the result is a dictionary which maps species (e.g., like
     * "dog") to lists of animals with that property, so all the dogs are grouped
     * together and all the cats are grouped together, or whatever super
     * businessesey thing is actually happening in your problem domain.
     *
     * See also @{function:igroup}, which works the same way but operates on
     * array indexes.
     *
     * @param   list    List of objects to group by some property.
     * @param   string  Name of a method, like 'getType', to call on each object
     *                  in order to determine which group it should be placed into.
     * @param   ...     Zero or more additional method names, to subgroup the
     *                  groups.
     * @return  dict    Dictionary mapping distinct method returns to lists of
     *                  all objects which returned that value.
     */
    public static function mgroup(array $list, $by /* , ... */)
    {
        $map = self::mpull($list, $by);

        $groups = array();
        foreach ($map as $group) {
            // Can't array_fill_keys() here because 'false' gets encoded wrong.
            $groups[$group] = array();
        }

        foreach ($map as $key => $group) {
            $groups[$group][$key] = $list[$key];
        }

        $args = func_get_args();
        $args = array_slice($args, 2);
        if ($args) {
            array_unshift($args, null);
            foreach ($groups as $group_key => $grouped) {
                $args[0] = $grouped;
                $groups[$group_key] = call_user_func_array('mgroup', $args);
            }
        }

        return $groups;
    }

    /**
     * Group a list of arrays by the value of some index. This function is the same
     * as @{function:mgroup}, except it operates on the values of array indexes
     * rather than the return values of method calls.
     *
     * @param   list    List of arrays to group by some index value.
     * @param   string  Name of an index to select from each array in order to
     *                  determine which group it should be placed into.
     * @param   ...     Zero or more additional indexes names, to subgroup the
     *                  groups.
     * @return  dict    Dictionary mapping distinct index values to lists of
     *                  all objects which had that value at the index.
     */
    public static function igroup(array $list, $by /* , ... */)
    {
        $map = self::ipull($list, $by);

        $groups = array();
        foreach ($map as $group) {
            $groups[$group] = array();
        }

        foreach ($map as $key => $group) {
            $groups[$group][$key] = $list[$key];
        }

        $args = func_get_args();
        $args = array_slice($args, 2);
        if ($args) {
            array_unshift($args, null);
            foreach ($groups as $group_key => $grouped) {
                $args[0] = $grouped;
                $groups[$group_key] = call_user_func_array('igroup', $args);
            }
        }

        return $groups;
    }

    /**
     * Sort a list of objects by the return value of some method. In PHP, this is
     * often vastly more efficient than `usort()` and similar.
     *
     *    // Sort a list of Duck objects by name.
     *    $sorted = msort($ducks, 'getName');
     *
     * It is usually significantly more efficient to define an ordering method
     * on objects and call `msort()` than to write a comparator. It is often more
     * convenient, as well.
     *
     * NOTE: This method does not take the list by reference; it returns a new list.
     *
     * @param   list    List of objects to sort by some property.
     * @param   string  Name of a method to call on each object; the return values
     *                  will be used to sort the list.
     * @return  list    Objects ordered by the return values of the method calls.
     */
    public static function msort(array $list, $method)
    {
        $surrogate = self::mpull($list, $method);

        asort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }

    /**
     * Reverse of `msort`.
     *
     * @param   list    List of objects to sort by some property.
     * @param   string  Name of a method to call on each object; the return values
     *                  will be used to sort the list.
     * @return  list    Objects ordered by the return values of the method calls.
     */
    public static function mrsort(array $list, $method)
    {
        $surrogate = self::mpull($list, $method);

        arsort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }

    /**
     * Sort a list of arrays by the value of some index. This method is identical to
     * @{function:msort}, but operates on a list of arrays instead of a list of
     * objects.
     *
     * @param   list    List of arrays to sort by some index value.
     * @param   string  Index to access on each object; the return values
     *                  will be used to sort the list.
     * @return  list    Arrays ordered by the index values.
     */
    public static function isort(array $list, $index)
    {
        $surrogate = self::ipull($list, $index);

        asort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }

    /**
     * Reverse of `isort`.
     *
     * @param   list    List of arrays to sort by some index value.
     * @param   string  Index to access on each object; the return values
     *                  will be used to sort the list.
     * @return  list    Arrays ordered by the index values.
     */
    public static function irsort(array $list, $index)
    {
        $surrogate = self::ipull($list, $index);

        arsort($surrogate);

        $result = array();
        foreach ($surrogate as $key => $value) {
            $result[$key] = $list[$key];
        }

        return $result;
    }

    /**
     * Filter a list of objects by executing a method across all the objects and
     * filter out the ones with empty() results. this function works just like
     * @{function:ifilter}, except that it operates on a list of objects instead
     * of a list of arrays.
     *
     * For example, to remove all objects with no children from a list, where
     * 'hasChildren' is a method name, do this:
     *
     *   mfilter($list, 'hasChildren');
     *
     * The optional third parameter allows you to negate the operation and filter
     * out nonempty objects. To remove all objects that DO have children, do this:
     *
     *   mfilter($list, 'hasChildren', true);
     *
     * @param  array        List of objects to filter.
     * @param  string       A method name.
     * @param  bool         Optionally, pass true to drop objects which pass the
     *                      filter instead of keeping them.
     * @return array        List of objects which pass the filter.
     */
    public static function mfilter(array $list, $method, $negate = false)
    {
        if (!is_string($method)) {
            throw new \Exception('Argument method is not a string.');
        }

        $result = array();
        foreach ($list as $key => $object) {
            $value = $object->$method();

            if (!$negate) {
                if (!empty($value)) {
                    $result[$key] = $object;
                }
            } else {
                if (empty($value)) {
                    $result[$key] = $object;
                }
            }
        }

        return $result;
    }

    /**
     * Filter a list of arrays by removing the ones with an empty() value for some
     * index. This function works just like @{function:mfilter}, except that it
     * operates on a list of arrays instead of a list of objects.
     *
     * For example, to remove all arrays without value for key 'username', do this:
     *
     *   ifilter($list, 'username');
     *
     * The optional third parameter allows you to negate the operation and filter
     * out nonempty arrays. To remove all arrays that DO have value for key
     * 'username', do this:
     *
     *   ifilter($list, 'username', true);
     *
     * @param  array        List of arrays to filter.
     * @param  scalar       The index.
     * @param  bool         Optionally, pass true to drop arrays which pass the
     *                      filter instead of keeping them.
     * @return array        List of arrays which pass the filter.
     */
    public static function ifilter(array $list, $index, $negate = false)
    {
        if (!is_scalar($index)) {
            throw new \Exception('Argument index is not a scalar.');
        }

        $result = array();
        if (!$negate) {
            foreach ($list as $key => $array) {
                if (!empty($array[$index])) {
                    $result[$key] = $array;
                }
            }
        } else {
            foreach ($list as $key => $array) {
                if (empty($array[$index])) {
                    $result[$key] = $array;
                }
            }
        }

        return $result;
    }

    /**
     * Sort a supplied array (the first argument) by one or more indices, specified in this format:
     * arrayOrderBy($data, [ 'index_name', SORT_ASC, 'index2_name', SORT_DESC ])
     *
     * Internally uses array_multisort().
     *
     * @param $data
     * @param array $args
     * @return mixed
     */
    public static function arrayOrderBy($data, array $args = array())
    {
        if (empty($args))
            return $data;

        foreach ($args as $n => $field)
        {
            if (is_string($field))
            {
                $tmp = array();
                foreach ($data as $key => $row)
                    $tmp[$key] = $row[$field];
                $args[$n] = $tmp;
            }
        }

        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }

    /**
     * Split a URL into an array (similar to parse_url() itself) but with cleaner parameter handling.
     *
     * @param $url
     * @return mixed
     */
    public static function parseUrl($url)
    {
        $url_parts = @parse_url($url);
        $url_parts['path_clean'] = trim($url_parts['path'], '/');
        $url_parts['query_arr'] = self::convertUrlQuery($url_parts['query']);

        return $url_parts;
    }

    /**
     * Convert the query string of a URL into an array of keys and values.
     *
     * @param $query
     * @return array
     */
    public static function convertUrlQuery($query)
    {
        $queryParts = explode('&', $query);
        $params = array();
        foreach ($queryParts as $param)
        {
            $item = explode('=', $param);
            $params[$item[0]] = $item[1];
        }
        return $params;
    }

    /**
     * Construct a URL based on an array returned from parseUrl().
     *
     * @param $url
     * @return string
     */
    public static function buildUrl($url)
    {
        is_array($url) || $url = parse_url($url);

        if (is_array($url['query']))
            $url['query'] = http_build_query($url['query']);

        if (isset($url['path']) && substr($url['path'], 0, 1) !== '/')
            $url['path'] = '/' . $url['path'];

        $parsed_string = '';
        if (isset($url['scheme']))
            $parsed_string .= $url['scheme'] . '://';

        if (isset($url['user']))
        {
            $parsed_string .= $url['user'];

            if (isset($url['pass']))
                $parsed_string .= ':' . $url['pass'];

            $parsed_string .= '@';
        }

        if (isset($url['host']))
            $parsed_string .= $url['host'];

        if (isset($url['port']))
            $parsed_string .= ':' . $url['port'];

        if (!empty($url['path']))
            $parsed_string .= $url['path'];
        else
            $parsed_string .= '/';

        if (isset($url['query']))
            $parsed_string .= '?' . $url['query'];

        if (isset($url['fragment']))
            $parsed_string .= '#' . $url['fragment'];

        return $parsed_string;
    }
    
    /**
     * Construct a URL based on an array returned from parseUrl().
     *
     * @param $needle       The value we're looking for
     * @param $haystack     The array we're looking through
     * @param $strict       If true, checks type as well   
     * @return string
     */
    public static function recursive_array_search($needle, $haystack, $strict = false)
    {
        foreach($haystack as $key => $value) {
            if (is_array($value)) {
                // Value is an array, check that instead!
                $nextKey = self::recursive_array_search($needle, $value, $strict);
                
                if ($nextKey)
                    return $nextKey;
            }
            else if($strict ? $value === $needle : $value == $needle)
                return $key;
        }
        
        return false;
    }

    /**
     * Detect if the User-Agent matches common crawler UAs.
     * Not expected to be 100% accurate or trustworthy, just used to prevent
     * common crawlers from accessing features like API endpoints.
     *
     * @return bool
     */
    public static function isCrawler()
    {
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $crawlers_agents = strtolower('Bloglines subscriber|Dumbot|Sosoimagespider|QihooBot|FAST-WebCrawler|Superdownloads Spiderman|LinkWalker|msnbot|ASPSeek|WebAlta Crawler|Lycos|FeedFetcher-Google|Yahoo|YoudaoBot|AdsBot-Google|Googlebot|Scooter|Gigabot|Charlotte|eStyle|AcioRobot|GeonaBot|msnbot-media|Baidu|CocoCrawler|Google|Charlotte t|Yahoo! Slurp China|Sogou web spider|YodaoBot|MSRBOT|AbachoBOT|Sogou head spider|AltaVista|IDBot|Sosospider|Yahoo! Slurp|Java VM|DotBot|LiteFinder|Yeti|Rambler|Scrubby|Baiduspider|accoona');
        $crawlers = explode("|", $crawlers_agents);

        foreach($crawlers as $crawler)
        {
            if (strpos($ua, trim($crawler)) !== false)
                return true;
        }

        return false;
    }

    /**
     * Get the system time zone.
     * @return string
     */
    public static function getSystemTimeZone()
    {
        if (file_exists('/etc/timezone'))
        {
            // Ubuntu / Debian.
            $data = file_get_contents('/etc/timezone');
            if ($data)
                return trim($data);
        }
        elseif (is_link('/etc/localtime'))
        {
            // Mac OS X (and older Linuxes)
            // /etc/localtime is a symlink to the
            // timezone in /usr/share/zoneinfo.
            $filename = readlink('/etc/localtime');
            if (strpos($filename, '/usr/share/zoneinfo/') === 0)
                return substr($filename, 20);
        }
        elseif (file_exists('/etc/sysconfig/clock'))
        {
            // RHEL / CentOS
            $data = parse_ini_file('/etc/sysconfig/clock');
            if (!empty($data['ZONE']))
                return trim($data['ZONE']);
        }

        return 'UTC';
    }
}
