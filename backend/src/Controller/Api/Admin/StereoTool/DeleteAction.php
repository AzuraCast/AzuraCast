<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\StereoTool;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;

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
