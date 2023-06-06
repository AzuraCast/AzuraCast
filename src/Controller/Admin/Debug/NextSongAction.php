<?php

declare(strict_types=1);

namespace App\Controller\Admin\Debug;

use App\Container\LoggerAwareTrait;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ\Annotations;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Psr\Http\Message\ResponseInterface;

final class NextSongAction
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Annotations $annotations
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
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

        return $request->getView()->renderToResponse(
            $response,
            'system/log_view',
            [
                'sidebar' => null,
                'title' => __('Debug Output'),
                'log_records' => $testHandler->getRecords(),
            ]
        );
    }
}
