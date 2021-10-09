<?php
/**
 * @var App\Environment $settings
 * @var App\Version $version
 */

$releaseChannel = $version->getReleaseChannel();
$releaseChannelNames = [
    App\Version::RELEASE_CHANNEL_ROLLING => __('Rolling Release'),
    App\Version::RELEASE_CHANNEL_STABLE => __('Stable'),
];
$releaseChannelName = $releaseChannelNames[$releaseChannel];

