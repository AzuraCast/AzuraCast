<?php

/**
 * Customized implementation of the Zend/Laminas Config XML Writer object.
 */

declare(strict_types=1);

namespace App\Xml;

use XMLWriter;

final class Writer
{
    public static function toString(
        array $config,
        string $baseElement = 'xml-config',
        bool $includeOpeningTag = true
    ): string {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        if ($includeOpeningTag) {
            $writer->startDocument('1.0', 'UTF-8');
        }

        $writer->startElement($baseElement);

        foreach ($config as $sectionName => $data) {
            if (!is_array($data)) {
                if (str_starts_with($sectionName, '@')) {
                    $writer->writeAttribute(substr($sectionName, 1), (string)$data);
                } else {
                    $writer->writeElement($sectionName, (string)$data);
                }
            } else {
                self::addBranch($sectionName, $data, $writer);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private static function addBranch(
        mixed $branchName,
        array $config,
        XMLWriter $writer
    ): void {
        $attributes = [];
        $innerText = null;

        foreach ($config as $key => $value) {
            if (str_starts_with((string)$key, '@')) {
                $attributes[substr($key, 1)] = (string)$value;
                unset($config[$key]);
            } else {
                if ('_' === $key) {
                    $innerText = (string)$value;
                    unset($config[$key]);
                }
            }
        }

        if (0 !== count($config) && array_is_list($config)) {
            foreach ($config as $value) {
                if (is_array($value)) {
                    self::addBranch($branchName, $value, $writer);
                } else {
                    $writer->writeElement($branchName, (string)$value);
                }
            }
        } else {
            $writer->startElement($branchName);

            foreach ($attributes as $attrKey => $attrVal) {
                $writer->writeAttribute($attrKey, $attrVal);
            }

            if (null !== $innerText) {
                $writer->text($innerText);
            }

            foreach ($config as $key => $value) {
                /** @var string $key */
                if (is_array($value)) {
                    self::addBranch($key, $value, $writer);
                } else {
                    $writer->writeElement($key, (string)$value);
                }
            }

            $writer->endElement();
        }
    }
}
