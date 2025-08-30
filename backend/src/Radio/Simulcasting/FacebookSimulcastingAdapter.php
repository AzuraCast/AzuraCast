<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

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
        
        return <<<LIQ
        # Facebook Live (RTMPS)
        fb_url = "{$config['url']}{$config['stream_key']}"
        output.url(
            url=fb_url,
            fallible=true,
            %ffmpeg(
                format="{$config['format']}",
                %video.raw(
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
        LIQ;
    }
}
