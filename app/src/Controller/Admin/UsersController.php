<?php
namespace Controller\Admin;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Entity;
use Slim\Container;
use App\Http\Request;
use App\Http\Response;

class UsersController extends \AzuraCast\Legacy\Controller
{
    /** @var Entity\Repository\UserRepository */
    protected $record_repo;

    public function __construct(Container $di)
    {
        parent::__construct($di);

        $this->record_repo = $this->em->getRepository(Entity\User::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $users = $this->em->createQuery('SELECT u, r FROM Entity\User u LEFT JOIN u.roles r ORDER BY u.name ASC')
            ->execute();

        return $this->render($response, 'admin/users/index', [
            'user' => $request->getAttribute('user'),
            'users' => $users,
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        $form_config = $this->config->forms->user->form->toArray();
        $form = new \App\Form($form_config);

        if (!empty($id)) {
            $record = $this->record_repo->find((int)$id);
            $record_defaults = $this->record_repo->toArray($record, true, true);

            unset($record_defaults['auth_password']);

            $form->setDefaults($record_defaults);
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\User)) {
                $record = new Entity\User;
            }

            $this->record_repo->fromArray($record, $data);

            try {
                $this->em->persist($record);
                $this->em->flush();

                $this->alert(_('Record updated.'), 'green');

                return $this->redirectToName($response, 'admin:users:index');
            } catch(UniqueConstraintViolationException $e) {
                $this->alert(_('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
            }
        }

        return $this->renderForm($response, $form, 'edit', _('Edit Record'));
    }

    public function deleteAction(Request $request, Response $response, $args): Response
    {
        $user = $this->record_repo->find((int)$args['id']);

        if ($user instanceof Entity\User) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectToName($response, 'admin:users:index');
    }

    public function impersonateAction(Request $request, Response $response, $id): Response
    {
        $user = $this->record_repo->find((int)$id);

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception(_('Record not found!'));
        }

        /** @var \App\Auth $auth */
        $auth = $this->di[\App\Auth::class];
        $auth->masqueradeAsUser($user);

        $this->alert('<b>' . _('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

        return $this->redirectHome($response);
    }
}