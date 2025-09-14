<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Rsas;
use App\Service\Flow;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[OA\Post(
    path: '/admin/rsas',
    operationId: 'postRsas',
    summary: 'Upload a new Rocket Streaming Audio Server (RSAS) binary.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class PostAction implements SingleActionInterface
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

        $baseDir = Rsas::getDirectory();
        File::mkdirIfNotExists($baseDir);

        $fsUtils = new Filesystem();

        $tgzPath = $baseDir . '/rsas.tar.gz';
        $fsUtils->remove($tgzPath);

        $flowResponse->moveTo($tgzPath);

        try {
            $process = new Process(
                [
                    'tar',
                    'xvzf',
                    $tgzPath,
                    '--strip-components=1',
                ],
                $baseDir
            );
            $process->mustRun();
        } finally {
            $fsUtils->remove($tgzPath);
        }

        return $response->withJson(Status::success());
    }
}
