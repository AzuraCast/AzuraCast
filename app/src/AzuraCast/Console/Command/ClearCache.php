<?php
namespace AzuraCast\Console\Command;

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
        $app_settings = $this->di->get('settings');
        if (!empty($app_settings['routerCacheFile'])) {
            @unlink($app_settings['routerCacheFile']);
        }

        $output->writeln('Router cache file cleared.');

        // Flush Doctrine cache.

        /** @var EntityManager $em */
        $em = $this->di->get('em');

        // Delete metadata, query and result caches
        $cacheDriver = $em->getConfiguration()->getMetadataCacheImpl();
        $cacheDriver->deleteAll();

        $queryCacheDriver = $em->getConfiguration()->getQueryCacheImpl();
        $queryCacheDriver->deleteAll();

        $resultCacheDriver = $em->getConfiguration()->getResultCacheImpl();
        $resultCacheDriver->deleteAll();

        $output->writeln('Doctrine ORM cache flushed.');

        // Flush remainder of local cache object
        $cache = $this->di->get('cache');
        $cache->clean();

        $output->writeln('Local cache flushed.');
    }
}