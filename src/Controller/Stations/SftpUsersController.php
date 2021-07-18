<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Environment;
use App\Exception\StationUnsupportedException;
use App\Form\SftpUserForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use App\Service\SftpGo;
use App\Session\Flash;
use DI\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

class SftpUsersController extends AbstractStationCrudController
{
    public function __construct(
        protected AzuraCastCentral $ac_central,
        protected Environment $environment,
        FactoryInterface $factory
    ) {
        parent::__construct($factory->make(SftpUserForm::class));

        $this->csrf_namespace = 'stations_sftp_users';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        if (!SftpGo::isSupportedForStation($station)) {
            throw new StationUnsupportedException(__('This feature is not currently supported on this station.'));
        }

        $baseUrl = $request->getRouter()->getBaseUrl()
            ->withScheme('sftp')
            ->withPort(null);

        $port = $this->environment->getSftpPort();

        $sftpInfo = [
            'url' => (string)$baseUrl,
            'ip' => $this->ac_central->getIp(),
            'port' => $port,
        ];

        return $request->getView()->renderToResponse($response, 'stations/sftp_users/index', [
            'users' => $station->getSftpUsers(),
            'sftp_info' => $sftpInfo,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, int $id = null): ResponseInterface
    {
        if (false !== $this->doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . __('Changes saved.') . '</b>', Flash::SUCCESS);
            return $response->withRedirect((string)$request->getRouter()->fromHere('stations:sftp_users:index'));
        }

        return $request->getView()->renderToResponse(
            $response,
            'system/form_page',
            [
                'form' => $this->form,
                'render_mode' => 'edit',
                'title' => $id ? __('Edit SFTP User') : __('Add SFTP User'),
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $id,
        string $csrf
    ): ResponseInterface {
        $this->doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('SFTP User deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect((string)$request->getRouter()->fromHere('stations:sftp_users:index'));
    }
}
