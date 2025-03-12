<?php

declare(strict_types=1);

namespace App\Console\Command\Dev;

use App\Console\Command\CommandAbstract;
use App\Container\EnvironmentAwareTrait;
use App\Entity\Attributes\StableMigration;
use App\Utilities\Types;
use DirectoryIterator;
use LogicException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'azuracast:new-version',
    description: 'Update the codebase in preparation for a new stable release version.',
)]
final class NewVersionCommand extends CommandAbstract
{
    use EnvironmentAwareTrait;

    protected function configure(): void
    {
        $this->addArgument('version', InputArgument::REQUIRED, 'The new version string.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $version = Types::string($input->getArgument('version'));

        $io->title(sprintf('Preparing codebase for release of stable version "%s"...', $version));

        $io->section('Update Version file and API docs.');

        $this->updateVersionFile($io, $version);

        $this->runCommand(
            $output,
            'azuracast:api:docs',
            [
                '--api-version' => $version,
            ]
        );

        $io->section('Add attribute to latest DB migration.');

        $this->addAttributeToLatestMigration($io, $version);

        $io->section('Update changelog.');

        $this->updateChangelog($io, $version);

        $io->success(
            'New version tagged successfully! Merge this branch with "stable", then push a new lightweight tag onto ' .
            'the "stable" branch. Remember to switch back to the "main" branch for development!'
        );

        return 0;
    }

    private function updateVersionFile(SymfonyStyle $io, string $version): void
    {
        $versionFile = $this->environment->getBackendDirectory() . '/src/Version.php';

        $fsUtils = new Filesystem();

        $fileObj = PhpFile::fromCode($fsUtils->readFile($versionFile));

        $classObj = $this->getFirstClassInFile($fileObj);
        $classObj->getConstant('STABLE_VERSION')->setValue($version);

        $fsUtils->dumpFile($versionFile, (new PsrPrinter())->printFile($fileObj));
    }

    private function addAttributeToLatestMigration(SymfonyStyle $io, string $version): void
    {
        $migrationPath = $this->getLatestMigration();

        $fsUtils = new Filesystem();

        $fileObj = PhpFile::fromCode($fsUtils->readFile($migrationPath));

        $classObj = $this->getFirstClassInFile($fileObj);
        $classObj->addAttribute(
            StableMigration::class,
            [$version]
        );

        $fsUtils->dumpFile($migrationPath, (new PsrPrinter())->printFile($fileObj));
    }

    private function getFirstClassInFile(PhpFile $fileObj): ClassType
    {
        foreach ($fileObj->getClasses() as $class) {
            if ($class instanceof ClassType) {
                return $class;
            }
        }

        throw new LogicException('No class detected in file.');
    }

    private function getLatestMigration(): string
    {
        $migrationsDir = $this->environment->getBackendDirectory() . '/src/Entity/Migration';

        $migrationsByPath = [];
        foreach (new DirectoryIterator($migrationsDir) as $file) {
            if ($file->isDot() || !$file->isFile()) {
                continue;
            }

            $pathBase = $file->getBasename('.php');

            if (str_starts_with($pathBase, 'Version')) {
                $pathBase = str_replace('Version', '', $pathBase);
                $migrationsByPath[$pathBase] = $file->getPathname();
            }
        }

        krsort($migrationsByPath);
        $latestMigration = reset($migrationsByPath);

        if (false === $latestMigration) {
            throw new LogicException('Cannot find latest migration!');
        }

        return $latestMigration;
    }

    private function updateChangelog(SymfonyStyle $io, string $version): void
    {
        $changelogPath = $this->environment->getBaseDirectory() . '/CHANGELOG.md';

        $fsUtils = new Filesystem();

        $changelog = $fsUtils->readFile($changelogPath);

        $hasNewHeader = false;
        $newChangelogLines = [];

        foreach (explode("\n", $changelog) as $changelogLine) {
            // Insert new version before first subheading.
            if (!$hasNewHeader && str_starts_with($changelogLine, '##')) {
                $newChangelogLines[] = '## New Features/Changes';
                $newChangelogLines[] = '';
                $newChangelogLines[] = '## Code Quality/Technical Changes';
                $newChangelogLines[] = '';
                $newChangelogLines[] = '## Bug Fixes';
                $newChangelogLines[] = '';
                $newChangelogLines[] = '---';
                $newChangelogLines[] = '';
                $newChangelogLines[] = '# AzuraCast ' . $version . ' (' . date('M j, Y') . ')';
                $newChangelogLines[] = '';

                $hasNewHeader = true;
            }

            $newChangelogLines[] = $changelogLine;
        }

        $fsUtils->dumpFile($changelogPath, implode("\n", $newChangelogLines));
    }
}
