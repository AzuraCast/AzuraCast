<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

final class PostAction
{
    public function __construct(
        private readonly StereoTool $stereoTool
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $binaryPath = $this->stereoTool->getBinaryPath();
        if (is_file($binaryPath)) {
            unlink($binaryPath);
        }

        $flowResponse->moveTo($binaryPath);

        chmod($binaryPath, 0744);

        $process = new Process([$binaryPath, '--help']);
        $process->setWorkingDirectory(dirname($binaryPath));
        $process->setTimeout(5.0);
        $process->run();

        if (!$process->isSuccessful()) {
            unlink($binaryPath);
            throw new RuntimeException('Incompatible binary for StereoTool was uploaded.');
        }

        preg_match('/STEREO TOOL ([.\d]+) CONSOLE APPLICATION/i', $process->getErrorOutput(), $matches);

        if (!$matches[1]) {
            unlink($binaryPath);
            throw new RuntimeException('Unexpected help output received from Stereo Tool.');
        }

        return $response->withJson(Entity\Api\Status::success());
    }
}
