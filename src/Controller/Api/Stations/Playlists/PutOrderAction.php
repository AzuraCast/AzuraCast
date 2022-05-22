<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Playlists;

use App\Entity;
use App\Exception;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

final class PutOrderAction extends AbstractPlaylistsAction
{
    public function __construct(
        EntityManagerInterface $em,
        private readonly Entity\Repository\StationPlaylistMediaRepository $spmRepo,
    ) {
        parent::__construct($em);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id,
        int $id
    ): ResponseInterface {
        $record = $this->requireRecord($request->getStation(), $id);

        if (
            Entity\Enums\PlaylistSources::Songs !== $record->getSourceEnum()
            || Entity\Enums\PlaylistOrders::Sequential !== $record->getOrderEnum()
        ) {
            throw new Exception(__('This playlist is not a sequential playlist.'));
        }

        $order = $request->getParam('order');

        $this->spmRepo->setMediaOrder($record, $order);
        return $response->withJson($order);
    }
}
