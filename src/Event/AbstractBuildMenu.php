<?php

namespace App\Event;

use App\Acl;
use App\Entity\User;
use App\Http\RouterInterface;
use App\Settings;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractBuildMenu extends Event
{
    protected Acl $acl;

    protected User $user;

    protected RouterInterface $router;

    protected Settings $settings;

    protected array $menu = [];

    public function __construct(Acl $acl, User $user, RouterInterface $router)
    {
        $this->acl = $acl;
        $this->user = $user;
        $this->router = $router;
        $this->settings = Settings::getInstance();
    }

    public function getAcl(): Acl
    {
        return $this->acl;
    }

    public function getRouter(): RouterInterface
    {
        return $this->router;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    /**
     * Add a single item to the menu.
     *
     * @param string $item_id
     * @param array $item_details
     */
    public function addItem($item_id, array $item_details): void
    {
        $this->merge([$item_id => $item_details]);
    }

    /**
     * Merge a menu subtree into the menu.
     *
     * @param array $items
     */
    public function merge(array $items): void
    {
        $this->menu = array_merge_recursive($this->menu, $items);
    }

    /**
     * @return mixed[]
     */
    public function getFilteredMenu(): array
    {
        $menu = $this->menu;

        foreach ($menu as &$item) {
            if (isset($item['items'])) {
                $item['items'] = array_filter($item['items'], [$this, 'filterMenuItem']);
            }
        }

        return array_filter($menu, [$this, 'filterMenuItem']);
    }

    /**
     * @param array $item
     */
    protected function filterMenuItem(array $item): bool
    {
        if (isset($item['items']) && empty($item['items'])) {
            return false;
        }

        if (isset($item['visible']) && !$item['visible']) {
            return false;
        }

        if (isset($item['permission']) && !$this->checkPermission($item['permission'])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $permission_name
     */
    public function checkPermission(string $permission_name): bool
    {
        return $this->acl->userAllowed($this->user, $permission_name);
    }
}
