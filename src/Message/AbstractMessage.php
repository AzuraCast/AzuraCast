<?php
namespace App\Message;

use Bernard\Message;

abstract class AbstractMessage implements Message
{
    public function getName(): string
    {
        return static::class;
    }
}
