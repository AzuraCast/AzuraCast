<?php

namespace App\Console;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

class Application extends \Silly\Edition\PhpDi\Application
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
    public function runCommandWithArgs($command, array $args = [], string $outputFile = 'php://temp'): array
    {
        $input = new ArrayInput(array_merge(['command' => $command], $args));
        $input->setInteractive(false);

        $temp_stream = fopen($outputFile, 'wb+');
        $output = new StreamOutput($temp_stream);

        $command = $this->find($command);
        $result_code = $command->run($input, $output);

        rewind($temp_stream);
        $result_output = stream_get_contents($temp_stream);
        fclose($temp_stream);

        return [
            $result_code,
            $result_output,
        ];
    }
}
