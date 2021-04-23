<?php

namespace App\Traits;

trait LoadFromParentObject
{
    public function fromParentObject(object|array $obj): void
    {
        if (is_object($obj)) {
            foreach (get_object_vars($obj) as $key => $value) {
                $this->$key = $value;
            }
        } elseif (is_array($obj)) {
            foreach ($obj as $key => $value) {
                $this->$key = $value;
            }
        }
    }
}
