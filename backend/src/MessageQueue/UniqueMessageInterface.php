<?php

declare(strict_types=1);

namespace App\MessageQueue;

interface UniqueMessageInterface
{
    public function getIdentifier(): string;

    public function getTtl(): ?float;
}
