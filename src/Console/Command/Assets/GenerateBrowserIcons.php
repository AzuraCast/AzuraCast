<?php

declare(strict_types=1);

namespace App\Console\Command\Assets;

use App\Customization\BrowserIcons;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateBrowserIcons
{
    public function __invoke(
        SymfonyStyle $io,
        string $original,
        ?string $outputDir,
        BrowserIcons $browserIcons
    ): int {
        try {
            $browserIcons->makeIcons($original, $outputDir);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $io->success('Browser icons generated.');
        return 0;
    }
}
