<?php
namespace App\Controller\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

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

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $record_repo = $this->em->getRepository(Entity\Relay::class);
        $relays = $record_repo->fetchArray(false);

        return $request->getView()->renderToResponse($response, 'admin/relays/index', [
            'relays' => $relays,
        ]);
    }
}
