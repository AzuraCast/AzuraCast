<?php
namespace App\Console\Command;

use Azura\Console\Command\CommandAbstract;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SetupFixtures extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:setup:fixtures')
            ->setDescription('Install fixtures for demo / local dev.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $loader = new Loader();
        $loader->loadFromDirectory(APP_INCLUDE_ROOT.'/src/Entity/Fixture');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        $output->writeln('Fixtures loaded.');

        return 0;
    }
}
