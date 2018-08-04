<?php
namespace App\Controller\Admin;

use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class ApiController
{
    /** @var EntityManager */
    protected $em;

    /** @var Entity\Repository\BaseRepository */
    protected $record_repo;

    /** @var array */
    protected $form_config;

    /** @var string */
    protected $csrf_namespace = 'admin_api';

    public function __construct(EntityManager $em, array $form_config)
    {
        $this->em = $em;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $records = $this->em->createQuery('SELECT a, u FROM '.Entity\ApiKey::class.' a JOIN a.user u')
            ->getArrayResult();

        return $request->getView()->renderToResponse($response, 'admin/api/index', [
            'records' => $records,
            'csrf' => $request->getSession()->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(Request $request, Response $response, $id): Response
    {
        $form = new \AzuraForms\Form($this->form_config);

        $record = $this->record_repo->find($id);

        if (!($record instanceof Entity\ApiKey)) {
            throw new \App\Exception\NotFound(__('%s not found.', __('API Key')));
        }

        $form->populate($this->record_repo->toArray($record, true, true));

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            $this->record_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $request->getSession()->flash(__('%s updated.', __('API Key')), 'green');

            return $response->redirectToRoute('admin:api:index');
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Edit %s', __('API Key'))
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id, $csrf_token): Response
    {
        $request->getSession()->getCsrf()->verify($csrf_token, $this->csrf_namespace);

        $record = $this->record_repo->find($id);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $request->getSession()->flash(__('%s deleted.', __('API Key')), 'green');

        return $response->redirectToRoute('admin:api:index');
    }
}
