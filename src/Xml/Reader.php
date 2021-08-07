<?php

/**
 * Extends the Zend Config XML library to allow attribute handling.
 */

declare(strict_types=1);

namespace App\Xml;

use Laminas\Config\Reader\Xml;

/**
 * XML config reader.
 */
class Reader extends Xml
{
    /**
     * Get all attributes on the current node.
     *
     * @return string[]
     */
    protected function getAttributes(): array
    {
        $attributes = [];

        if ($this->reader->hasAttributes) {
            while ($this->reader->moveToNextAttribute()) {
                $attributes['@' . $this->reader->localName] = $this->reader->value;
            }

            $this->reader->moveToElement();
        }

        return $attributes;
    }
}
