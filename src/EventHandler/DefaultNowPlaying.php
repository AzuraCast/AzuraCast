<?php
namespace App\EventHandler;

use App\Event\GenerateRawNowPlaying;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DefaultNowPlaying implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        if (APP_TESTING_MODE) {
            return [];
        }

        return [
            GenerateRawNowPlaying::NAME => [
                ['loadRawFromFrontend', 10],
                ['addToRawFromRemotes', 0],
                ['cleanUpRawOutput', -10],
            ],
        ];
    }

    public function loadRawFromFrontend(GenerateRawNowPlaying $event)
    {
        $np_raw = $event->getFrontend()->getNowPlaying($event->getPayload(), $event->includeClients());

        $event->setRawResponse($np_raw);
    }

    public function addToRawFromRemotes(GenerateRawNowPlaying $event)
    {
        $np_raw = $event->getRawResponse();

        // Loop through all remotes and update NP data accordingly.
        foreach($event->getRemotes() as $remote_adapter) {
            $remote_adapter->updateNowPlaying($np_raw, $event->includeClients());
        }

        $event->setRawResponse($np_raw);
    }

    public function cleanUpRawOutput(GenerateRawNowPlaying $event)
    {
        $np_raw = $event->getRawResponse();

        array_walk($np_raw['current_song'], function(&$value) {
            $value = htmlspecialchars_decode($value);
            $value = trim($value);
        });

        $event->setRawResponse($np_raw);
    }

}
