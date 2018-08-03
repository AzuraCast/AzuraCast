<?php
/**
 * Extends the Zend Config XML library to allow attribute handling.
 */

namespace App\Xml;

use Traversable;
use XMLWriter;
use Zend\Config\Exception;
use Zend\Stdlib\ArrayUtils;

class Writer extends \Zend\Config\Writer\Xml
{
    /**
     * toString(): defined by Writer interface.
     *
     * @see    WriterInterface::toString()
     * @param  mixed $config
     * @param string $base_element
     * @return string
     */
    public function toString($config, $base_element = 'zend-config')
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
     * @param  array $config
     * @param string $base_element
     * @return string
     */
    public function processConfig(array $config, $base_element = 'zend-config')
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        $writer->setIndentString(str_repeat(' ', 4));

        $writer->startDocument('1.0', 'UTF-8');
        $writer->startElement($base_element);

        // Make sure attributes come first
        uksort($config, [$this, '_attributesFirst']);

        foreach ($config as $sectionName => $data) {
            if (!is_array($data)) {
                if (substr($sectionName, 0, 1) == '@') {
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
     * @param  string $branchName
     * @param  array $config
     * @param  XMLWriter $writer
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function addBranch($branchName, array $config, XMLWriter $writer)
    {
        $branchType = null;

        // Ensure attributes come first.
        uksort($config, [$this, '_attributesFirst']);

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
                if (is_array($value)) {
                    $this->addBranch($key, $value, $writer);
                } else {
                    if (substr($key, 0, 1) == '@') {
                        $writer->writeAttribute(substr($key, 1), (string)$value);
                    } else {
                        $writer->writeElement($key, (string)$value);
                    }
                }
            }
        }

        if ($branchType === 'string') {
            $writer->endElement();
        }
    }

    protected function _attributesFirst($a, $b) {
        if (substr($a, 0, 1) == '@') {
            return -1;
        } else if (substr($b, 0, 1) == '@') {
            return 1;
        } else {
            return 0;
        }
    }
}
