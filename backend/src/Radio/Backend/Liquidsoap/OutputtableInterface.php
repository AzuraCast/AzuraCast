<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap;

interface OutputtableInterface
{
    public function getOutputtableSource(): ?OutputtableSource;
}
