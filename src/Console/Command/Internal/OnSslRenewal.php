<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Container\EntityManagerAwareTrait;
use App\Entity\Station;
use App\Radio\Adapters;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'azuracast:internal:on-ssl-renewal',
    description: 'Reload broadcast frontends when an SSL certificate changes.',
)]
final class OnSslRenewal extends CommandAbstract
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly Adapters $adapters,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stations = $this->em->createQuery(
            <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
        )->toIterable();

        /** @var Station $station */
        foreach ($stations as $station) {
            if ($station->getFrontendType()->supportsReload()) {
                $frontend = $this->adapters->getFrontendAdapter($station);
                if (null !== $frontend) {
                    $frontend->write($station);
                    $frontend->reload($station);
                }
            }
        }

        return 0;
    }
}
