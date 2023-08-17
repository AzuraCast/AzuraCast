<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\ContainerAwareTrait;
use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:setup:fixtures',
    description: 'Install fixtures for demo / local development.',
)]
final class SetupFixturesCommand extends CommandAbstract
{
    use ContainerAwareTrait;
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $loader = new Loader();

        // Dependency-inject the fixtures and load them.
        $fixturesDir = $this->environment->getBaseDirectory() . '/src/Entity/Fixture';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fixturesDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            // Skip dotfiles
            if (($fileName = $file->getBasename('.php')) == $file->getBasename()) {
                continue;
            }

            $className = 'App\\Entity\\Fixture\\' . $fileName;
            $fixture = $this->di->get($className);

            $loader->addFixture($fixture);
        }

        $purger = new ORMPurger($this->em);
        $executor = new ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());

        $io->success(__('Fixtures loaded.'));

        return 0;
    }
}
