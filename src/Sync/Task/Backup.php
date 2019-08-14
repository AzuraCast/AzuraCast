<?php
namespace App\Sync\Task;

use App\Entity;
use App\Message;
use App\MessageQueue;
use Azura\Console\Application;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;

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

            $this->settings_repo->setSetting(Entity\Settings::BACKUP_LAST_RUN, time());
            $this->settings_repo->setSetting(Entity\Settings::BACKUP_LAST_RESULT, $result_code);
            $this->settings_repo->setSetting(Entity\Settings::BACKUP_LAST_OUTPUT, $result_output);
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
        $backup_enabled = (bool)$this->settings_repo->getSetting(Entity\Settings::BACKUP_ENABLED, 0);
        if (!$backup_enabled) {
            $this->logger->debug('Automated backups disabled; skipping...');
            return;
        }

        $now_utc = Chronos::now('UTC');

        $threshold = $now_utc->subDay()->getTimestamp();
        $last_run = $this->settings_repo->getSetting(Entity\Settings::BACKUP_LAST_RUN, 0);

        if ($last_run <= $threshold) {
            // Check if the backup time matches (if it's set).
            $backup_timecode = (int)$this->settings_repo->getSetting(Entity\Settings::BACKUP_TIME);
            if (0 !== $backup_timecode) {
                $current_timecode = (int)$now_utc->format('Hi');

                if ($backup_timecode !== $current_timecode) {
                    return;
                }
            }

            // Trigger a new backup.
            $message = new Message\BackupMessage;
            $message->path = 'automatic_backup.zip';
            $message->exclude_media = (bool)$this->settings_repo->getSetting(Entity\Settings::BACKUP_EXCLUDE_MEDIA, 0);
            $this->message_queue->produce($message);
        }
    }
}
