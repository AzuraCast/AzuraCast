<?php

namespace AzuraCast\Console\Command;

use Entity;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiDocs extends \App\Console\Command\CommandAbstract
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
        define('AZURACAST_VERSION', \AzuraCast\Version::getVersion());
        define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

        $swagger = \Swagger\scan([
            APP_INCLUDE_ROOT . '/util/swagger.php',
            APP_INCLUDE_BASE . '/src/Entity/Api',
            APP_INCLUDE_BASE . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates'
            ],
        ]);

        file_put_contents(APP_INCLUDE_STATIC . '/api/swagger.json', $swagger);

        return $output->writeln('API documentation updated!');
    }
}


