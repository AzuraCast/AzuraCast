<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Rsas;
use App\Service\Flow;
use Psr\Http\Message\ResponseInterface;

final class PostLicenseAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $licensePath = Rsas::getLicensePath();

        @unlink($licensePath);
        $flowResponse->moveTo($licensePath);

        return $response->withJson(Status::success());
    }
}
