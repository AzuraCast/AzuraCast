<?php
namespace App;

/**
 * Class with static methods for exporting data into various formats.
 */
class Export
{
    /**
     * Generate a CSV-compatible file body given an array.
     *
     * @param $table_data
     * @param bool $headers_first_row
     * @return string
     */
    public static function csv($table_data, $headers_first_row = true)
    {
        $final_display = [];
        $row_count = 0;
        foreach ($table_data as $table_row) {
            $row_count++;
            $col_count = 0;

            $header_row = [];
            $body_row = [];

            foreach ($table_row as $table_col => $table_val) {
                $col_count++;
                if (!$headers_first_row && $row_count == 1) {
                    $header_row[] = '"' . str_replace('"', '""', $table_col) . '"';
                }

                $body_row[] = '"' . str_replace('"', '""', $table_val) . '"';
            }

            if ($header_row) {
                $final_display[] = implode(',', $header_row);
            }

            if ($body_row) {
                $final_display[] = implode(',', $body_row);
            }
        }

        return implode("\n", $final_display);
    }

    /**
     * Convert from an XML string into a PHP array.
     *
     * @param $xml
     * @return array
     */
    public static function xml_to_array($xml)
    {
        $values = $index = $array = [];
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $xml, $values, $index);
        xml_parser_free($parser);
        $i = 0;
        $name = $values[$i]['tag'];
        $array[$name] = isset($values[$i]['attributes']) ? $values[$i]['attributes'] : '';
        $array[$name] = self::_struct_to_array($values, $i);

        return $array;
    }

    protected static function _struct_to_array($values, &$i)
    {
        $child = [];
        if (isset($values[$i]['value'])) {
            array_push($child, $values[$i]['value']);
        }

        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                    array_push($child, $values[$i]['value']);
                    break;

                case 'complete':
                    $name = $values[$i]['tag'];
                    if (!empty($name)) {
                        $child[$name] = ($values[$i]['value']) ? ($values[$i]['value']) : '';
                        if (isset($values[$i]['attributes'])) {
                            $child[$name] = $values[$i]['attributes'];
                        }
                    }
                    break;

                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    $child[$name][$size] = self::_struct_to_array($values, $i);
                    break;

                case 'close':
                    return $child;
                    break;
            }
        }

        return $child;
    }

    /**
     * Convert a PHP array into an XML string.
     *
     * @param $array
     * @return mixed
     */
    public static function array_to_xml($array)
    {
        $xml_info = new \SimpleXMLElement('<?xml version="1.0"?><return></return>');
        self::_arr_to_xml($array, $xml_info);

        return $xml_info->asXML();
    }

    protected static function _arr_to_xml($array, &$xml)
    {
        foreach ((array)$array as $key => $value) {
            if (is_array($value)) {
                $key = is_numeric($key) ? "item$key" : $key;
                $subnode = $xml->addChild("$key");

                self::_arr_to_xml($value, $subnode);
            } else {
                $key = is_numeric($key) ? "item$key" : $key;
                $xml->addChild("$key", htmlspecialchars($value));
            }
        }
    }
}
