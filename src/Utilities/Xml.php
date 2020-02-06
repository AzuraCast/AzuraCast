<?php
namespace App\Utilities;

use SimpleXMLElement;

class Xml
{
    /**
     * Convert from an XML string into a PHP array.
     *
     * @param string $xml
     *
     * @return array
     */
    public static function xmlToArray($xml)
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

    /**
     * Convert a PHP array into an XML string.
     *
     * @param array $array
     *
     * @return mixed
     */
    public static function arrayToXml($array)
    {
        $xml_info = new SimpleXMLElement('<?xml version="1.0"?><return></return>');
        self::_arr_to_xml($array, $xml_info);

        return $xml_info->asXML();
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