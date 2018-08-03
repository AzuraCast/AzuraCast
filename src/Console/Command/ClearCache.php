<?php
namespace App\Console\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCache extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('cache:clear')
            ->setDescription('Clear all application caches.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Flush route cache.
        $app_settings = $this->get('settings');
        if (!empty($app_settings['routerCacheFile'])) {
            @unlink($app_settings['routerCacheFile']);
        }

        $output->writeln('Router cache file cleared.');

        // Flush all Redis databases
        /** @var \Redis $redis */
        $redis = $this->get(\Redis::class);

        for($i = 0; $i < 14; $i++) {
            $redis->select($i);
            $redis->flushAll();
        }

        $output->writeln('Local cache flushed.');
        return 0;
    }
}
