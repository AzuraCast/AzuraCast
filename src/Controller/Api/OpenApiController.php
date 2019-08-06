<?php
namespace App\Controller\Api;

use App\Version;
use Azura\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class OpenApiController
{
    /** @var Settings */
    protected $settings;

    /** @var Version */
    protected $version;

    /**
     * @param Settings $settings
     * @param Version $version
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(Settings $settings, Version $version)
    {
        $this->settings = $settings;
        $this->version = $version;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();

        $api_base_url = (string)$router->fromHere(null, [], [], true);
        $api_base_url = str_replace('/openapi.yml', '', $api_base_url);

        define('AZURACAST_API_URL', $api_base_url);
        define('AZURACAST_API_NAME', 'This AzuraCast Installation');
        define('AZURACAST_VERSION', $this->version->getVersion());

        $oa = \OpenApi\scan([
            $this->settings[Settings::BASE_DIR] . '/util/openapi.php',
            $this->settings[Settings::BASE_DIR] . '/src/Entity',
            $this->settings[Settings::BASE_DIR] . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates'
            ],
        ]);

        $yaml = $oa->toYaml();

        return $response
            ->withHeader('Content-Type', 'text/x-yaml')
            ->write($yaml);
    }
}
