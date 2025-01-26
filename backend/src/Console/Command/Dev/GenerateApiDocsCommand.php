<?php

declare(strict_types=1);

namespace App\Console\Command\Dev;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Utilities\Types;
use App\Version;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use OpenApi\Util;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this->addOption(
            'api-version',
            null,
            InputOption::VALUE_REQUIRED,
            'The version to tag, if different from the default.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = Types::stringOrNull($input->getOption('api-version'));

        $io = new SymfonyStyle($input, $output);

        $yaml = $this->generate($version)?->toYaml();
        $yamlPath = $this->environment->getBaseDirectory() . '/web/static/openapi.yml';

        file_put_contents($yamlPath, $yaml);

        $io->writeln('API documentation updated!');
        return 0;
    }

    public function generate(
        string $version = null,
        string $apiBaseUrl = 'https://demo.azuracast.com/api'
    ): ?OpenApi {
        define('AZURACAST_API_URL', $apiBaseUrl);
        define('AZURACAST_API_NAME', 'AzuraCast Public Demo Server');
        define('AZURACAST_VERSION', $version ?? Version::STABLE_VERSION);

        $finder = Util::finder(
            [
                $this->environment->getBackendDirectory() . '/src/OpenApi.php',
                $this->environment->getBackendDirectory() . '/src/Entity',
                $this->environment->getBackendDirectory() . '/src/Controller/Api',
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
