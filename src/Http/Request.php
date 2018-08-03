<?php
namespace App\Http;

use App\Entity\User;
use App\Exception;
use App\Mvc\View;

class Request extends \Slim\Http\Request
{
    const ATTRIBUTE_VIEW = 'view';
    const ATTRIBUTE_USER = 'user';

    /**
     * Detect if a parameter exists in the request.
     *
     * @param  string $key The parameter key.
     * @return bool Whether the key exists.
     */
    public function hasParam($key): bool
    {
        return ($this->getParam($key, null) !== null);
    }

    /**
     * Detect if an attribute exists in the request.
     *
     * @param $key
     * @return bool
     */
    public function hasAttribute($key): bool
    {
        return ($this->getAttribute($key, null) !== null);
    }

    /**
     * Get the View associated with the request, if it's set.
     * Set by @see \App\Middleware\EnableView
     *
     * @return View
     * @throws Exception
     */
    public function getView(): View
    {
        $view = $this->getAttribute(self::ATTRIBUTE_VIEW);
        if ($view instanceof View) {
            return $view;
        }

        throw new Exception('No view present in this request.');
    }

    /**
     * Get the current user associated with the request, if it's set.
     * Set by @see \App\Middleware\GetCurrentUser
     *
     * @return User
     * @throws Exception
     */
    public function getUser(): User
    {
        $user = $this->getAttribute(self::ATTRIBUTE_USER);
        if ($user instanceof User) {
            return $user;
        }

        throw new Exception('No user present in this request.');
    }
}
