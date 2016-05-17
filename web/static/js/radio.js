/**
 * Radio Player Script
 */

var volume = 70,
    is_playing;

$(function() {

    // Check webstorage for existing volume preference.
    if (store.enabled && store.get('player_volume') !== undefined)
        volume = store.get('player_volume', 70);

    $(document).on("click", ".btn-audio", function(e) {
        e.preventDefault();
        
        if ($(this).hasClass('playing'))
        {
            stopAllPlayers();
        }
        else 
        {
            if ($('.btn-audio.playing').length != 0)
                stopAllPlayers();

            var audio_source = $(this).data('url');
            playAudio(audio_source);

            $(this).addClass('playing').find('i').removeClass('zmdi-play').addClass('zmdi-stop');
        }
        
        return false;
    });

});

function playAudio(source_url)
{
    $("#radio-player").jPlayer({
        ready: function (event) {
            ready = true;

            startPlayer(source_url);
        },
        pause: function() {
            stopAllPlayers();
        },
        play: function() {
            is_playing = true;
        },
        suspend: function(event) {
            console.log('Stream Suspended');

            is_playing = false;
        },
        error: function(event) {
            var error_details = event.jPlayer.error;
            console.log('Error: '+error_details.message+' - '+error_details.hint);
            is_playing = false;

            // Auto-replay if Media URL load failure.
            if (error_details.message == 'Media URL could not be loaded.')
                startPlayer(source_url);
            else
                stopAllPlayers();
        },
        volumechange: function(event) {
            volume = Math.round(event.jPlayer.options.volume * 100);

            // Persist to local webstorage if enabled.
            if (store.enabled)
                store.set('player_volume', volume);
        },
        wmode: 'window',
        swfPath: APP_ContentPath+'/vendors/bower_components/jPlayer/dist/jplayer/jquery.jplayer.swf',
        solution: (canPlayMp3()) ? 'html, flash' : 'flash',
        supplied: 'mp3',
        preload: 'none',
        volume: (volume/100),
        muted: false,
        backgroundColor: '#000000',
        cssSelectorAncestor: '#radio-player-controls',
        errorAlerts: false,
        warningAlerts: false
    });
}

function startPlayer(source_url)
{
    var stream = {
        title: "AzuraCast",
        mp3: source_url
    };

    $("#radio-player").jPlayer("setMedia", stream).jPlayer("play");

    is_playing = true;
    // check_interval = setInterval('checkPlayer()', 1500);
}

function stopAllPlayers()
{
    try
    {
        $('#radio-player').jPlayer('stop');
    }
    catch(e) {}

    try
    {
        $('#radio-player').jPlayer("clearMedia").jPlayer("destroy");
    }
    catch(e) {}

    is_playing = false;
    $('.btn-audio').removeClass('playing').find('i').removeClass('zmdi-stop').addClass('zmdi-play');
}

function canPlayMp3()
{
    if (isIE())
        return false;

    if (isSteam())
        return false;

    var a = document.createElement('audio');
    var can_play_mp3 = !!(a.canPlayType && a.canPlayType('audio/mpeg;').replace(/no/, ''));
    return can_play_mp3;
}

function isIE () {
    var myNav = navigator.userAgent.toLowerCase();
    return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}

function isSteam() {
    var myNav = navigator.userAgent.toLowerCase();
    return (myNav.indexOf('gameoverlay') != -1) ? true : false;
}