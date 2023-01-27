# Known Issues

This file will include the majority of the known issues we're aware of with AzuraCast and that are in the stages of
review / investigation / fixing. We don't provide a date on the fixes but rest assured we're aware of them.

## Metadata Updates with Live DJs

When a live streamer/DJ connects to your stream, only the first metadata _update_ is received, rather than the initial
metadata when the client connects. This is often reported as AzuraCast "missing" the first song that a DJ broadcasts.
This is an [upstream issue](https://github.com/savonet/liquidsoap/issues/2109) with Liquidsoap that we are continuing to
follow. The easy workaround for most users is to connect while playing a very short track (such as an intro or station
ID), then immediately transition to another song after connecting, which triggers a second metadata update and fixes the
issue.

## WebDJ Audio Quality Issues

The WebDJ feature, on some browsers and in some situations, can broadcast sound that seems "crackly" and intermittent to
stream listeners. This is a known issue, but the cause of it is currently
unknown. [#5116](https://github.com/AzuraCast/AzuraCast/issues/5116)

The upstream Webstreamer software that we use has been updated and may possibly resolve this, but incorporating it will
require significant development effort on our end.

## Can't Skip Songs After DJ Disconnects

Many users have requested the ability for Liquidsoap to skip to the next song automatically after a DJ disconnects.
This is also an [upstream issue](https://github.com/savonet/liquidsoap/issues/2107) with Liquidsoap that we are
continuing to follow.

## Playlist Scheduling Problems

Users intermittently report problems with our AutoDJ scheduler not working correctly. These issues are often very
difficult for us to diagnose, even with the appropriate logs, as they are intermittent and hard to reproduce on other
installations. If you're a developer and you're interested in helping us with this, please let us know.

One known issue is that "Scheduled" type playlists always take priority over "General Rotation" playlists, which only
play when _no_ scheduled playlist is available to play. This is the intended functionality of AzuraCast, but some users
prefer non-exclusive schedule entries. The current workaround to this is to make scheduled copies of the General
Rotation playlists (even possibly scheduling them for all-day blocks), but we are looking into better solutions for
this.

## Ansible Issues

Our Ansible installation is no longer officially supported with our team's developer resources. Ansible installations
represent less than 5% of our total installed user base, and contribute much more to our support burden than that. If
you're experiencing Ansible issues, we would gladly review and accept any pull requests to help resolve it, but we will
not be devoting our own resources toward these issues.
