<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Annotations;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;

final class NextSongAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Annotations $annotations
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $testHandler = new TestHandler(Level::Debug, false);
        $this->logger->pushHandler($testHandler);

        $nextSongAnnotated = $this->annotations->annotateNextSong(
            $request->getStation()
        );

        $this->logger->info('Annotated next song', [
            'annotation' => $nextSongAnnotated,
        ]);
        $this->logger->popHandler();

        return $response->withJson([
            'logs' => $testHandler->getRecords(),
        ]);
    }
}
