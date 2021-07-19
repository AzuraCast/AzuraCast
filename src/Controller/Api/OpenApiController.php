<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use OpenApi\Generator;
use OpenApi\Util;
use Psr\Http\Message\ResponseInterface;

class OpenApiController
{
    public function __construct(
        protected Environment $environment,
        protected Version $version
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $api_base_url = (string)$request->getRouter()->fromHere(absolute: true);
        $api_base_url = str_replace('/openapi.yml', '', $api_base_url);

        define('AZURACAST_API_URL', $api_base_url);
        define('AZURACAST_API_NAME', 'This AzuraCast Installation');
        define('AZURACAST_VERSION', $this->version->getVersion());

        $finder = Util::finder(
            [
                $this->environment->getBaseDirectory() . '/util/openapi.php',
                $this->environment->getBaseDirectory() . '/src/Entity',
                $this->environment->getBaseDirectory() . '/src/Controller/Api',
            ],
            [
                'bootstrap',
                'locale',
                'templates',
            ]
        );

        $yaml = (Generator::scan($finder))->toYaml();

        $response->getBody()->write($yaml);
        return $response->withHeader('Content-Type', 'text/x-yaml');
    }
}
