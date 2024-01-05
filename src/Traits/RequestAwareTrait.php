<?php

declare(strict_types=1);

namespace App\Traits;

use App\Http\ServerRequest;

trait RequestAwareTrait
{
    protected ?ServerRequest $request = null;

    public function setRequest(?ServerRequest $request): void
    {
        $this->request = $request;
    }

    public function withRequest(?ServerRequest $request): self
    {
        $newInstance = clone $this;
        $newInstance->setRequest($request);
        return $newInstance;
    }
}
