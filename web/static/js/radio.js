/**
 * Radio Player Script
 */

var volume = 55,
    is_playing = false,
    player,
    $player;

function stopAllPlayers()
{
    is_playing = false;

    player.pause();
    player.src = '';

    $('.btn-audio').each(function() {
        var play_icon = $(this).removeClass('playing').find('i');
        if (play_icon.hasClass('material-icons')) {
            play_icon.text('play_circle_filled');
        }
    });

    $('#radio-player-controls').removeClass('jp-state-playing');
}

function setVolume(new_volume)
{
    volume = parseInt(new_volume);

    $('.jp-volume-bar-value').css('width', volume+'%');
    $('.jp-volume-range').val(volume);

    // Set volume logarithmically based on original input.
    player.volume = Math.min((Math.exp(volume/100)-1)/(Math.E-1), 1);

    if (store.enabled)
        store.set('player_volume', volume);
}

function playAudio(source_url)
{
    player.src = source_url;
    player.play();
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

    setVolume(volume);

    // Handle events.
    $player.on('play', function(e) {
        is_playing = true;

        $('.jp-unmute').hide();
        $('#radio-player-controls').addClass('jp-state-playing');
    });

    $player.on('error', function(e) {
        switch (e.target.error.code) {
            case e.target.error.MEDIA_ERR_NETWORK:
                var original_src = player.src;
                stopAllPlayers();

                if (original_src !== '') {
                    console.log('Network interrupted stream. Automatically reocnnecting shortly...');
                    setTimeout(function() {
                        handlePlayClick(original_src);
                    }, 2000);
                }
                break;

            case e.target.error.MEDIA_ERR_SRC_NOT_SUPPORTED:
            case e.target.error.MEDIA_ERR_ABORTED:
            case e.target.error.MEDIA_ERR_DECODE:
            default:
                console.error(e.target.error.message);
                break;
        }
    });

    $player.on('ended', function(e) {
        var original_src = player.src;
        var was_playing = is_playing;

        stopAllPlayers();

        if (was_playing && original_src !== '') {
            console.log('Network interrupted stream. Automatically reocnnecting shortly...');
            setTimeout(function() {
                handlePlayClick(original_src);
            }, 5000);
        }
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
        setVolume(0);
    });

    $('.jp-unmute').on('click', function(e) {
        setVolume(55);
    });

    $('.jp-volume-full').on('click', function(e) {
        setVolume(100);
    });

    $('.jp-volume-bar').on('click', function(e) {
        var $bar = $(e.currentTarget),
            offset = $bar.offset(),
            x = e.pageX - offset.left,
            w = $bar.width(),
            y = $bar.height() - e.pageY + offset.top,
            h = $bar.height();

        setVolume(Math.round((x/w) * 100));
    });

    $('.jp-volume-range').on('change', function(e) {
        setVolume($(this).val());
    });

});
