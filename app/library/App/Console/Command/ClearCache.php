<?php
namespace App\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearCache extends CommandAbstract
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
        $app_settings = $this->di->get('settings');
        if (!empty($app_settings['routerCacheFile']))
            @unlink($app_settings['routerCacheFile']);

        $output->writeln('Router cache file cleared.');

        // Flush Doctrine cache.
        $em = $this->di->get('em');

        $cacheDriver = $em->getConfiguration()->getMetadataCacheImpl();
        $cacheDriver->deleteAll();

        $queryCacheDriver = $em->getConfiguration()->getQueryCacheImpl();
        $queryCacheDriver->deleteAll();

        $resultCacheDriver = $em->getConfiguration()->getResultCacheImpl();
        $resultCacheDriver->deleteAll();

        $output->writeln('Doctrine ORM cache flushed.');

        // Flush local cache.
        $cache = $this->di->get('cache');
        $cache->clean();

        $output->writeln('Local cache flushed.');
    }
}