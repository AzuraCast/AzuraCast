<?php

/**
 * Extends the Zend Config XML library to allow attribute handling.
 */

declare(strict_types=1);

namespace App\Xml;

use Laminas\Config\Exception;
use Laminas\Config\Writer\Xml;
use Laminas\Stdlib\ArrayUtils;
use Traversable;
use XMLWriter;

class Writer extends Xml
{
    /**
     * toString(): defined by Writer interface.
     *
     * @param mixed $config
     * @param string $base_element
     *
     * @see WriterInterface::toString()
     */
    public function toString($config, $base_element = 'zend-config'): string
    {
        if ($config instanceof Traversable) {
            $config = ArrayUtils::iteratorToArray($config);
        } elseif (!is_array($config)) {
            throw new Exception\InvalidArgumentException(__METHOD__ . ' expects an array or Traversable config');
        }

        return $this->processConfig($config, $base_element);
    }

    /**
     * processConfig(): defined by AbstractWriter.
     *
     * @param array $config
     * @param string $base_element
     */
    public function processConfig(array $config, $base_element = 'zend-config'): string
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($base_element);

        // Make sure attributes come first
        uksort($config, [$this, 'attributesFirst']);

        foreach ($config as $sectionName => $data) {
            if (!is_array($data)) {
                if (str_starts_with($sectionName, '@')) {
                    $writer->writeAttribute(substr($sectionName, 1), (string)$data);
                } else {
                    $writer->writeElement($sectionName, (string)$data);
                }
            } else {
                $this->addBranch($sectionName, $data, $writer);
            }
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    /**
     * Add a branch to an XML object recursively.
     *
     * @param string $branchName
     * @param array $config
     * @param XMLWriter $writer
     *
     * @throws Exception\RuntimeException
     */
    protected function addBranch($branchName, array $config, XMLWriter $writer): void
    {
        $branchType = null;

        // Ensure attributes come first.
        uksort($config, [$this, 'attributesFirst']);

        foreach ($config as $key => $value) {
            if ($branchType === null) {
                if (is_numeric($key)) {
                    $branchType = 'numeric';
                } else {
                    $writer->startElement($branchName);
                    $branchType = 'string';
                }
            } elseif ($branchType !== (is_numeric($key) ? 'numeric' : 'string')) {
                throw new Exception\RuntimeException('Mixing of string and numeric keys is not allowed');
            }

            if ($branchType === 'numeric') {
                if (is_array($value)) {
                    $this->addBranch($branchName, $value, $writer);
                } else {
                    $writer->writeElement($branchName, (string)$value);
                }
            } else {
                /** @var string $key */
                if (is_array($value)) {
                    $this->addBranch($key, $value, $writer);
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

    protected function attributesFirst(mixed $a, mixed $b): int
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
