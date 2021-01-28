<?php

namespace App\Entity;

interface ProcessableMediaInterface
{
    public function needsReprocessing(int $currentFileModifiedTime = 0): bool;
}
