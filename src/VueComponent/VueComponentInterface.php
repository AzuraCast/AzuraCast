<?php

declare(strict_types=1);

namespace App\VueComponent;

use App\Http\ServerRequest;

interface VueComponentInterface
{
    public function getProps(ServerRequest $request): array;
}
