<?php

namespace App\Console\Command;

use App\Settings;
use App\Version;
use Symfony\Component\Console\Style\SymfonyStyle;

use function OpenApi\scan;

class GenerateApiDocsCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Settings $settings
    ): int {
        define('AZURACAST_API_URL', 'https://demo.azuracast.com/api');
        define('AZURACAST_API_NAME', 'AzuraCast Public Demo Server');
        define('AZURACAST_VERSION', Version::FALLBACK_VERSION);

        $oa = scan([
            $settings[Settings::BASE_DIR] . '/util/openapi.php',
            $settings[Settings::BASE_DIR] . '/src/Entity',
            $settings[Settings::BASE_DIR] . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates',
            ],
        ]);

        $yaml_path = $settings[Settings::BASE_DIR] . '/web/static/api/openapi.yml';
        $yaml = $oa->toYaml();

        file_put_contents($yaml_path, $yaml);

        $io->writeln('API documentation updated!');
        return 0;
    }
}
