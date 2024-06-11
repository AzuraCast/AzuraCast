<?php

declare(strict_types=1);

namespace App\Message;

use App\MessageQueue\UniqueMessageInterface;

abstract class AbstractUniqueMessage extends AbstractMessage implements UniqueMessageInterface
{
    public function getIdentifier(): string
    {
        $staticClassParts = explode("\\", static::class);
        $staticClass = array_pop($staticClassParts);

        return $staticClass . '_' . md5(serialize($this));
    }

    public function getTtl(): ?float
    {
        return 60;
    }
}
