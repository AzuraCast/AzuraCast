<?php

namespace App\Console\Command;

use App\Entity;
use Azura\Console\Command\CommandAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiDocs extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:api:generate-docs')
            ->setDescription('Trigger regeneration of AzuraCast API documentation.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        define('AZURACAST_VERSION', \App\Version::getVersion());
        define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

        $oa = \OpenApi\scan([
            APP_INCLUDE_ROOT . '/util/openapi.php',
            APP_INCLUDE_ROOT . '/src/Entity/Api',
            APP_INCLUDE_ROOT . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates'
            ],
        ]);

        $yaml_path = APP_INCLUDE_STATIC.'/api/openapi.yml';
        $yaml = $oa->toYaml();

        file_put_contents($yaml_path, $yaml);

        $output->writeln('API documentation updated!');
        return 0;
    }
}


