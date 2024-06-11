<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Cache\AzuraRelayCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Entity\Api\Admin\Relay as ApiRelay;
use App\Entity\Api\Status;
use App\Entity\Relay;
use App\Entity\Station;
use App\Entity\StationMount;
use App\Entity\StationRemote;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use App\Radio\Enums\FrontendAdapters;
use App\Radio\Enums\RemoteAdapters;
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
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Adapters $adapters,
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
            $row = new ApiRelay();
            $row->id = $station->getIdRequired();
            $row->name = $station->getName();
            $row->shortcode = $station->getShortName();
            $row->description = $station->getDescription();
            $row->url = $station->getUrl();
            $row->genre = $station->getGenre();

            $row->type = $station->getFrontendType()->value;

            $frontendConfig = $station->getFrontendConfig();
            $row->port = $frontendConfig->getPort();
            $row->relay_pw = $frontendConfig->getRelayPassword();
            $row->admin_pw = $frontendConfig->getAdminPassword();

            $mounts = [];

            $fa = $this->adapters->getFrontendAdapter($station);
            if (null !== $fa && $station->getMounts()->count() > 0) {
                foreach ($station->getMounts() as $mount) {
                    /** @var StationMount $mount */
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
     * @return Station[]
     */
    private function getManageableStations(ServerRequest $request): array
    {
        $allStations = $this->em->createQuery(
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
            $allStations,
            static function (Station $station) use ($acl) {
                return $acl->isAllowed(StationPermissions::Broadcasting, $station->getId());
            }
        );
    }

    public function updateAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $relayRepo = $this->em->getRepository(Relay::class);

        $body = (array)$request->getParsedBody();

        if (!empty($body['base_url'])) {
            $baseUrl = $body['base_url'];
        } else {
            /** @noinspection HttpUrlsUsage */
            $baseUrl = 'http://' . $this->readSettings()->getIp($request);
        }

        $relay = $relayRepo->findOneBy(['base_url' => $baseUrl]);
        if (!$relay instanceof Relay) {
            $relay = new Relay($baseUrl);
        }

        $relay->setName($body['name'] ?? 'Relay');
        $relay->setIsVisibleOnPublicPages($body['is_visible_on_public_pages'] ?? true);
        $relay->setUpdatedAt(time());

        $this->em->persist($relay);

        // List existing remotes to avoid duplication.
        $existingRemotes = [];

        foreach ($relay->getRemotes() as $remote) {
            /** @var StationRemote $remote */
            $existingRemotes[$remote->getStation()->getId()][$remote->getMount()] = $remote;
        }

        // Iterate through all remotes that *should* exist.
        foreach ($this->getManageableStations($request) as $station) {
            $stationId = $station->getId();

            foreach ($station->getMounts() as $mount) {
                /** @var StationMount $mount */
                $mountPath = $mount->getName();

                if (isset($existingRemotes[$stationId][$mountPath])) {
                    /** @var StationRemote $remote */
                    $remote = $existingRemotes[$stationId][$mountPath];

                    unset($existingRemotes[$stationId][$mountPath]);
                } else {
                    $remote = new StationRemote($station);
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
        foreach ($existingRemotes as $existingRemoteStation) {
            foreach ($existingRemoteStation as $existingRemote) {
                $this->em->remove($existingRemote);
            }
        }

        $this->em->flush();

        $this->azuraRelayCache->setForRelay($relay, (array)$body['nowplaying']);

        return $response->withJson(Status::success());
    }
}
