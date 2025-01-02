<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Rsas;
use Psr\Http\Message\ResponseInterface;

final class DeleteLicenseAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $licensePath = Rsas::getLicensePath();
        @unlink($licensePath);

        return $response->withJson(Status::success());
    }
}
