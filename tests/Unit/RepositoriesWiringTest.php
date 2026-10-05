<?php

declare(strict_types=1);

namespace Unit;

use App\Tests\Module;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Psr\Container\ContainerInterface;
use ReflectionClass;

final class RepositoriesWiringTest extends Unit
{
    private ContainerInterface $di;

    protected function _inject(Module $testsModule): void
    {
        $this->di = $testsModule->container;
    }

    /**
     * @return array<string, array{class-string}>
     */
    public static function repositoryClassProvider(): array
    {
        $rows = [];

        foreach (glob(__DIR__ . '/../../backend/src/Entity/Repository/*.php') ?: [] as $file) {
            /** @var class-string $class */
            $class = 'App\\Entity\\Repository\\' . basename($file, '.php');

            if ((new ReflectionClass($class))->isAbstract()) {
                continue;
            }

            $rows[$class] = [$class];
        }

        return $rows;
    }

    /**
     * @param class-string $class
     */
    #[DataProvider('repositoryClassProvider')]
    public function testRepositoryResolves(string $class): void
    {
        self::assertInstanceOf($class, $this->di->get($class));
    }
}
