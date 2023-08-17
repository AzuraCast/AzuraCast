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
        string $logPath,
        bool $tailFile = true,
        array $filteredTerms = []
    ): ResponseInterface {
        clearstatcache();

        if (!is_file($logPath)) {
            throw new NotFoundException('Log file not found!');
        }

        if (!$tailFile) {
            $log = file_get_contents($logPath) ?: '';
            $logContents = $this->processLog(
                rawLog: $log,
                filteredTerms: $filteredTerms
            );

            return $response->withJson(
                [
                    'contents' => $logContents,
                    'eof' => true,
                ]
            );
        }

        $params = $request->getQueryParams();
        $lastViewedSize = (int)($params['position'] ?? 0);

        $logSize = filesize($logPath);
        if ($lastViewedSize > $logSize) {
            $lastViewedSize = $logSize;
        }

        $logVisibleSize = ($logSize - $lastViewedSize);
        $cutFirstLine = false;

        if ($logVisibleSize > self::$maximum_log_size) {
            $logVisibleSize = self::$maximum_log_size;
            $cutFirstLine = true;
        }

        $logContents = '';

        if ($logVisibleSize > 0) {
            $fp = fopen($logPath, 'rb');
            if (false === $fp) {
                throw new RuntimeException(sprintf('Could not open file at path "%s".', $logPath));
            }

            fseek($fp, -$logVisibleSize, SEEK_END);
            $logContentsRaw = fread($fp, $logVisibleSize) ?: '';
            fclose($fp);

            $logContents = $this->processLog(
                rawLog: $logContentsRaw,
                cutFirstLine: $cutFirstLine,
                cutEmptyLastLine: true,
                filteredTerms: $filteredTerms
            );
        }

        return $response->withJson(
            [
                'contents' => $logContents,
                'position' => $logSize,
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
