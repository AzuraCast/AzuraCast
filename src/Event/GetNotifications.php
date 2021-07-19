<?php

declare(strict_types=1);

namespace App\Event;

use App\Entity\Api\Notification;
use App\Http\ServerRequest;
use Symfony\Contracts\EventDispatcher\Event;

class GetNotifications extends Event
{
    protected array $notifications = [];

    public function __construct(
        protected ServerRequest $request
    ) {
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
