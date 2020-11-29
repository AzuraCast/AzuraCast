<?php

namespace App\Event;

use App\Http\ServerRequest;
use App\Notification\Notification;
use Symfony\Contracts\EventDispatcher\Event;

class GetNotifications extends Event
{
    protected ServerRequest $request;

    protected array $notifications;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
        $this->notifications = [];
    }
    
    public function getRequest(): ServerRequest
    {
        return $this->request;
    }

    /**
     * Add a new notification to the list that will be displayed.
     *
     * @param Notification $notification
     */
    public function addNotification(Notification $notification): void
    {
        $this->notifications[] = $notification;
    }

    /**
     * Retrieve the complete list of notifications that were triggered.
     *
     * @return Notification[]
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }
}
