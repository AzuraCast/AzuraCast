<?php

namespace App\Traits;

trait LoadFromParentObject
{
    public function fromParentObject($obj): void
    {
        foreach (get_object_vars($obj) as $key => $value) {
            $this->$key = $value;
        }
    }
}
