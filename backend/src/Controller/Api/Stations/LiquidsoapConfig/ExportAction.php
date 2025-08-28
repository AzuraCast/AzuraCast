<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\LiquidsoapConfig;

use App\Controller\SingleActionInterface;
use App\Event\Radio\WriteLiquidsoapConfiguration;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Backend\Liquidsoap;
use App\Utilities\Time;
use App\Version;
use OpenApi\Attributes as OA;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

#[
    OA\Get(
        path: '/station/{station_id}/liquidsoap-config/export',
        operationId: 'exportStationLiquidsoapConfig',
        summary: 'Generate an archive of all Liquidsoap configuration on your station, including custom config.',
        tags: [OpenApi::TAG_STATIONS_BROADCASTING],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            // TODO: API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class ExportAction implements SingleActionInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Version $version,
        private Liquidsoap $liquidsoap
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $event = new WriteLiquidsoapConfiguration($station, false, false);
        $this->eventDispatcher->dispatch($event);

        // Add some debug information
        $event->prependLines([
            '# Liquidsoap Configuration Export',
            '# Exported ' . Time::nowUtc()->toAtomString(),
            '# AzuraCast ' . $this->version->getVersionText(false),
            '# Liquidsoap ' . $this->liquidsoap->getVersion(),
        ]);

        $config = $event->buildConfiguration();
        $config = $this->flattenIncludes(
            $config,
            dirname($this->liquidsoap->getConfigurationPath($station))
        );

        $config = str_replace(
            $station->getFilteredPasswords(),
            '(PASSWORD)',
            $config
        );

        $response->getBody()->write($config);

        return $response->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename=liquidsoap_' . $station->short_name . '.liq');
    }

    private function flattenIncludes(
        string $config,
        string $baseDir
    ): string {
        preg_match_all(
            '/^%include \"([a-zA-Z\/\.]*)\"$/m',
            $config,
            $matches,
            PREG_SET_ORDER
        );

        if (empty($matches)) {
            return $config;
        }

        $fsUtils = new Filesystem();

        foreach ($matches as $match) {
            try {
                $path = Path::makeAbsolute(
                    $match[1],
                    $baseDir
                );

                $contents = $this->flattenIncludes(
                    $fsUtils->readFile($path),
                    dirname($path)
                );

                $newConfig = <<<EOF
                # Imported from {$path}
                # startimport({$path})
                {$contents}
                # endimport({$path})
                EOF;

                $config = str_replace(
                    $match[0],
                    $newConfig,
                    $config
                );
            } catch (\Throwable) {
            }
        }

        return $config;
    }
}
