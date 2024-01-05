<?php

/**
 * A customized implementation of the Zend/Laminas Config XML reader.
 */

declare(strict_types=1);

namespace App\Xml;

use RuntimeException;
use XMLReader;

use const LIBXML_XINCLUDE;

/**
 * XML config reader.
 */
final class Reader
{
    /**
     * Nodes to handle as plain text.
     */
    private static array $textNodes = [
        XMLReader::TEXT,
        XMLReader::CDATA,
        XMLReader::WHITESPACE,
        XMLReader::SIGNIFICANT_WHITESPACE,
    ];

    public static function fromString(string $string): array|string|bool
    {
        if (empty($string)) {
            return [];
        }

        /** @var XMLReader|false $reader */
        $reader = XMLReader::XML($string, null, LIBXML_XINCLUDE);
        if (false === $reader) {
            return false;
        }

        set_error_handler(
            static function ($error, $message = '') {
                throw new RuntimeException(
                    sprintf('Error reading XML string: %s', $message),
                    $error
                );
            },
            E_WARNING
        );

        $return = self::processNextElement($reader);
        restore_error_handler();

        $reader->close();

        return $return;
    }

    private static function processNextElement(XMLReader $reader): string|array
    {
        $children = [];
        $text = '';

        while ($reader->read()) {
            // @phpstan-ignore-next-line
            if ($reader->nodeType === XMLReader::ELEMENT) {
                // @phpstan-ignore-next-line
                if ($reader->depth === 0) {
                    return self::processNextElement($reader);
                }

                $attributes = self::getAttributes($reader);
                // @phpstan-ignore-next-line
                $name = $reader->name;

                // @phpstan-ignore-next-line
                if ($reader->isEmptyElement) {
                    $child = [];
                } else {
                    $child = self::processNextElement($reader);
                }

                if ($attributes) {
                    if (is_string($child)) {
                        $child = ['_' => $child];
                    }

                    if (!is_array($child)) {
                        $child = [];
                    }

                    $child = array_merge($child, $attributes);
                }

                if (isset($children[$name])) {
                    if (!is_array($children[$name]) || !array_key_exists(0, $children[$name])) {
                        $children[$name] = [$children[$name]];
                    }

                    $children[$name][] = $child;
                } else {
                    $children[$name] = $child;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT) {
                break;
            } elseif (in_array($reader->nodeType, self::$textNodes)) {
                // @phpstan-ignore-next-line
                $text .= $reader->value;
            }
        }

        return $children ?: $text;
    }

    /**
     * Get all attributes on the current node.
     *
     * @return string[]
     */
    private static function getAttributes(XMLReader $reader): array
    {
        $attributes = [];

        // @phpstan-ignore-next-line
        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                // @phpstan-ignore-next-line
                $attributes['@' . $reader->localName] = $reader->value;
            }

            $reader->moveToElement();
        }

        return $attributes;
    }
}
