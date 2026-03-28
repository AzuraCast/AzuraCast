<?php

declare(strict_types=1);

namespace App\Service\Vite;

final class ScriptDependencies
{
    public function __construct(
        public string $js,
        public array $css = [],
        public array $prefetch = [],
    ) {
    }
}
