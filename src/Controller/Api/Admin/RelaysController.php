<?php
namespace App\Controller\Api\Admin;

use App\Acl;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Adapters;
use Azura\Doctrine\Repository;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class RelaysController
{
    /** @var Acl */
    protected $acl;

    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /**
     * @param Acl $acl
     * @param EntityManager $em
     * @param Adapters $adapters
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(
        Acl $acl,
        EntityManager $em,
        Adapters $adapters
    ) {
        $this->acl = $acl;
        $this->em = $em;
        $this->adapters = $adapters;
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
        $stations = $this->getManageableStations($request);

        $router = $request->getRouter();

        $return = [];
        foreach($stations as $station) {
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
        /** @var Repository $relay_repo */
        $relay_repo = $this->em->getRepository(Entity\Relay::class);

        $body = $request->getParsedBody();

        if (!empty($body['base_url'])) {
            $base_url = $body['base_url'];
        } else {
            $base_url = 'http://'.$request->getServerParam('REMOTE_ADDR');
        }

        $relay = $relay_repo->findOneBy(['base_url' => $base_url]);
        if (!$relay instanceof Entity\Relay) {
            $relay = new Entity\Relay($base_url);
        }

        $relay->setName($body['name'] ?? 'Relay');
        $relay->setIsVisibleOnPublicPages($body['is_visible_on_public_pages'] ?? true);
        $relay->setNowplaying((array)$body['nowplaying']);

        $this->em->persist($relay);

        // List existing remotes to avoid duplication.
        $existing_remotes = [];

        foreach($relay->getRemotes() as $remote) {
            /** @var Entity\StationRemote $remote */
            $existing_remotes[$remote->getStation()->getId()][$remote->getMount()] = $remote;
        }

        // Iterate through all remotes that *should* exist.
        $stations = $this->getManageableStations($request);

        foreach($stations as $station) {
            $station_id = $station->getId();

            foreach($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $mount_path = $mount->getName();

                if (isset($existing_remotes[$station_id][$mount_path])) {
                    /** @var Entity\StationRemote $remote */
                    $remote = $existing_remotes[$station_id][$mount_path];

                    unset($existing_remotes[$station_id][$mount_path]);
                } else {
                    $remote = new Entity\StationRemote($station);
                }

                $remote->setType(Adapters::REMOTE_AZURARELAY);
                $remote->setDisplayName($mount->getDisplayName().' ('.$relay->getName().')');
                $remote->setIsVisibleOnPublicPages($relay->isIsVisibleOnPublicPages());
                $remote->setUrl($relay->getBaseUrl());
                $remote->setMount($mount->getName());

                $this->em->persist($remote);
            }
        }

        // Remove all remotes that weren't processed earlier.
        foreach($existing_remotes as $existing_remote_station) {
            foreach($existing_remote_station as $existing_remote) {
                $this->em->remove($existing_remote);
            }
        }

        $this->em->flush();

        return $response->withJson(new Entity\Api\Status);
    }

    /**
     * @param Request $request
     * @return Entity\Station[]
     */
    protected function getManageableStations(Request $request): array
    {
        $all_stations = $this->em->createQuery(/** @lang DQL */'SELECT s, sm 
            FROM App\Entity\Station s 
            JOIN s.mounts sm
            WHERE s.is_enabled = 1
            AND s.frontend_type != :remote_frontend')
            ->setParameter('remote_frontend', Adapters::FRONTEND_REMOTE)
            ->execute();

        $user = $request->getUser();

        return array_filter($all_stations, function(Entity\Station $station) use ($user) {
            return $this->acl->userAllowed($user, Acl::STATION_BROADCASTING, $station->getId());
        });
    }
}
