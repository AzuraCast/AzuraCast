<?php

namespace App\MessageQueue;

interface UniqueMessageInterface
{
    public function getIdentifier(): string;

    public function getTtl(): ?float;
}
