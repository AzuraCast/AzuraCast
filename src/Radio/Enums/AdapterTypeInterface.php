<?php

declare(strict_types=1);

namespace App\Radio\Enums;

interface AdapterTypeInterface
{
    public function getValue(): string;

    public function getName(): string;

    /** @return class-string|null */
    public function getClass(): ?string;
}
