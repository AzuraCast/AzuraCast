<?php
namespace Controller\Admin;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Entity;

class UsersController extends BaseController
{
    /** @var Entity\Repository\UserRepository */
    protected $record_repo;

    public function preDispatch()
    {
        parent::preDispatch();

        $this->record_repo = $this->em->getRepository(Entity\User::class);
    }

    public function permissions()
    {
        return $this->acl->isAllowed('administer users');
    }

    public function indexAction()
    {
        if ($_GET) {
            $this->redirectFromHere($_GET);
        }

        if ($this->hasParam('q')) {
            $this->view->q = $q = trim($this->getParam('q'));

            $query = $this->em->createQuery('SELECT u, r FROM Entity\User u LEFT JOIN u.roles r WHERE (u.name LIKE :query OR u.email LIKE :query) ORDER BY u.name ASC')
                ->setParameter('query', '%' . $q . '%');
        } else {
            $query = $this->em->createQuery('SELECT u, r FROM Entity\User u LEFT JOIN u.roles r ORDER BY u.name ASC');
        }

        $this->view->user = $this->auth->getLoggedInUser();
        $this->view->pager = new \App\Paginator\Doctrine($query, $this->getParam('page', 1), 50);
    }

    public function editAction()
    {
        $form_config = $this->config->forms->user->form->toArray();
        $form = new \App\Form($form_config);

        if ($this->hasParam('id')) {
            $record = $this->record_repo->find($this->getParam('id'));
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
                return $this->redirectFromHere(['action' => 'index', 'id' => null]);
            } catch(UniqueConstraintViolationException $e) {
                $this->alert(_('Another user already exists with this e-mail address. Please update the e-mail address.'), 'red');
            }
        }

        return $this->renderForm($form, 'edit', _('Edit Record'));
    }

    public function deleteAction()
    {
        $id = (int)$this->getParam('id');
        $user = $this->record_repo->find($id);

        if ($user instanceof Entity\User) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $this->alert('<b>' . _('Record deleted.') . '</b>', 'green');

        return $this->redirectFromHere(['action' => 'index', 'id' => null]);
    }

    public function impersonateAction()
    {
        $id = (int)$this->getParam('id');
        $user = $this->record_repo->find($id);

        if (!($user instanceof Entity\User)) {
            throw new \App\Exception(_('Record not found!'));
        }

        // Set new identity in Zend_Auth
        $this->auth->masqueradeAsUser($user);

        $this->alert('<b>' . _('Logged in successfully.') . '</b><br>' . $user->getEmail(), 'green');

        return $this->redirectHome();
    }
}