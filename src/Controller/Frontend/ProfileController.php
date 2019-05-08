<?php
namespace App\Controller\Frontend;

use App\Form\Form;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use Psr\Http\Message\ResponseInterface;
use BaconQrCode;

class ProfileController
{
    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $profile_form;

    /** @var array */
    protected $two_factor_form;

    /** @var Entity\Repository\UserRepository */
    protected $user_repo;

    /**
     * @param EntityManager $em
     * @param array $profile_form
     * @param array $two_factor_form
     * @see \App\Provider\FrontendProvider
     */
    public function __construct(
        EntityManager $em,
        array $profile_form,
        array $two_factor_form)
    {
        $this->em = $em;
        $this->profile_form = $profile_form;
        $this->two_factor_form = $two_factor_form;

        $this->user_repo = $this->em->getRepository(Entity\User::class);
    }

    public function indexAction(Request $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();
        $user_profile = $this->user_repo->toArray($user);

        $customization_form = new Form($this->profile_form['groups']['customization'], $user_profile);

        return $request->getView()->renderToResponse($response, 'frontend/profile/index', [
            'user' => $request->getUser(),
            'customization_form' => $customization_form,
        ]);
    }

    public function editAction(Request $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();

        $form_config = $this->profile_form;
        $form_config['groups']['reset_password']['elements']['password'][1]['validator'] = function($val, \AzuraForms\Field\AbstractField $field) use ($user) {
            $form = $field->getForm();

            $new_password = $form->getField('new_password')->getValue();
            if (!empty($new_password)) {
                if ($user->verifyPassword($val)) {
                    return true;
                }

                return 'Current password could not be verified.';
            }

            return true;
        };

        $form = new Form($form_config);

        $user_profile = $this->user_repo->toArray($user);
        unset($user_profile['auth_password']);

        $form->populate(array_filter($user_profile));

        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            $data = $form->getValues();

            $this->user_repo->fromArray($user, $data);

            if (!empty($data['new_password']))
            {
                $user->setAuthPassword($data['new_password']);
            }

            $this->em->persist($user);
            $this->em->flush();

            $request->getSession()->flash(__('Profile saved!'), 'green');

            return $response->withRedirect($request->getRouter()->named('profile:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Edit Profile')
        ]);
    }

    public function themeAction(Request $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();

        $theme_field = $this->profile_form['groups']['customization']['elements']['theme'][1];
        $theme_options = array_keys($theme_field['choices']);

        $current_theme = $user->getTheme();
        if (empty($current_theme)) {
            $current_theme = $theme_field['default'];
        }

        foreach($theme_options as $theme) {
            if ($theme !== $current_theme) {
                $user->setTheme($theme);
                break;
            }
        }

        $this->em->persist($user);
        $this->em->flush($user);

        return $response->withRedirect(
            $request->getReferrer($request->getRouter()->named('dashboard'))
        );
    }

    public function enableTwoFactorAction(Request $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();
        $form = new Form($this->two_factor_form);

        $form->getField('otp')->addValidator(function($otp, \AzuraForms\Field\AbstractField $element) {
            $secret = $element->getForm()->getField('secret')->getValue();

            $totp = TOTP::create($secret);
            return ($totp->verify($otp, null, \App\Auth::TOTP_WINDOW))
                ? true
                : __('The token you supplied is invalid. Please try again.');
        });

        if ($request->isPost()) {
            $secret = $request->getParsedBodyParam('secret');
        } else {
            // Generate new TOTP secret.
            $secret = substr(trim(Base32::encodeUpper(random_bytes(128)), '='), 0, 64);

            $form->populate([
                'secret' => $secret,
            ]);
        }

        // Customize TOTP code
        $totp = TOTP::create($secret);
        $totp->setLabel($user->getEmail());

        if ($request->isPost() && $form->isValid($request->getParsedBody())) {
            $user->setTwoFactorSecret($totp->getProvisioningUri());
            $this->em->persist($user);
            $this->em->flush($user);

            $request->getSession()->flash(__('Two-factor authentication enabled.'), 'green');

            return $response->withRedirect($request->getRouter()->named('profile:index'));
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
        $writer = new BaconQrCode\Writer($renderer);
        $qr_code = $writer->writeString($totp_uri);

        return $request->getView()->renderToResponse($response, 'frontend/profile/enable_two_factor', [
            'form' => $form,
            'qr_code' => $qr_code,
            'totp_uri' => $totp_uri,
        ]);
    }

    public function disableTwoFactorAction(Request $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();

        $user->setTwoFactorSecret(null);
        $this->em->persist($user);
        $this->em->flush($user);

        $request->getSession()->flash(__('Two-factor authentication disabled.'), 'green');

        return $response->withRedirect($request->getRouter()->named('profile:index'));
    }
}
