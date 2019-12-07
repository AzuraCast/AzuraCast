<?php
namespace App\Message;

use Bernard\Message;

abstract class AbstractMessage implements Message
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }
}
