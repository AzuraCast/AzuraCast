<?php

declare(strict_types=1);

namespace App\Radio\Simulcasting;

class YouTubeSimulcastingAdapter extends AbstractSimulcastingAdapter
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
        return 'youtube';
    }

    public function getAdapterDescription(): string
    {
        return 'YouTube Live';
    }

    public function getConfiguration(): array
    {
        return [
            'type' => 'hls',
            'stream_key' => $this->getStreamKey(),
            'format' => 'mpegts',
            'video_codec' => 'libx264',
            'audio_codec' => 'aac',
        ];
    }

    public function getLiquidsoapOutput(Simulcasting $simulcasting, \App\Entity\Station $station): string
    {
        $config = $this->getConfiguration();
        
        return <<<LIQ
        # YouTube Live (HLS ingest)
        output.youtube.live.hls(
            key="{$config['stream_key']}",
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
