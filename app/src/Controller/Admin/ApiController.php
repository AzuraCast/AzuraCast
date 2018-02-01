<?php
namespace Controller\Admin;

use App\Flash;
use Doctrine\ORM\EntityManager;
use Entity;
use Slim\Container;
use App\Http\Request;
use App\Http\Response;

class ApiController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Entity\Repository\BaseRepository */
    protected $record_repo;

    /** @var array */
    protected $form_config;

    public function __construct(EntityManager $em, Flash $flash, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->form_config = $form_config;

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'admin/api/index', [
            'records' => $this->record_repo->fetchArray(),
        ]);
    }

    public function editAction(Request $request, Response $response, $id = null): Response
    {
        $form = new \App\Form($this->form_config);

        if (!empty($id)) {
            $record = $this->record_repo->find($id);
            $form->setDefaults($this->record_repo->toArray($record, true, true));
        } else {
            $record = null;
        }

        if ($_POST && $form->isValid($_POST)) {
            $data = $form->getValues();

            if (!($record instanceof Entity\ApiKey)) {
                $record = new Entity\ApiKey;
            }

            $this->record_repo->fromArray($record, $data);

            $this->em->persist($record);
            $this->em->flush();

            $this->flash->alert(_('Changes saved.'), 'green');

            return $response->redirectToRoute('admin:api:index');
        }

        /** @var \App\Mvc\View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => _('Edit Record')
        ]);
    }

    public function deleteAction(Request $request, Response $response, $id): Response
    {
        $record = $this->record_repo->find($id);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->flash->alert(_('Record deleted.'), 'green');

        return $response->redirectToRoute('admin:api:index');
    }
}