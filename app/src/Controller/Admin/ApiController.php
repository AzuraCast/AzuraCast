<?php
namespace Controller\Admin;

use Entity;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class ApiController extends BaseController
{
    /** @var Entity\Repository\BaseRepository */
    protected $record_repo;

    public function __construct(Container $di)
    {
        parent::__construct($di);

        $this->record_repo = $this->em->getRepository(Entity\ApiKey::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        $this->view->records = $this->record_repo->fetchArray();

        return $this->render($response, 'admin/api/index');
    }

    public function editAction(Request $request, Response $response, $args): Response
    {
        $form = new \App\Form($this->config->forms->api_key);

        if (!empty($args['id'])) {
            $id = $args['id'];
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

    public function deleteAction(Request $request, Response $response, $args): Response
    {
        $record = $this->record_repo->find($args['id']);

        if ($record instanceof Entity\ApiKey) {
            $this->em->remove($record);
        }

        $this->em->flush();

        $this->alert(_('Record deleted.'), 'green');

        return $this->redirectToName($response, 'admin:api:index');
    }
}