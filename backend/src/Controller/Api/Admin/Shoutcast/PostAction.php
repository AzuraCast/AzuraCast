<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Shoutcast;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

#[OA\Post(
    path: '/admin/shoutcast',
    operationId: 'postShoutcast',
    summary: 'Upload a new Shoutcast binary.',
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
        if ('x86_64' !== php_uname('m')) {
            throw new RuntimeException('Shoutcast cannot be installed on non-X86_64 systems.');
        }

        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $fsUtils = new Filesystem();

        $scBaseDir = Shoutcast::getDirectory();
        $scTgzPath = $scBaseDir . '/sc_serv.tar.gz';
        $fsUtils->remove($scTgzPath);

        $flowResponse->moveTo($scTgzPath);

        try {
            $process = new Process(
                [
                    'tar',
                    'xvzf',
                    $scTgzPath,
                ],
                $scBaseDir
            );
            $process->mustRun();
        } finally {
            $fsUtils->remove($scTgzPath);
        }

        return $response->withJson(Status::success());
    }
}
