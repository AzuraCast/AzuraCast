<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

use App\Entity\Simulcasting;

class FacebookSimulcastingAdapter extends AbstractSimulcastingAdapter
{
    public function getStreamKey(): string
    {
        return $this->simulcasting->getStreamKey();
    }

    public function getStatus(): string
    {
        return $this->simulcasting->getStatus()->value;
    }

    public function run(): bool
    {
        // This will be controlled via LiquidSoap configuration
        // The actual implementation is in the LiquidSoap script
        return true;
    }

    public function stop(): bool
    {
        // This will be controlled via LiquidSoap configuration
        return true;
    }

    public function getAdapterName(): string
    {
        return 'facebook';
    }

    public function getAdapterDescription(): string
    {
        return 'Facebook Live';
    }

    public function getConfiguration(): array
    {
        return [
            'type' => 'rtmps',
            'url' => 'rtmps://live-api-s.facebook.com:443/rtmp/',
            'stream_key' => $this->getStreamKey(),
            'format' => 'flv',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ];
    }

    public function getLiquidsoapOutput(Simulcasting $simulcasting, \App\Entity\Station $station): string
    {
        $config = $this->getConfiguration();
        $outputName = "simulcast_facebook_{$this->getCleanStreamName($simulcasting)}_{$simulcasting->getId()}";
        $instanceId = $simulcasting->getId();

        return <<<LIQ
        # Facebook Live (RTMPS) - Controllable source
        # Stefan from Liquidsoap IRC is a fucking GOD!
        {$outputName} = output.url(
            id="{$outputName}",
            url="{$config['url']}{$config['stream_key']}",
            start=false,
            fallible=true,
            restart_delay = null(),
            %ffmpeg(
                format="{$config['format']}",
                %video(
                    codec="{$config['video_codec']}",
                    pixel_format="yuv420p",
                    b=simulcast_v_bps,
                    preset="veryfast",
                    r=simulcast_v_fps,
                    g=simulcast_v_gop
                ),
                %audio(
                    codec="{$config['audio_codec']}",
                    samplerate=44100,
                    channels=2,
                    b=simulcast_a_bps,
                    profile="aac_low"
                )
            ),
            simulcast_videostream
        )

        # Telnet aliases to control it
        server.register(
        "{$outputName}_start",
        fun(_) -> begin
            {$outputName}.start();     # <- call with ()
            "OK"
        end
        )

        server.register(
        "{$outputName}_stop",
        fun(_) -> begin
            {$outputName}.stop();      # <- call with ()
            "OK"
        end
        )

        server.register(
        "{$outputName}_active",
        fun(_) ->
            if {$outputName}.is_active() then "true" else "false" end
        )

        server.register(
        "{$outputName}_status",
        fun(_) ->
            "id=" ^ {$outputName}.id()
            ^ " active="  ^ (if {$outputName}.is_active()  then "true" else "false" end)
            ^ " up="      ^ (if {$outputName}.is_up()      then "true" else "false" end)
            ^ " started=" ^ (if {$outputName}.is_started() then "true" else "false" end)
            ^ " ready="   ^ (if {$outputName}.is_ready()   then "true" else "false" end)
            ^ ""
        )
        LIQ;
    }
    
    private function getCleanStreamName(Simulcasting $simulcasting): string
    {
        $streamName = $simulcasting->getName();
        $cleanName = preg_replace('/[^a-zA-Z0-9_]/', '_', $streamName);
        return strtolower($cleanName);
    }
}
