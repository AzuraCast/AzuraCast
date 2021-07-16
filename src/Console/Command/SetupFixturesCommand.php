<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Environment;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetupFixturesCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        ContainerInterface $di,
        Environment $environment
    ): int {
        $loader = new Loader();

        // Dependency-inject the fixtures and load them.
        $fixturesDir = $environment->getBaseDirectory() . '/src/Entity/Fixture';

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
            $fixture = $di->get($className);

            $loader->addFixture($fixture);
        }

        $purger = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        $io->success(__('Fixtures loaded.'));

        return 0;
    }
}
