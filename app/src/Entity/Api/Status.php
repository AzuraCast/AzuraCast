<?php

namespace Entity\Api;

use Entity;

/**
 * @SWG\Definition(type="object")
 */
class Status
{
    public function __construct($success = true, $message = 'Changes saved successfully.')
    {
        $this->success = (bool)$success;
        $this->message = (string)$message;
    }

    /**
     * @SWG\Property(example=true)
     * @var bool
     */
    public $success;

    /**
     * @SWG\Property(example="Changes saved successfully.")
     * @var string
     */
    public $message;
}