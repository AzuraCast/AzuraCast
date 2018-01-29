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

    public function __construct(EntityManager $em, Flash $flash)
    {
        $this->em = $em;
        $this->flash = $flash;

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
        $form = new \App\Form($this->config->forms->api_key);

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

            $this->alert(_('Changes saved.'), 'green');

            return $this->redirectToName($response, 'admin:api:index');
        }

        return $this->renderForm($response, $form, 'edit', _('Edit Record'));
    }

    public function deleteAction(Request $request, Response $response, $id): Response
    {
        $record = $this->record_repo->find($id);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectToName($response, 'admin:api:index');
    }
}