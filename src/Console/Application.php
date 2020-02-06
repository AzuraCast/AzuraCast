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
     *
     * @return array [$return_code, $return_output]
     */
    public function runCommandWithArgs($command, array $args = []): array
    {
        $input = new ArrayInput(array_merge(['command' => $command], $args));
        $input->setInteractive(false);

        $temp_stream = fopen('php://temp', 'w+');
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
