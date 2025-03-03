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
    description: 'Upload a new Shoutcast binary.',
    security: OpenApi::API_KEY_SECURITY,
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
    tags: ['Administration: General'],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
