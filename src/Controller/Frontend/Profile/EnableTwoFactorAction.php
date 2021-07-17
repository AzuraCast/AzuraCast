<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Profile;

use App\Auth;
use App\Config;
use App\Form\Form;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Session\Flash;
use AzuraForms\Field\AbstractField;
use BaconQrCode;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Psr\Http\Message\ResponseInterface;

class EnableTwoFactorAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Config $config,
        EntityManagerInterface $em
    ): ResponseInterface {
        $twoFactorFormConfig = $config->get('forms/profile_two_factor');

        $user = $request->getUser();
        $form = new Form($twoFactorFormConfig);

        $session = $request->getSession();

        if ($request->isPost()) {
            $secret = $session->get('totp_secret');
        } else {
            // Generate new TOTP secret.
            $secret = substr(trim(Base32::encodeUpper(random_bytes(128)), '='), 0, 64);
            $session->set('totp_secret', $secret);
        }

        // Customize TOTP code
        $totp = TOTP::create($secret);
        $totp->setLabel($user->getEmail());

        $form->getField('otp')->addValidator(function ($otp, AbstractField $element) use ($totp) {
            return ($totp->verify($otp, null, Auth::TOTP_WINDOW))
                ? true
                : __('The token you supplied is invalid. Please try again.');
        });

        if ($form->isValid($request)) {
            $user->setTwoFactorSecret($totp->getProvisioningUri());

            $em->persist($user);
            $em->flush();

            $request->getFlash()->addMessage(__('Two-factor authentication enabled.'), Flash::SUCCESS);

            return $response->withRedirect((string)$request->getRouter()->named('profile:index'));
        }

        // Further customize TOTP code (with metadata that won't be stored in the DB)
        $totp->setIssuer('AzuraCast');
        $totp->setParameter('image', 'https://www.azuracast.com/img/logo.png');

        // Generate QR code
        $totp_uri = $totp->getProvisioningUri();

        $renderer = new BaconQrCode\Renderer\ImageRenderer(
            new BaconQrCode\Renderer\RendererStyle\RendererStyle(300),
            new BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $qr_code = (new BaconQrCode\Writer($renderer))->writeString($totp_uri);

        return $request->getView()->renderToResponse($response, 'frontend/profile/enable_two_factor', [
            'form' => $form,
            'qr_code' => $qr_code,
            'totp_uri' => $totp_uri,
        ]);
    }
}
