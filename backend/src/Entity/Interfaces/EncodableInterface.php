<?php

declare(strict_types=1);

namespace App\Entity\Interfaces;

use App\Radio\AutoDJ\EncoderDefinition;

interface EncodableInterface
{
    public function getEncoderDefinition(): ?EncoderDefinition;
}
