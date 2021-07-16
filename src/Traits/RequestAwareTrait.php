<?php

declare(strict_types=1);

namespace App\Traits;

use Psr\Http\Message\ServerRequestInterface;

trait RequestAwareTrait
{
    protected ?ServerRequestInterface $request = null;

    public function setRequest(?ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function withRequest(?ServerRequestInterface $request): self
    {
        $newInstance = clone $this;
        $newInstance->setRequest($request);
        return $newInstance;
    }
}
