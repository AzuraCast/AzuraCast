<?php
namespace Controller\Frontend;

use Entity;
use Slim\Container;
use App\Http\Request;
use App\Http\Response;

class ProfileController extends BaseController
{
    /** @var Entity\Repository\UserRepository */
    protected $user_repo;

    public function __construct(Container $di)
    {
        parent::__construct($di);

        $this->user_repo = $this->em->getRepository(Entity\User::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $form = new \App\Form($this->config->forms->profile);

        $user_profile = $this->user_repo->toArray($user);
        unset($user_profile['auth_password']);
        $form->setDefaults($user_profile);

        return $this->render($response, 'frontend/profile/index', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    public function editAction(Request $request, Response $response): Response
    {
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $form_config = $this->config->forms->profile->toArray();
        $form_config['groups']['reset_password']['elements']['password'][1]['validator'] = function($val, \Nibble\NibbleForms\Field $field) use ($user) {
            $form = $field->getForm();

            $new_password = $form->getData('new_password');
            if (!empty($new_password)) {
                if ($user->verifyPassword($val)) {
                    return true;
                } else {
                    $field->error[] = 'Current password could not be verified.';
                    return false;
                }
            }

            return true;
        };

        $form = new \App\Form($form_config);

        $user_profile = $this->user_repo->toArray($user);
        unset($user_profile['auth_password']);

        $form->setDefaults($user_profile);

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            $this->user_repo->fromArray($user, $data);

            if (!empty($data['new_password']))
            {
                $user->setAuthPassword($data['new_password']);
            }

            $this->em->persist($user);
            $this->em->flush();

            $this->alert(_('Profile saved!'), 'green');

            return $this->redirectToName($response, 'profile:index');
        }

        return $this->renderForm($response, $form, 'edit', _('Edit Profile'));
    }
}