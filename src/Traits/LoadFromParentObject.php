<?php

namespace App\Traits;

trait LoadFromParentObject
{
    /**
     * @param array|object $obj
     */
    public function fromParentObject($obj): void
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
