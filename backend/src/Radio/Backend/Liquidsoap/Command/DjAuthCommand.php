<?php

declare(strict_types=1);

namespace App\Radio\Backend\Liquidsoap\Command;

use App\Entity\Repository\StationStreamerRepository;
use App\Entity\Station;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\Types;
use InvalidArgumentException;
use RuntimeException;

final class DjAuthCommand extends AbstractCommand
{
    public function __construct(
        private readonly StationStreamerRepository $streamerRepo,
        private readonly Scheduler $scheduler,
    ) {
    }

    protected function doRun(
        Station $station,
        bool $asAutoDj = false,
        array $payload = []
    ): array {
        if (!$station->enable_streamers) {
            throw new RuntimeException('Streamers are disabled on this station.');
        }

        [$user, $pass] = $this->getCredentials($payload);

        // Allow connections using the exact broadcast source password.
        if ('source' === $user) {
            $sourcePw = $station->frontend_config->source_pw;

            if (!empty($sourcePw) && strcmp($sourcePw, $pass) === 0) {
                return [
                    'allow' => true,
                    'username' => $user,
                ];
            }
        }

        $streamer = $this->streamerRepo->getStreamer($station, $user);

        if (null === $streamer) {
            return [
                'allow' => false,
            ];
        }

        return [
            'allow' => $streamer->authenticate($pass) && $this->scheduler->canStreamerStreamNow($streamer),
            'username' => $streamer->streamer_username,
            'display_name' => $streamer->display_name,
        ];
    }

    /**
     * @return array{string, string}
     */
    private function getCredentials(array $payload = []): array
    {
        $user = Types::stringOrNull($payload['user'] ?? null, true);
        $pass = Types::stringOrNull($payload['password'] ?? null, true);

        if (null === $pass) {
            throw new InvalidArgumentException('No credentials provided!');
        }

        if (null === $user || 'source' === $user) {
            foreach ([',', ':'] as $separator) {
                if (str_contains($pass, $separator)) {
                    [$user, $pass] = explode($separator, $pass, 2);
                    return [$user, $pass];
                }
            }
        }

        if (null === $user) {
            throw new InvalidArgumentException('No credentials provided!');
        }

        return [$user, $pass];
    }
}
