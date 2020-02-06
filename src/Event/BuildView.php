<?php
namespace App\Event;

use App\View;
use Symfony\Contracts\EventDispatcher\Event;

class BuildView extends Event
{
    protected $view;

    public function __construct(View $view)
    {
        $this->view = $view;
    }

    public function getView(): View
    {
        return $this->view;
    }
}
