<?php
namespace App\Controller\Admin;

use App\Entity;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RelaysController
{
    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $record_repo = $this->em->getRepository(Entity\Relay::class);
        $relays = $record_repo->fetchArray(false);

        return \App\Http\RequestHelper::getView($request)->renderToResponse($response, 'admin/relays/index', [
            'relays' => $relays,
        ]);
    }
}
