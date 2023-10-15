<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Auth;
use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use InvalidArgumentException;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Psr\Http\Message\ResponseInterface;
use Throwable;

final class PutTwoFactorAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $params = (array)$request->getParsedBody();

        try {
            if (!empty($params['secret'])) {
                $secret = $params['secret'];
                if (64 !== strlen($secret)) {
                    throw new InvalidArgumentException('Secret is not the correct length.');
                }
            } else {
                // Generate new TOTP secret.
                $secret = substr(trim(Base32::encodeUpper(random_bytes(128)), '='), 0, 64);
            }

            // Customize TOTP code
            $user = $request->getUser();

            $totp = TOTP::create($secret);
            $totp->setLabel($user->getEmail() ?: 'AzuraCast');

            if (!empty($params['otp'])) {
                if ($totp->verify($params['otp'], null, Auth::TOTP_WINDOW)) {
                    $user = $this->em->refetch($user);
                    $user->setTwoFactorSecret($totp->getProvisioningUri());

                    $this->em->persist($user);
                    $this->em->flush();

                    return $response->withJson(Status::success());
                }

                throw new InvalidArgumentException('Could not verify TOTP code.');
            }

            // Further customize TOTP code (with metadata that won't be stored in the DB)
            $totp->setIssuer('AzuraCast');
            $totp->setParameter('image', 'https://www.azuracast.com/img/logo.png');

            return $response->withJson([
                'secret' => $secret,
                'totp_uri' => $totp->getProvisioningUri(),
            ]);
        } catch (Throwable $e) {
            return $response->withStatus(400)->withJson(Error::fromException($e));
        }
    }
}
