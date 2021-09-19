<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Console\Application;
use App\Environment;
use App\Version;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use OpenApi\Util;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateApiDocsCommand extends CommandAbstract
{
    public function __construct(
        Application $application,
        protected Environment $environment,
        protected Version $version
    ) {
        parent::__construct($application);
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $yaml = $this->generate()->toYaml();
        $yaml_path = $this->environment->getBaseDirectory() . '/web/static/api/openapi.yml';

        file_put_contents($yaml_path, $yaml);

        $io->writeln('API documentation updated!');
        return 0;
    }

    public function generate(
        bool $useCurrentVersion = false,
        string $apiBaseUrl = 'https://demo.azuracast.com/api'
    ): OpenApi {
        define('AZURACAST_API_URL', $apiBaseUrl);
        define('AZURACAST_API_NAME', 'AzuraCast Public Demo Server');
        define(
            'AZURACAST_VERSION',
            $useCurrentVersion ? $this->version->getVersion() : Version::FALLBACK_VERSION
        );

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

        return Generator::scan($finder);
    }
}
