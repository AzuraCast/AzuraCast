<?php
namespace App\Event;

use App\Entity\User;
use App\Notification\Notification;
use Symfony\Contracts\EventDispatcher\Event;

class GetNotifications extends Event
{
    protected User $current_user;

    protected array $notifications;

    public function __construct(User $current_user)
    {
        $this->current_user = $current_user;
        $this->notifications = [];
    }

    public function getCurrentUser(): User
    {
        return $this->current_user;
    }

    /**
     * Add a new notification to the list that will be displayed.
     *
     * @param Notification $notification
     */
    public function addNotification(Notification $notification)
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
