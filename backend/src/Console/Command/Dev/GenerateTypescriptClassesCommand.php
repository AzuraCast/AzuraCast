<?php

declare(strict_types=1);

namespace App\Console\Command\Dev;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\TypeScript\NativeJsEnumTransformer;
use App\TypeScript\NativeJsWriter;
use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:dev:ts',
    description: 'Trigger generation of TypeScript equivalents of PHP classes.',
)]
final class GenerateTypescriptClassesCommand extends CommandAbstract
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $backendBaseDir = $this->environment->getBackendDirectory();
        $outputFile = $this->environment->getBaseDirectory() . '/frontend/entities/PhpClasses.ts';

        $config = TypeScriptTransformerConfig::create()
            ->autoDiscoverTypes($backendBaseDir)
            ->transformers([
                NativeJsEnumTransformer::class,
            ])
            ->transformToNativeEnums()
            ->writer(NativeJsWriter::class)
            ->formatter(PrettierFormatter::class)
            ->outputFile($outputFile);

        TypeScriptTransformer::create($config)->transform();

        $io->writeln('TypeScript classes generated!');
        return 0;
    }
}
