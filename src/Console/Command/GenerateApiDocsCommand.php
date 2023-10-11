<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Version;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use OpenApi\Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:api:docs',
    description: 'Trigger regeneration of AzuraCast API documentation.',
)]
final class GenerateApiDocsCommand extends CommandAbstract
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Version $version
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $yaml = $this->generate()?->toYaml();
        $yamlPath = $this->environment->getBaseDirectory() . '/web/static/openapi.yml';

        file_put_contents($yamlPath, $yaml);

        $io->writeln('API documentation updated!');
        return 0;
    }

    public function generate(
        bool $useCurrentVersion = false,
        string $apiBaseUrl = 'https://demo.azuracast.com/api'
    ): ?OpenApi {
        define('AZURACAST_API_URL', $apiBaseUrl);
        define('AZURACAST_API_NAME', 'AzuraCast Public Demo Server');
        define(
            'AZURACAST_VERSION',
            $useCurrentVersion ? $this->version->getVersion() : Version::FALLBACK_VERSION
        );

        $finder = Util::finder(
            [
                $this->environment->getBaseDirectory() . '/src/OpenApi.php',
                $this->environment->getBaseDirectory() . '/src/Entity',
                $this->environment->getBaseDirectory() . '/src/Controller/Api',
            ],
            [
                'bootstrap',
                'locale',
                'templates',
            ]
        );

        return Generator::scan($finder, [
            'logger' => $this->logger,
        ]);
    }
}
