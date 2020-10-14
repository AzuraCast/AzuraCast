<?php

namespace App\Controller\Api;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Settings;
use App\Version;
use Psr\Http\Message\ResponseInterface;

use function OpenApi\scan;

class OpenApiController
{
    protected Settings $settings;

    protected Version $version;

    public function __construct(Settings $settings, Version $version)
    {
        $this->settings = $settings;
        $this->version = $version;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();

        $api_base_url = $router->fromHere(null, [], [], true);
        $api_base_url = str_replace('/openapi.yml', '', $api_base_url);

        define('AZURACAST_API_URL', $api_base_url);
        define('AZURACAST_API_NAME', 'This AzuraCast Installation');
        define('AZURACAST_VERSION', $this->version->getVersion());

        $oa = scan([
            $this->settings[Settings::BASE_DIR] . '/util/openapi.php',
            $this->settings[Settings::BASE_DIR] . '/src/Entity',
            $this->settings[Settings::BASE_DIR] . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates',
            ],
        ]);

        $yaml = $oa->toYaml();

        $response->getBody()->write($yaml);
        return $response->withHeader('Content-Type', 'text/x-yaml');
    }
}
