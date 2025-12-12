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
use App\OpenApi;
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
        tags: [OpenApi::TAG_ADMIN],
        parameters: [],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: ApiRelay::class)
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

        $return = [];
        foreach ($stations as $station) {
            $row = new ApiRelay();
            $row->id = $station->id;
            $row->name = $station->name;
            $row->shortcode = $station->short_name;
            $row->description = $station->description;
            $row->url = $station->url;
            $row->genre = $station->genre;

            $row->type = $station->frontend_type->value;

            $frontendConfig = $station->frontend_config;
            $row->port = $frontendConfig->port;
            $row->relay_pw = $frontendConfig->relay_pw;
            $row->admin_pw = $frontendConfig->admin_pw;

            $mounts = [];

            $fa = $this->adapters->getFrontendAdapter($station);
            if (null !== $fa && $station->mounts->count() > 0) {
                foreach ($station->mounts as $mount) {
                    /** @var StationMount $mount */
                    $mounts[] = $mount->api($fa);
                }
            }

            $row->mounts = $mounts;

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
                return $acl->isAllowed(StationPermissions::Broadcasting, $station->id);
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

        $relay->name = $body['name'] ?? 'Relay';
        $relay->is_visible_on_public_pages = $body['is_visible_on_public_pages'] ?? true;

        $this->em->persist($relay);

        // List existing remotes to avoid duplication.
        $existingRemotes = [];

        foreach ($relay->remotes as $remote) {
            /** @var StationRemote $remote */
            if ($remote->mount !== null) {
                $existingRemotes[$remote->station->id][$remote->mount] = $remote;
            }
        }

        // Iterate through all remotes that *should* exist.
        foreach ($this->getManageableStations($request) as $station) {
            $stationId = $station->id;

            foreach ($station->mounts as $mount) {
                /** @var StationMount $mount */
                $mountPath = $mount->name;

                if (isset($existingRemotes[$stationId][$mountPath])) {
                    /** @var StationRemote $remote */
                    $remote = $existingRemotes[$stationId][$mountPath];

                    unset($existingRemotes[$stationId][$mountPath]);
                } else {
                    $remote = new StationRemote($station);
                }

                $remote->relay = $relay;
                $remote->type = RemoteAdapters::AzuraRelay;
                $remote->display_name = $mount->display_name . ' (' . $relay->name . ')';
                $remote->is_visible_on_public_pages = $relay->is_visible_on_public_pages;
                $remote->autodj_bitrate = $mount->autodj_bitrate;
                $remote->autodj_format = $mount->autodj_format;
                $remote->url = $relay->base_url;
                $remote->mount = $mount->name;

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
