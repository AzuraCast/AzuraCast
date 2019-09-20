<?php
namespace App\Console\Command;

use App\Entity;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetSettingCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        string $settingKey,
        string $settingValue
    ) {
        $io->title('AzuraCast Settings');

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $em->getRepository(Entity\Settings::class);

        if (strtolower($settingValue) === 'null') {
            $settings_repo->deleteSetting($settingKey);

            $io->success(sprintf('Setting "%s" removed.', $settingKey));
            return null;
        }

        if (0 === strpos($settingValue, '{')) {
            $settingValue = json_decode($settingValue, true);
        }

        $settings_repo->setSetting($settingKey, $settingValue);

        $io->success(sprintf('Setting "%s" updated.', $settingKey));
    }
}
