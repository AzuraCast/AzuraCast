<?php
namespace App\Middleware;

use App\Auth;
use App\Customization;
use App\Event\BuildView;
use App\Http\Request;
use App\Http\Response;
use App\Entity;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Get the current user entity object and assign it into the request if it exists.
 */
class GetCurrentUser
{
    /** @var Auth */
    protected $auth;

    /** @var Customization */
    protected $customization;

    /** @var EventDispatcher */
    protected $dispatcher;

    public function __construct(Auth $auth, Customization $customization, EventDispatcher $dispatcher)
    {
        $this->auth = $auth;
        $this->customization = $customization;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     * @throws \App\Exception
     */
    public function __invoke(Request $request, Response $response, $next): Response
    {
        $user = ($this->auth->isLoggedIn()) ? $this->auth->getLoggedInUser() : null;

        // Initialize customization (timezones, locales, etc) based on the current logged in user.
        $this->customization->setUser($user);
        $request = $this->customization->init($request);

        $this->dispatcher->addListener(BuildView::NAME, function(BuildView $event) use ($user) {
            $event->getView()->addData([
                'user' => $user,
            ]);
        });

        $request = $request
            ->withAttribute(Request::ATTRIBUTE_USER, $user)
            ->withAttribute('is_logged_in', ($user instanceof Entity\User));

        return $next($request, $response);
    }
}
