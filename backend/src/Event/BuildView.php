<?php

declare(strict_types=1);

namespace App\Event;

use App\View;
use Symfony\Contracts\EventDispatcher\Event;

final class BuildView extends Event
{
    public function __construct(
        private readonly View $view
    ) {
    }

    public function getView(): View
    {
        return $this->view;
    }
}
