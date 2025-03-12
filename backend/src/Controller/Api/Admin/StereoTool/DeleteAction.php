<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\StereoTool;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/admin/stereo_tool',
    operationId: 'deleteStereoTool',
    summary: 'Removes the installed Stereo Tool binary.',
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
        $libraryPath = StereoTool::getLibraryPath();
        File::clearDirectoryContents($libraryPath);

        return $response->withJson(Status::success());
    }
}
