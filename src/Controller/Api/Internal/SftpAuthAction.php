<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Entity\SftpUser;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class SftpAuthAction
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $errorResponse = $response
            ->withStatus(500)
            ->withJson(['username' => '']);

        $parsedBody = (array)$request->getParsedBody();
        $username = $parsedBody['username'] ?? '';
        $password = $parsedBody['password'] ?? '';
        $pubKey = $parsedBody['public_key'] ?? '';

        if (empty($username)) {
            return $errorResponse;
        }

        $sftpUser = $this->em->getRepository(SftpUser::class)->findOneBy(['username' => $username]);
        if (!($sftpUser instanceof SftpUser)) {
            $this->logger->notice(
                sprintf(
                    'SFTP user "%s" not found.',
                    $username
                )
            );

            return $errorResponse;
        }

        if (!$sftpUser->authenticate($password, $pubKey)) {
            $this->logger->notice(
                sprintf(
                    'SFTP user "%s" could not authenticate.',
                    $username
                ),
                [
                    'hasPassword' => !empty($password),
                    'hasPubKey' => !empty($pubKey),
                ]
            );

            return $errorResponse;
        }

        $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

        if (!$storageLocation->isLocal()) {
            $this->logger->error(
                sprintf(
                    'SFTP login failed for user "%s": Storage Location %s is not local.',
                    $username,
                    $storageLocation
                )
            );

            return $errorResponse;
        }

        $quotaRaw = $storageLocation->getStorageQuotaBytes();
        $quota = $quotaRaw ?? 0;

        $row = [
            'status' => 1,
            'username' => $sftpUser->getUsername(),
            'expiration_date' => 0,
            'home_dir' => $storageLocation->getPath(),
            'uid' => 0,
            'gid' => 0,
            'quota_size' => $quota,
            'permissions' => [
                '/' => ['*'],
            ],
        ];

        return $response->withJson(
            $row,
            options: JSON_NUMERIC_CHECK
        );
    }
}
