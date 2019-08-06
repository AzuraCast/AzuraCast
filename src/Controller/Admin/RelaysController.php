<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Http\RequestHelper;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RelaysController
{
    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $record_repo = $this->em->getRepository(Entity\Relay::class);
        $relays = $record_repo->fetchArray(false);

        return RequestHelper::getView($request)->renderToResponse($response, 'admin/relays/index', [
            'relays' => $relays,
        ]);
    }
}
