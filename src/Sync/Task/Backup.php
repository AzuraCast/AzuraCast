<?php
namespace App\Sync\Task;

use App\MessageQueue;
use App\Message;
use Azura\Console\Application;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Monolog\Logger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Finder\Finder;

class Backup extends AbstractTask
{
    public const BASE_DIR = '/var/azuracast/backups';

    /** @var Entity\Repository\SettingsRepository */
    protected $settings_repo;

    /** @var MessageQueue */
    protected $message_queue;

    /** @var Application */
    protected $console;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     * @param MessageQueue $message_queue
     * @param Application $console
     *
     * @see \App\Provider\SyncProvider
     */
    public function __construct(
        EntityManager $em,
        Logger $logger,
        MessageQueue $message_queue,
        Application $console
    ) {
        parent::__construct($em, $logger);

        $this->settings_repo = $em->getRepository(Entity\Settings::class);

        $this->message_queue = $message_queue;
        $this->console = $console;
    }

    /**
     * Handle event dispatch.
     *
     * @param Message\AbstractMessage $message
     */
    public function __invoke(Message\AbstractMessage $message)
    {
        if ($message instanceof Message\BackupMessage) {

            [$result_code, $result_output] = $this->runBackup(
                $message->path,
                $message->exclude_media
            );

            $this->settings_repo->setSettings([
                Entity\Settings::BACKUP_LAST_RUN        => time(),
                Entity\Settings::BACKUP_LAST_RESULT     => $result_code,
                Entity\Settings::BACKUP_LAST_OUTPUT     => $result_output,
            ]);

        }
    }

    /**
     * @param string|null $path
     * @param bool $exclude_media
     * @return array [$result_code, $result_output]
     */
    public function runBackup($path = null, $exclude_media = false): array
    {
        $input_params = [];
        if (null !== $path) {
            $input_params['path'] = $path;
        }
        if ($exclude_media) {
            $input_params['--exclude-media'] = true;
        }

        return $this->console->runCommand('azuracast:backup', $input_params);
    }

    /**
     * @inheritdoc
     */
    public function run($force = false): void
    {

    }
}
