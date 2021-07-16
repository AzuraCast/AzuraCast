<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Environment;
use App\Version;
use OpenApi\Generator;
use OpenApi\Util;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateApiDocsCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Environment $environment
    ): int {
        define('AZURACAST_API_URL', 'https://demo.azuracast.com/api');
        define('AZURACAST_API_NAME', 'AzuraCast Public Demo Server');
        define('AZURACAST_VERSION', Version::FALLBACK_VERSION);

        $finder = Util::finder(
            [
                $environment->getBaseDirectory() . '/util/openapi.php',
                $environment->getBaseDirectory() . '/src/Entity',
                $environment->getBaseDirectory() . '/src/Controller/Api',
            ],
            [
                'bootstrap',
                'locale',
                'templates',
            ]
        );

        $yaml_path = $environment->getBaseDirectory() . '/web/static/api/openapi.yml';
        $yaml = (Generator::scan($finder))->toYaml();

        file_put_contents($yaml_path, $yaml);

        $io->writeln('API documentation updated!');
        return 0;
    }
}
