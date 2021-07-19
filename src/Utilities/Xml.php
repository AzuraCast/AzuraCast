<?php

declare(strict_types=1);

namespace App\Utilities;

use SimpleXMLElement;

class Xml
{
    /**
     * Convert from an XML string into a PHP array.
     *
     * @param string $xml
     *
     * @return mixed[]
     */
    public static function xmlToArray(string $xml): array
    {
        $values = $index = $array = [];
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parse_into_struct($parser, $xml, $values, $index);
        xml_parser_free($parser);
        $i = 0;
        $name = $values[$i]['tag'];
        $array[$name] = $values[$i]['attributes'] ?? '';
        $array[$name] = self::structToArray($values, $i);

        return $array;
    }

    /**
     * Convert a PHP array into an XML string.
     *
     * @param array $array
     *
     */
    public static function arrayToXml(array $array): string|bool
    {
        $xml_info = new SimpleXMLElement('<?xml version="1.0"?><return></return>');
        self::arrToXml($array, $xml_info);

        return $xml_info->asXML();
    }

    /**
     * @return mixed[]
     */
    protected static function structToArray(mixed $values, mixed &$i): array
    {
        $child = [];
        if (isset($values[$i]['value'])) {
            $child[] = $values[$i]['value'];
        }

        while ($i++ < count($values)) {
            switch ($values[$i]['type']) {
                case 'cdata':
                    $child[] = $values[$i]['value'];
                    break;

                case 'complete':
                    $name = $values[$i]['tag'];
                    if (!empty($name)) {
                        $child[$name] = $values[$i]['attributes'] ?? (($values[$i]['value']) ?: '');
                    }
                    break;

                case 'open':
                    $name = $values[$i]['tag'];
                    $size = isset($child[$name]) ? sizeof($child[$name]) : 0;
                    $child[$name][$size] = self::structToArray($values, $i);
                    break;

                case 'close':
                    return $child;
            }
        }

        return $child;
    }

    /** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection */
    protected static function arrToXml(array $array, SimpleXMLElement &$xml): void
    {
        foreach ($array as $key => $value) {
            $key = is_numeric($key) ? "item$key" : $key;
            if (is_array($value)) {
                $subnode = $xml->addChild((string)$key);

                self::arrToXml($value, $subnode);
            } else {
                $xml->addChild((string)$key, htmlspecialchars($value, ENT_QUOTES | ENT_HTML5));
            }
        }
    }
}
