/**
 * Radio Player Script
 */

var volume = 55,
    is_playing = false,
    player,
    $player;

function stopAllPlayers()
{
    player.pause();
    player.src = '';

    is_playing = false;

    $('.btn-audio').each(function() {
        var play_icon = $(this).removeClass('playing').find('i');
        if (play_icon.hasClass('material-icons')) {
            play_icon.text('play_circle_filled');
        } else {
            play_icon.removeClass('zmdi-stop').addClass('zmdi-play');
        }
    });

    $('#radio-player-controls').removeClass('jp-state-playing');
}

function setVolume(new_volume)
{
    volume = new_volume;

    var volume_percent = Math.round(volume * 100);
    $('.jp-volume-bar-value').css('width', volume_percent+'%');

    player.volume = Math.pow(volume,3);

    if (store.enabled)
        store.set('player_volume', volume*100);
}

function playAudio(source_url)
{
    player.src = source_url;
    player.play().catch(function(error) {
        console.error(error);
        stopAllPlayers();
    });
}

function handlePlayClick(audio_source)
{
    btn = $('.btn-audio[data-url="'+audio_source+'"]');

    if (btn.hasClass('playing')) {
        stopAllPlayers();
    } else {
        if (is_playing) {
            stopAllPlayers();
        }

        playAudio(audio_source);

        var play_icon = btn.addClass('playing').find('i');
        if (play_icon.hasClass('material-icons')) {
            play_icon.text('pause_circle_filled');
        } else {
            play_icon.removeClass('zmdi-play').addClass('zmdi-stop');
        }
    }
}

$(function() {

    // Check webstorage for existing volume preference.
    if (store.enabled && store.get('player_volume') !== undefined) {
        volume = store.get('player_volume', volume);
    }

    // Check the query string if browser supports easy query string access.
    if (typeof URLSearchParams !== 'undefined') {
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('volume')) {
            volume = parseInt(urlParams.get('volume'));
        }
    }

    $('.btn-audio').on('click', function(e) {
        e.preventDefault();
        handlePlayClick($(this).data('url'));
        return false;
    });

    // Create audio element.
    player = document.createElement('audio');
    $player = $(player);

    setVolume(volume/100);

    // Handle events.
    $player.on('play', function(e) {
        is_playing = true;

        $('.jp-unmute').hide();
        $('#radio-player-controls,#radio-embedded-controls').addClass('jp-state-playing');

        var volume_percent = Math.round($player.volume * 100);
        $('.jp-volume-bar-value').css('width', volume_percent+'%');
    });

    $player.on('ended', function(e) {
        stopAllPlayers();
    });

    if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('pause', function() {
            stopAllPlayers();
        });
    }

    // Handle button clicks.
    $('.jp-pause').on('click', function(e) {
        stopAllPlayers();
    });

    $('.jp-mute').on('click', function(e) {
        player.volume = 0;
        $('.jp-unmute').show();
        $('.jp-mute').hide();
    });

    $('.jp-unmute').on('click', function(e) {
        setVolume(volume);
        $('.jp-unmute').hide();
        $('.jp-mute').show();
    });

    $('.jp-volume-bar').on('click', function(e) {
        var $bar = $(e.currentTarget),
            offset = $bar.offset(),
            x = e.pageX - offset.left,
            w = $bar.width(),
            y = $bar.height() - e.pageY + offset.top,
            h = $bar.height();

        setVolume(x/w);
    });

    // Handle autoplay.
    $('.btn-audio[data-autoplay="true"]:first').click();

});
