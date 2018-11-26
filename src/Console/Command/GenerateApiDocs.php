<?php

namespace App\Console\Command;

use App\Entity;
use App\Version;
use Azura\Console\Command\CommandAbstract;
use Azura\Settings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateApiDocs extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:api:docs')
            ->setDescription('Trigger regeneration of AzuraCast API documentation.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Version $version */
        $version = $this->get(Version::class);

        /** @var Settings $settings */
        $settings = $this->get(Settings::class);

        define('AZURACAST_VERSION', $version->getVersion());
        define('SAMPLE_TIMESTAMP', rand(time() - 86400, time() + 86400));

        $oa = \OpenApi\scan([
            $settings[Settings::BASE_DIR] . '/util/openapi.php',
            $settings[Settings::BASE_DIR] . '/src/Entity/Api',
            $settings[Settings::BASE_DIR] . '/src/Controller/Api',
        ], [
            'exclude' => [
                'bootstrap',
                'locale',
                'templates'
            ],
        ]);

        $yaml_path = $settings[Settings::BASE_DIR].'/web/static/api/openapi.yml';
        $yaml = $oa->toYaml();

        file_put_contents($yaml_path, $yaml);

        $output->writeln('API documentation updated!');
        return 0;
    }
}


