<?php
namespace App\Controller\Admin;

use App\Acl;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Settings;
use App\Form\Form;
use App\Form\SettingsForm;
use App\Http\Request;
use App\Http\Response;
use App\Sync\Runner;
use App\Sync\Task\Backup;
use Doctrine\ORM\EntityManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class BackupsController
{
    /** @var SettingsForm */
    protected $settings_form;

    /** @var SettingsRepository */
    protected $settings_repo;

    /** @var Form */
    protected $backup_run_form;

    /** @var Backup */
    protected $backup_task;

    /** @var Filesystem */
    protected $backup_fs;

    /**
     * @param SettingsForm $settings_form
     * @param Form $backup_run_form
     * @param Backup $backup_task
     *
     * @see \App\Provider\AdminProvider
     */
    public function __construct(
        SettingsForm $settings_form,
        Form $backup_run_form,
        Backup $backup_task
    )
    {
        $this->settings_form = $settings_form;
        $this->settings_repo = $settings_form->getEntityRepository();

        $this->backup_run_form = $backup_run_form;

        $this->backup_task = $backup_task;
        $this->backup_fs = new Filesystem(new Local(Backup::BASE_DIR));
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $request->getView()->renderToResponse($response, 'admin/backup/index', [
            'backups'       => $this->backup_fs->listContents('', false),
            'is_enabled'    => (bool)$this->settings_repo->getSetting(Settings::BACKUP_ENABLED, false),
            'last_run'      => $this->settings_repo->getSetting(Settings::BACKUP_LAST_RUN, 0),
            'last_result'   => $this->settings_repo->getSetting(Settings::BACKUP_LAST_RESULT, 0),
            'last_output'   => $this->settings_repo->getSetting(Settings::BACKUP_LAST_OUTPUT, ''),
        ]);
    }

    public function configureAction(Request $request, Response $response): ResponseInterface
    {

    }

    public function runAction(Request $request, Response $response): ResponseInterface
    {

    }

    public function downloadAction(Request $request, Response $response): ResponseInterface
    {

    }

    public function deleteAction(Request $request, Response $response): ResponseInterface
    {

    }
}
