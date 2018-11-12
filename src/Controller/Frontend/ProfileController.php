<?php
namespace App\Controller\Frontend;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class ProfileController
{
    /** @var EntityManager */
    protected $em;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\UserRepository */
    protected $user_repo;

    /**
     * @param EntityManager $em
     * @param array $form_config
     * @see \App\Provider\FrontendProvider
     */
    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;

        $this->user_repo = $this->em->getRepository(Entity\User::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $user = $request->getUser();
        $user_profile = $this->user_repo->toArray($user);
        unset($user_profile['auth_password']);

        $account_info_form = new \AzuraForms\Form($this->form_config['groups']['account_info'], $user_profile);
        $customization_form = new \AzuraForms\Form($this->form_config['groups']['customization'], $user_profile);

        return $request->getView()->renderToResponse($response, 'frontend/profile/index', [
            'user' => $request->getUser(),
            'account_info_form' => $account_info_form,
            'customization_form' => $customization_form,
        ]);
    }

    public function editAction(Request $request, Response $response): Response
    {
        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        $form_config = $this->form_config;
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

        $form = new \AzuraForms\Form($form_config);

        $user_profile = $this->user_repo->toArray($user);
        unset($user_profile['auth_password']);

        $form->populate(array_filter($user_profile));

        if ($_POST && $form->isValid($_POST)) {
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
}
