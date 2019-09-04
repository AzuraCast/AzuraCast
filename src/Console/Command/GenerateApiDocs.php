<?php
namespace App\Console\Command;

use App\Version;
use Azura\Console\Command\CommandAbstract;
use Azura\Settings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function OpenApi\scan;

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
        /** @var Settings $settings */
        $settings = $this->get(Settings::class);

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

        $output->writeln('API documentation updated!');
        return 0;
    }
}


