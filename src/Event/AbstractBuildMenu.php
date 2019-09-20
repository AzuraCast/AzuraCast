<?php
namespace App\Event;

use App\Acl;
use App\Entity\User;
use App\Http\Router;
use Azura\Http\RouterInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractBuildMenu extends Event
{
    /** @var Acl */
    protected $acl;

    /** @var User */
    protected $user;

    /** @var RouterInterface */
    protected $router;

    /** @var array */
    protected $menu = [];

    /**
     * @param Acl $acl
     * @param User $user
     * @param RouterInterface $router
     */
    public function __construct(Acl $acl, User $user, RouterInterface $router)
    {
        $this->acl = $acl;
        $this->user = $user;
        $this->router = $router;
    }

    /**
     * @return Acl
     */
    public function getAcl(): Acl
    {
        return $this->acl;
    }

    /**
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
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
     * @return array
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
     *
     * @return bool
     */
    protected function filterMenuItem(array $item): bool
    {
        if (isset($item['items']) && empty($item['items'])) {
            return false;
        }

        if (isset($item['visible'])) {
            return (bool)$item['visible'];
        }

        if (isset($item['permission'])) {
            return $this->checkPermission($item['permission']);
        }

        return true;
    }

    /**
     * @param string $permission_name
     *
     * @return bool
     */
    public function checkPermission(string $permission_name): bool
    {
        return $this->acl->userAllowed($this->user, $permission_name);
    }
}
