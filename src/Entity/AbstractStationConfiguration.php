<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @extends ArrayCollection<string, mixed>
 */
abstract class AbstractStationConfiguration extends ArrayCollection
{
    public function toArray(): array
    {
        $reflClass = new \ReflectionObject($this);

        $return = [];
        foreach ($reflClass->getConstants(\ReflectionClassConstant::IS_PUBLIC) as $constantVal) {
            $return[(string)$constantVal] = $this->get($constantVal);
        }
        return $return;
    }
}
