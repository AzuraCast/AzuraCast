<?php
namespace App\Controller\Api\Admin;

use App\Acl;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RelaysController
{
    /** @var Acl */
    protected $acl;

    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /** @var Serializer */
    protected $serializer;

    /** @var ValidatorInterface */
    protected $validator;

    /**
     * @param Acl $acl
     * @param EntityManager $em
     * @param Adapters $adapters
     * @param Serializer $serializer
     * @param ValidatorInterface $validator
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(
        Acl $acl,
        EntityManager $em,
        Adapters $adapters,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        $this->acl = $acl;
        $this->em = $em;
        $this->adapters = $adapters;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @OA\Get(path="/admin/relays",
     *   tags={"Administration: Relays"},
     *   description="Returns all necessary information to relay all 'relayable' stations.",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_Admin_Relay"))
     *   )
     * )
     *
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $all_stations = $this->em->createQuery(/** @lang DQL */'SELECT s, sm 
            FROM App\Entity\Station s 
            JOIN s.mounts sm
            WHERE s.is_enabled = 1
            AND s.frontend_type != :remote_frontend')
            ->setParameter('remote_frontend', Adapters::FRONTEND_REMOTE)
            ->execute();

        $user = $request->getUser();

        $stations = array_filter($all_stations, function(Entity\Station $station) use ($user) {
            return $this->acl->userAllowed($user, Acl::STATION_BROADCASTING, $station->getId());
        });

        $router = $request->getRouter();

        $return = [];
        foreach($stations as $station) {
            /** @var Entity\Station $station */
            $fa = $this->adapters->getFrontendAdapter($station);

            $row = new Entity\Api\Admin\Relay;
            $row->id = $station->getId();
            $row->name = $station->getName();
            $row->shortcode = $station->getShortName();
            $row->description = $station->getDescription();
            $row->url = $station->getUrl();
            $row->genre = $station->getGenre();

            $row->type = $station->getFrontendType();

            $frontend_config = (array)$station->getFrontendConfig();
            $row->port = $frontend_config['port'] ?? null;
            $row->relay_pw = $frontend_config['relay_pw'] ?? null;
            $row->admin_pw = $frontend_config['admin_pw'] ?? null;

            $mounts = [];
            if ($station->getMounts()->count() > 0) {
                foreach ($station->getMounts() as $mount) {
                    /** @var Entity\StationMount $mount */
                    $mounts[] = $mount->api($fa);
                }
            }

            $row->mounts = $mounts;
            $row->resolveUrls($router);

            $return[] = $row;
        }

        return $response->withJson($return);
    }

    public function updateAction(Request $request, Response $response): ResponseInterface
    {

    }
}
