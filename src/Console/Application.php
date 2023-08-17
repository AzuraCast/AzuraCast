<?php

declare(strict_types=1);

namespace App\Console;

use RuntimeException;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

final class Application extends SymfonyApplication
{
    /**
     * Run a one-off command from elsewhere in the application, and pass through the results.
     *
     * @param string $command
     * @param array $args
     * @param string $outputFile
     *
     * @return mixed[] [int $return_code, string $return_output]
     */
    public function runCommandWithArgs(string $command, array $args = [], string $outputFile = 'php://temp'): array
    {
        $input = new ArrayInput(array_merge(['command' => $command], $args));
        $input->setInteractive(false);

        $tempStream = fopen($outputFile, 'wb+');
        if (false === $tempStream) {
            throw new RuntimeException(sprintf('Could not open output file: "%s"', $outputFile));
        }

        $output = new StreamOutput($tempStream);

        $resultCode = $this->find($command)->run($input, $output);

        rewind($tempStream);
        $resultOutput = stream_get_contents($tempStream);
        fclose($tempStream);

        $resultOutput = trim((string)$resultOutput);

        return [
            $resultCode,
            $resultOutput,
        ];
    }
}
