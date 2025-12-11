<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Shoutcast;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/admin/shoutcast',
    operationId: 'deleteShoutcast',
    summary: 'Removes the installed Shoutcast binary.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class DeleteAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $libraryPath = Shoutcast::getDirectory();
        File::clearDirectoryContents($libraryPath);

        return $response->withJson(Status::success());
    }
}
