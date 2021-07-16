<?php

declare(strict_types=1);

namespace App\Event;

use App\View;
use Symfony\Contracts\EventDispatcher\Event;

class BuildView extends Event
{
    public function __construct(
        protected View $view
    ) {
    }

    public function getView(): View
    {
        return $this->view;
    }
}
