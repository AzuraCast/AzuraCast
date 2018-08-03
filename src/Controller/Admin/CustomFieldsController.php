<?php
namespace App\Controller\Admin;

use App\Auth;
use App\Csrf;
use App\Flash;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class CustomFieldsController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var array */
    protected $form_config;

    /** @var Entity\Repository\BaseRepository */
    protected $record_repo;

    /** @var Csrf */
    protected $csrf;

    /** @var string */
    protected $csrf_namespace = 'admin_custom_fields';

    /**
     * UsersController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param array $form_config
     */
    public function __construct(EntityManager $em, Flash $flash, Csrf $csrf, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->csrf = $csrf;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\CustomField::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $records = $this->record_repo->fetchArray(true, 'name');

        return $request->getView()->renderToResponse($response, 'admin/custom_fields/index', [
            'records' => $records,
            'csrf' => $this->csrf->generate($this->csrf_namespace)
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        $form = new \AzuraForms\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->record_repo->find((int)$id);
            $record_defaults = $this->record_repo->toArray($record, true, true);
            $form->populate($record_defaults);
        } else {
            $record = null;
        }

        if (!empty($_POST) && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\CustomField)) {
                $record = new Entity\CustomField;
            }

            $this->record_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $this->flash->alert(sprintf(($id) ? __('%s updated.') : __('%s added.'), __('Custom Field')), 'green');

            return $response->redirectToRoute('admin:custom_fields:index');
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => sprintf(($id) ? __('Edit %s') : __('Add %s'), __('Custom Field'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $this->csrf->verify($csrf_token, $this->csrf_namespace);

        $user = $this->record_repo->find((int)$id);

        if ($user instanceof Entity\CustomField) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $this->flash->alert('<b>' . __('%s deleted.', __('Custom Field')) . '</b>', 'green');

        return $response->redirectToRoute('admin:custom_fields:index');
    }
}
