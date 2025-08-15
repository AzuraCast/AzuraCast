<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

interface EncodableInterface
{
    public function getEncodingFormat(): ?EncodingFormat;
}
