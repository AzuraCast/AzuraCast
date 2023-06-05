<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Cache\AzuraRelayCache;
use App\Entity;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/internal/relays',
        operationId: 'internalGetRelayDetails',
        description: "Returns all necessary information to relay all 'relayable' stations.",
        tags: ['Administration: Relays'],
        parameters: [],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_Admin_Relay')
                )
            ),
        ]
    )
]
final class RelaysController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Adapters $adapters,
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly AzuraRelayCache $azuraRelayCache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $stations = $this->getManageableStations($request);

        $router = $request->getRouter();

        $return = [];
        foreach ($stations as $station) {
            $row = new Entity\Api\Admin\Relay();
            $row->id = $station->getIdRequired();
            $row->name = $station->getName();
            $row->shortcode = $station->getShortName();
            $row->description = $station->getDescription();
            $row->url = $station->getUrl();
            $row->genre = $station->getGenre();

            $row->type = $station->getFrontendType()->value;

            $frontend_config = $station->getFrontendConfig();
            $row->port = $frontend_config->getPort();
            $row->relay_pw = $frontend_config->getRelayPassword();
            $row->admin_pw = $frontend_config->getAdminPassword();

            $mounts = [];

            $fa = $this->adapters->getFrontendAdapter($station);
            if (null !== $fa && $station->getMounts()->count() > 0) {
                foreach ($station->getMounts() as $mount) {
                    /** @var Entity\StationMount $mount */
                    $mounts[] = $mount->api($fa);
                }
            }

            $row->mounts = $mounts;
            $row->resolveUrls($router->getBaseUrl());

            $return[] = $row;
        }

        return $response->withJson($return);
    }

    /**
     * @param ServerRequest $request
     *
     * @return Entity\Station[]
     */
    private function getManageableStations(ServerRequest $request): array
    {
        $all_stations = $this->em->createQuery(
            <<<'DQL'
                SELECT s, sm
                FROM App\Entity\Station s
                JOIN s.mounts sm
                WHERE s.is_enabled = 1
                AND s.frontend_type != :remote_frontend
            DQL
        )->setParameter('remote_frontend', FrontendAdapters::Remote->value)
            ->execute();

        $acl = $request->getAcl();

        return array_filter(
            $all_stations,
            static function (Entity\Station $station) use ($acl) {
                return $acl->isAllowed(StationPermissions::Broadcasting, $station->getId());
            }
        );
    }

    public function updateAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $relay_repo = $this->em->getRepository(Entity\Relay::class);

        $body = (array)$request->getParsedBody();

        if (!empty($body['base_url'])) {
            $base_url = $body['base_url'];
        } else {
            /** @noinspection HttpUrlsUsage */
            $base_url = 'http://' . $this->settingsRepo->readSettings()->getIp($request);
        }

        $relay = $relay_repo->findOneBy(['base_url' => $base_url]);
        if (!$relay instanceof Entity\Relay) {
            $relay = new Entity\Relay($base_url);
        }

        $relay->setName($body['name'] ?? 'Relay');
        $relay->setIsVisibleOnPublicPages($body['is_visible_on_public_pages'] ?? true);
        $relay->setUpdatedAt(time());

        $this->em->persist($relay);

        // List existing remotes to avoid duplication.
        $existing_remotes = [];

        foreach ($relay->getRemotes() as $remote) {
            /** @var Entity\StationRemote $remote */
            $existing_remotes[$remote->getStation()->getId()][$remote->getMount()] = $remote;
        }

        // Iterate through all remotes that *should* exist.
        foreach ($this->getManageableStations($request) as $station) {
            $station_id = $station->getId();

            foreach ($station->getMounts() as $mount) {
                /** @var Entity\StationMount $mount */
                $mount_path = $mount->getName();

                if (isset($existing_remotes[$station_id][$mount_path])) {
                    /** @var Entity\StationRemote $remote */
                    $remote = $existing_remotes[$station_id][$mount_path];

                    unset($existing_remotes[$station_id][$mount_path]);
                } else {
                    $remote = new Entity\StationRemote($station);
                }

                $remote->setRelay($relay);
                $remote->setType(RemoteAdapters::AzuraRelay);
                $remote->setDisplayName($mount->getDisplayName() . ' (' . $relay->getName() . ')');
                $remote->setIsVisibleOnPublicPages($relay->getIsVisibleOnPublicPages());
                $remote->setAutodjBitrate($mount->getAutodjBitrate());
                $remote->setAutodjFormat($mount->getAutodjFormat());
                $remote->setUrl($relay->getBaseUrl());
                $remote->setMount($mount->getName());

                $this->em->persist($remote);
            }
        }

        // Remove all remotes that weren't processed earlier.
        foreach ($existing_remotes as $existing_remote_station) {
            foreach ($existing_remote_station as $existing_remote) {
                $this->em->remove($existing_remote);
            }
        }

        $this->em->flush();

        $this->azuraRelayCache->setForRelay($relay, (array)$body['nowplaying']);

        return $response->withJson(Entity\Api\Status::success());
    }
}
