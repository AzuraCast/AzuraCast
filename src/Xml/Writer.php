<?php

/**
 * Customized implementation of the Zend/Laminas Config XML Writer object.
 */

declare(strict_types=1);

namespace App\Xml;

use RuntimeException;
use XMLWriter;

final class Writer
{
    public static function toString(
        array $config,
        string $baseElement = 'xml-config'
    ): string {
        return self::processConfig($config, $baseElement);
    }

    private static function processConfig(
        array $config,
        string $baseElement = 'xml-config'
    ): string {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($baseElement);

        // Make sure attributes come first
        uksort($config, [self::class, 'attributesFirst']);

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
        $branchType = null;

        // Ensure attributes come first.
        uksort($config, [self::class, 'attributesFirst']);

        foreach ($config as $key => $value) {
            if ($branchType === null) {
                if (is_numeric($key)) {
                    $branchType = 'numeric';
                } else {
                    $writer->startElement($branchName);
                    $branchType = 'string';
                }
            } elseif ($branchType !== (is_numeric($key) ? 'numeric' : 'string')) {
                throw new RuntimeException('Mixing of string and numeric keys is not allowed');
            }

            if ($branchType === 'numeric') {
                if (is_array($value)) {
                    self::addBranch($branchName, $value, $writer);
                } else {
                    $writer->writeElement($branchName, (string)$value);
                }
            } else {
                /** @var string $key */
                if (is_array($value)) {
                    self::addBranch($key, $value, $writer);
                } elseif (str_starts_with($key, '@')) {
                    $writer->writeAttribute(substr($key, 1), (string)$value);
                } else {
                    $writer->writeElement($key, (string)$value);
                }
            }
        }

        if ($branchType === 'string') {
            $writer->endElement();
        }
    }

    private static function attributesFirst(mixed $a, mixed $b): int
    {
        if (str_starts_with((string)$a, '@')) {
            return -1;
        }
        if (str_starts_with((string)$b, '@')) {
            return 1;
        }
        return 0;
    }
}
