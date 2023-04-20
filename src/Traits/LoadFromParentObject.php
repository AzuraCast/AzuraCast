<?php

declare(strict_types=1);

namespace App\Traits;

trait LoadFromParentObject
{
    /**
     * @param object|array<mixed> $obj
     */
    public function fromParentObject(object|array $obj): void
    {
        if (is_object($obj)) {
            foreach (get_object_vars($obj) as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        } elseif (is_array($obj)) {
            foreach ($obj as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }
}
