<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

trait HasLogViewer
{
    public static int $maximum_log_size = 1048576;

    protected function streamLogToResponse(
        ServerRequest $request,
        Response $response,
        string $log_path,
        bool $tail_file = true,
        array $filteredTerms = []
    ): ResponseInterface {
        clearstatcache();

        if (!is_file($log_path)) {
            throw new NotFoundException('Log file not found!');
        }

        if (!$tail_file) {
            $log = file_get_contents($log_path) ?: '';
            $log_contents = $this->processLog(
                rawLog: $log,
                filteredTerms: $filteredTerms
            );

            return $response->withJson(
                [
                    'contents' => $log_contents,
                    'eof' => true,
                ]
            );
        }

        $params = $request->getQueryParams();
        $last_viewed_size = (int)($params['position'] ?? 0);

        $log_size = filesize($log_path);
        if ($last_viewed_size > $log_size) {
            $last_viewed_size = $log_size;
        }

        $log_visible_size = ($log_size - $last_viewed_size);
        $cut_first_line = false;

        if ($log_visible_size > self::$maximum_log_size) {
            $log_visible_size = self::$maximum_log_size;
            $cut_first_line = true;
        }

        $log_contents = '';

        if ($log_visible_size > 0) {
            $fp = fopen($log_path, 'rb');
            if (false === $fp) {
                throw new RuntimeException(sprintf('Could not open file at path "%s".', $log_path));
            }

            fseek($fp, -$log_visible_size, SEEK_END);
            $log_contents_raw = fread($fp, $log_visible_size) ?: '';
            fclose($fp);

            $log_contents = $this->processLog(
                rawLog: $log_contents_raw,
                cutFirstLine: $cut_first_line,
                cutEmptyLastLine: true,
                filteredTerms: $filteredTerms
            );
        }

        return $response->withJson(
            [
                'contents' => $log_contents,
                'position' => $log_size,
                'eof' => false,
            ]
        );
    }

    private function processLog(
        string $rawLog,
        bool $cutFirstLine = false,
        bool $cutEmptyLastLine = false,
        array $filteredTerms = []
    ): string {
        $logParts = explode("\n", $rawLog);

        if ($cutFirstLine) {
            array_shift($logParts);
        }
        if ($cutEmptyLastLine && end($logParts) === '') {
            array_pop($logParts);
        }

        $log = implode("\n", $logParts);
        $log = mb_convert_encoding($log, 'UTF-8', 'UTF-8');

        return str_replace($filteredTerms, '(PASSWORD)', $log);
    }
}
