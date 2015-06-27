/**
 * Ponyville Live!
 * Radio Player Script
 */

var volume = 70;
var nowplaying_song,
    nowplaying_song_id,
    nowplaying_url,
    nowplaying_station,
    np_cache;

var vote_ratelimit = false;

var is_playing,
    jp_is_playing;

var socket;

$(function() {

    // Check webstorage for existing volume preference.
    if (store.enabled && store.get('pvlive_player_volume') !== undefined)
        volume = store.get('pvlive_player_volume', 70);

	$('.nowplaying-status').hide();

	$('.station').click(function(e) {
		if (!$(this).closest('.station').hasClass('playing'))
		{
			e.preventDefault();
            playStation($(this).attr('id'));
		}
	});

	// Toggle display of the "Playback History" pane.
	$('.station .btn-show-history').click(function(e) {
		e.preventDefault();

		$(this).closest('.station').find('.station-history').slideToggle();
	});

	// "Like" links.
	$('.station .vote-wrapper a').click(function(e)
	{
		e.preventDefault();

		// Vote rate limiting.
		if (vote_ratelimit)
			return false;

		vote_ratelimit = true;

		// Action to call remotely.
		if ($(this).hasClass('btn-active'))
			var vote_function = 'clearvote';
		else if ($(this).hasClass('btn-like'))
			var vote_function = 'like';
		else
			var vote_function = 'dislike';

		// Trigger AJAX call.
		var songhist_id = intOrZero($(this).closest('.station').data('historyid'));

		if (songhist_id == 0)
			return false;

        /*
        var remote_url;
        if (typeof DF_ApiUrl !== 'undefined' && DF_ApiUrl != '')
            remote_url = DF_ApiUrl+'/song/'+vote_function;
        else
        */
        var remote_url = DF_BaseUrl+'/api/song/'+vote_function;

		jQuery.ajax({
			'type': 'GET',
			'url': remote_url,
			'dataType': 'json',
			'data': {
				'sh_id': songhist_id,
				'client': 'pvlwebapp'
			},
            'xhrFields': {
                'withCredentials': true
            }
		}).done(function(return_data) {
			vote_ratelimit = false;
			console.log(return_data);
		});

		// Update local graphics and text.
		var score_display = $(this).closest('.vote-wrapper').find('.nowplaying-score');
		var score_original = intOrZero(score_display.data('original'));

		if (vote_function == 'clearvote')
		{
			$(this).removeClass('btn-active');
			score_display.text(score_original);
		}
		else
		{
			$(this).siblings('a').removeClass('btn-active');
			$(this).addClass('btn-active');

			if (vote_function == 'like')
				var new_score = score_original + 1;
			else
				var new_score = score_original - 1;

			score_display.text(new_score);
		}

		return false;
	});

	// Social links.
	$('.station .btn-share-station').click(function(e) {
		e.preventDefault();
        e.stopPropagation();

        closeAllDropdowns();

		var shareLink = document.URL;

		var nowplaying_title = $(this).closest('.station').find('.nowplaying-artist').text();
		var nowplaying_artist = $(this).closest('.station').find('.nowplaying-title').text();
		if (nowplaying_artist)
			var shareTitle = '"'+nowplaying_title+'" by '+nowplaying_artist;
		else
			var shareTitle = '"'+nowplaying_title+'"';

		var station_name = $(this).closest('.station').data('name');
		shareTitle = 'I\'m tuned in to '+shareTitle+' on '+station_name+'. Check it out!';

		var shareMedia = $(this).closest('.station').find('.station-image').prop('src');

		if ($(this).hasClass('btn-share-facebook'))
		{
			window.open('//www.facebook.com/share.php?s=100&p[url]=' + encodeURIComponent(shareLink) + '&p[images][0]=' + encodeURIComponent(shareMedia) + '&p[title]=' + encodeURIComponent(shareTitle),'Facebook','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
		}
		else if ($(this).hasClass('btn-share-twitter'))
		{
			window.open('//twitter.com/home?status=' + encodeURIComponent(shareTitle) + '+' + encodeURIComponent(shareLink),'Twitter','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=600,width=600');
		}
	});

    // Song profile link.
    $('.station .song-info, .station .btn-song-info').on('click', function(e) {
        if ($(this).closest('.station').hasClass('playing'))
        {
            e.preventDefault();
            e.stopPropagation();

            var song_id = $(this).closest('.station').data('songid');
            if (song_id != "")
                showSongInfo(song_id);

            return false;
        }
    });

    /* Upcoming Schedule button. */
    $('.nowplaying-onair, .btn-station-schedule').on('click', function(e) {
        if ($(this).closest('.station').hasClass('playing'))
        {
            e.preventDefault();

            var station_id = $(this).closest('.station').data('id');
            showStationSchedule(station_id);
        }
    });

    // "Browse Stations" link.
	$('#btn_browse_stations').click(function(e) {
		playInPopUp(0);
		e.preventDefault();
	});

    /* Switch Stream button. */
    $('.btn-switch-stream').on('click', function(e) {
        e.preventDefault();

        // Stop the current playing song.
        stopAllPlayers();

        var station = $(this).closest('.station');

        var stream_id = $(this).closest('li').attr('rel');
        station.data('streamid', stream_id);
        station.removeData('songid');

        nowplaying_song_id = null;
        nowplaying_song = null;

        // Force stream metadata switch.
        processNowPlaying();

        // Play the new stream URL.
        playStation(station.attr('id'));

        // Persist the stream change for future page loads.
        jQuery.ajax({
            'type': 'POST',
            'url': DF_BaseUrl+'/profile/stream',
            'dataType': 'text',
            'data': {
                'station': station.data('id'),
                'stream': stream_id
            }
        }).done(function(return_data) {
            console.log('Persist stream preference: '+return_data);
        });
    });

    /* Websocket Interaction */

    socket = io(pvlnode_remote_url, {path: pvlnode_remote_path});

    socket.on('connect', function(){});
    socket.on('disconnect', function(){});

    socket.on('nowplaying', function(e) {
        console.log('Nowplaying updated.');

        np_cache = e;
        processNowPlaying();

        console.log(np_cache);

        // Send message to iframe listeners.
        top.postMessage({
            type: 'nowplaying',
            body: JSON.stringify(e)
        }, '*');
    });

    socket.on('schedule.event_upcoming', function(e) {
        console.log(e);

        var station_name = e.station.name;
        var station_image = e.station.image_url;
        var station_id = 'station_'+ e.station.shortcode;

        // Only show events for visible stations.
        if ($('#'+station_id).length == 0)
            return false;

        var event_name = e.event.title;
        var event_url = e.event.web_url;

        notify(station_image, event_name, 'Coming up on '+station_name, {
            tag: 'event_upcoming',
            timeout: 15
        });

        // Send message to iframe listeners.
        top.postMessage({
            type: 'event_upcoming',
            body: JSON.stringify(e)
        }, '*');
    });

    socket.on('podcast.new_episode', function(e) {
        console.log(e);

        // Don't show on embedded pages.
        if (window.self !== window.top)
            return false;

        var podcast_name = e.podcast.name;
        var podcast_image = e.podcast.image_url;

        var episode_name = e.episode.title;
        var episode_url = e.episode.web_url;

        notify(podcast_image, podcast_name, 'New episode available:\n'+episode_name, {
            tag: 'podcast_episode',
            timeout: 15
        });

        // Send message to iframe listeners.
        top.postMessage({
            type: 'podcast_episode',
            body: JSON.stringify(e)
        }, '*');
    });

    // Autoplay registration.
    if (typeof pvl_autoplay_station !== "undefined") {
        var autoplay_triggered = false;

        socket.on('nowplaying', function(e) {
            if (!autoplay_triggered) {
                autoplay_triggered = true;
                playStation($('.station[data-id="'+pvl_autoplay_station+'"]').attr('id'));
            }
        });
    }
});

function processNowPlaying()
{
    var listener_total = 0;
    var listeners_by_type = [];

    _.each(np_cache, function(station_info, station_id)
    {
        var category = station_info.station.category;
        var listeners = 0;

        if (category == 'video')
            listeners = processVideoNowPlayingRow(station_info, station_id);
        else
            listeners = processAudioNowPlayingRow(station_info, station_id);

        listener_total += listeners;

        if (typeof(listeners_by_type[category]) == 'undefined')
            listeners_by_type[category] = 0;

        listeners_by_type[category] += listeners;
    });

    // Aggregate listener totals.
    for(type_name in listeners_by_type)
    {
        $('#nowplaying-listeners-'+type_name).html('<i class="icon-user"></i>&nbsp;'+listeners_by_type[type_name]);
    }
    $('#nowplaying-listeners-total').html('<i class="icon-user"></i>&nbsp;'+listener_total);

    if ($('.video-channel.online').length > 0)
    {
        $('.video-listing').show();
        processMultiRows();
    }
    else
    {
        $('.video-listing').hide();
    }
}

function processAudioNowPlayingRow(station_info, station_id)
{
    var station = $('#station_'+station_id);
    var station_exists = (station.length != 0);
    var listener_total = 0;

    if (!station_exists)
        return 0;

    var stream_id = parseInt(station.data('streamid'));
    var stream = _(station_info.streams).find({'id': stream_id });

    if (typeof stream == 'undefined')
    {
        station.hide();
        return 0;
    }

    // Set stream URL.
    station.data('stream', stream.url);
    station.data('type', stream.type);

    // Highlight active stream.
    station.find('.stream-switcher ul li').removeClass('active');
    station.find('.stream-switcher ul li[rel="'+stream_id+'"]').addClass('active');

    // Format title.
    var song_tooltip = '';

    if (!stream.current_song.title)
    {
        station.find('.nowplaying-artist').text(stream.current_song.text);
        station.find('.nowplaying-title').text('');

        song_tooltip += stream.current_song.text;
    }
    else
    {
        station.find('.nowplaying-artist').text(stream.current_song.title);
        station.find('.nowplaying-title').text(stream.current_song.artist);

        song_tooltip += stream.current_song.title + "\n" + stream.current_song.artist;
    }

    if (_.size(stream.current_song.external) > 0)
    {
        station.find('.song-info-button').show();

        song_tooltip += "\n" + 'More Info Available';
    }
    else
    {
        station.find('.song-info-button').hide();
    }

    // Set hover tooltip for song.
    station.find('.song-info').attr('title', song_tooltip);

    // Show stream info if non-default.
    if (station.data('defaultstream') != stream_id)
    {
        station.find('.genre-info').hide();
        station.find('.stream-info').html('<i class="icon-code-fork"></i> '+stream.name).show();
    }
    else
    {
        station.find('.genre-info').show();
        station.find('.stream-info').hide();
    }

    // Trigger notification of song change.
    if (station.hasClass('playing'))
    {
        if (stream.current_song.id != nowplaying_song_id && jp_is_playing) {
            notify(station.data('image'), station_info.station.name, stream.current_song.text, { tag: 'nowplaying' });
        }

        nowplaying_song = stream.current_song.text;
        nowplaying_song_id = stream.current_song.id;
    }

    // Post listener count.
    var station_listeners = intOrZero(station_info.listeners.current);

    if (station_listeners >= 0)
    {
        listener_total += station_listeners;
        station.find('.nowplaying-listeners').show().html('<i class="icon-user"></i>&nbsp;'+station_listeners);
    }
    else
    {
        station.find('.nowplaying-listeners').hide();
    }

    // Post listener count for each stream.
    _(station_info.streams).forEach(function(stream_row)
    {
        var stream_listeners = intOrZero(stream_row.listeners.current);

        if (stream_row.status == 'online')
            station.find('li[rel="'+stream_row.id+'"]').show();
        else
            station.find('li[rel="'+stream_row.id+'"]').hide();

        station.find('li[rel="'+stream_row.id+'"] .nowplaying-stream-listeners').html('<i class="icon-user"></i>'+stream_listeners);
    });

    // Style offline/online stations properly.
    if (stream.status == 'offline')
    {
        station.addClass('offline');

        if (station.data('inactive') == 'hide')
            station.hide();
    }
    else
    {
        station.removeClass('offline');

        if (!station.is(':visible'))
            station.show();
    }

    // Set event data.
    var event_info;

    if (station_info.event.title)
    {
        event_info = station_info.event;
        station.find('.nowplaying-onair').show().html('<i class="icon-star"></i>&nbsp;On Air: '+event_info.title);
    }
    else if (station_info.event_upcoming.title)
    {
        event_info = station_info.event_upcoming;
        station.find('.nowplaying-onair').show().html('<i class="icon-star"></i>&nbsp;In '+intOrZero(event_info.minutes_until)+' mins: '+event_info.title);
    }
    else
    {
        station.find('.nowplaying-onair').empty().hide();
    }

    // Set station history.
    if (stream.song_history)
    {
        var history_block = '';
        var i = 1;

        _(stream.song_history).forEach(function(history_row) {
            var history_song;
            if (history_row.song.title != '')
                history_song = history_row.song.artist+' - '+history_row.song.title;
            else
                history_song = history_row.song.text;

            history_block += '<div>#'+i+': <a href="#" onclick="showSongInfo(\''+history_row.song.id+'\'); return false;">'+history_song+'</a></div>';
            i++;
        });

        station.find('.station-history').html(history_block);
    }

    var current_song_id = station.data('songid');
    var song_id = stream.current_song.id;

    // Detect a change in song.
    if (current_song_id != song_id)
    {
        station.find('.vote-wrapper a').removeClass('btn-active');

        var song_score = intOrZero(stream.current_song.score);
        station.find('.nowplaying-score').data('original', song_score).text(song_score);
    }

    station.data('songid', song_id);

    var song_history_id = intOrZero(stream.current_song.sh_id);
    station.data('historyid', song_history_id);

    return listener_total;
}

function processVideoNowPlayingRow(station_info, station_id)
{
    var listener_total = 0;

    _(station_info.streams).forEach(function(stream_info) {
        var station = $('#channel_' + station_id + '_' + stream_info.id);
        var station_exists = (station.length != 0);

        if (!station_exists)
            return 0;

        // Post listener count.
        var station_listeners = intOrZero(stream_info.meta.listeners);

        if (station_listeners >= 0)
        {
            listener_total += station_listeners;
            station.find('.nowplaying-listeners').show().html('<i class="icon-user"></i>&nbsp;' + station_listeners);
        }
        else
        {
            station.find('.nowplaying-listeners').hide();
        }

        if (stream_info.on_air.thumbnail)
        {
            station.find('img.video-thumbnail').attr('src', stream_info.on_air.thumbnail);
        }

        // Style offline/online stations properly.
        if (stream_info.meta.status == 'offline')
        {
            station.removeClass('online offline').addClass('offline').hide();
        }
        else
        {
            if (!station.is(':visible'))
            {
                notify(station_info.station.image_url, station_info.station.name, 'Stream online!', {tag: 'nowplaying'});
            }

            station.removeClass('online offline').addClass('online').show();
        }

        // Set event data.
        var event_info;

        if (station_info.event.title)
        {
            event_info = station_info.event;
            station.find('.nowplaying-onair').show().find('.nowplaying-inner').html('<i class="icon-star"></i>&nbsp;On Air: ' + event_info.title);
        }
        else if (station_info.event_upcoming.title)
        {
            event_info = station_info.event_upcoming;
            station.find('.nowplaying-onair').show().find('.nowplaying-inner').html('<i class="icon-star"></i>&nbsp;In ' + intOrZero(event_info.minutes_until) + ' mins: ' + event_info.title);
        }
        else
        {
            station.find('.nowplaying-onair').empty().hide();
        }
    });

    return listener_total;
}

function playStation(id)
{
	var station = $('#'+id);

	var stream_type = station.data('type');
	var stream_url = station.data('stream');
	nowplaying_url = stream_url;

	var currently_playing = station.hasClass('playing');

	if (currently_playing)
	{
		stopAllPlayers();
	}
	else
	{
		if (stream_url && stream_type)
		{
			stopAllPlayers();

            station.find('.station-player-container').append('<div id="pvl-jplayer"></div><div id="pvl-jplayer-controls"></div>');
            $('#pvl-jplayer-controls').append($('#pvl-jplayer-controls-image').html());

            $("#pvl-jplayer").jPlayer({
                ready: function (event) {
                    ready = true;
                    startPlayer(nowplaying_url);
                },
                pause: function() {
                    stopAllPlayers();
                },
                play: function() {
                    jp_is_playing = true;
                },
                suspend: function(event) {
                    console.log('Stream Suspended');

                    jp_is_playing = false;
                },
                error: function(event) {
                    var error_details = event.jPlayer.error;
                    console.log('Error: '+error_details.message+' - '+error_details.hint);
                    jp_is_playing = false;

                    // Auto-replay if Media URL load failure.
                    if (error_details.message == 'Media URL could not be loaded.')
                        startPlayer(nowplaying_url);
                    else
                        stopAllPlayers();
                },
                volumechange: function(event) {
                    volume = Math.round(event.jPlayer.options.volume * 100);

                    // Persist to local webstorage if enabled.
                    if (store.enabled)
                        store.set('pvlive_player_volume', volume);
                },
                wmode: 'window',
                swfPath: DF_ContentPath+'/jplayer/jquery.jplayer.swf',
                solution: (canPlayMp3()) ? 'html, flash' : 'flash',
                supplied: 'mp3',
                preload: 'none',
                volume: (volume/100),
                muted: false,
                backgroundColor: '#000000',
                cssSelectorAncestor: '#pvl-jplayer-controls',
                errorAlerts: false,
                warningAlerts: false
            });

            station.addClass('playing');
            nowplaying_station = id;

            $('#tunein_player').data('current_station', station.data('id'));

            // Force a reload of the "now playing" data.
            processNowPlaying();
			
			// Log in Google Analytics
			ga('send', 'event', 'Station', 'Play', station.data('name'));
		}
		else
		{
			$('#player').text('Error: This stream is not currently active. Please select another stream to continue.');
		}
	}
}

var check_interval;

function startPlayer()
{
	var stream = {
		title: "Ponyville Live!",
		mp3: getPlaybackUrl()
	};

	$("#pvl-jplayer").jPlayer("setMedia", stream).jPlayer("play");

	is_playing = true;
	check_interval = setInterval('checkPlayer()', 1500);
}

function getPlaybackUrl()
{
	var playback_url = addParameter(nowplaying_url, 'played_at', getUnixTimestamp());
	console.log('Playback URL: '+playback_url);

	return playback_url;
}

function checkPlayer()
{
	if (is_playing && !jp_is_playing)
	{
		clearInterval(check_interval);
		startPlayer();
	}
}

function stopAllPlayers()
{
	if ($("#pvl-jplayer").length > 0 && is_playing)
	{
		try
		{
			$('#pvl-jplayer').jPlayer('stop');
		}
		catch(e) {}

		try
		{
			$('#pvl-jplayer').jPlayer("clearMedia").jPlayer("destroy");
		}
		catch(e) {}		
	}

	is_playing = false;
    nowplaying_station = null;

	$('.station .station-player-container').empty();
	$('.station-history').hide();

	$('.station').removeClass('playing');

	$('#tunein_player').removeData('current_station');
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

function playInPopUp(station_id) {
	var current_station = $('#tunein_player').data('current_station');

	if (station_id == 'current')
		station_id = current_station;

	if (current_station)
		stopAllPlayers();

	if (station_id !== undefined)
		orig_url += '/id/'+station_id;

	// Force mobile page.
	orig_url = '//ponyvillelive.com/mobile';

	window.open(orig_url, 'pvl_player', 'height=600,width=400,status=yes,scrollbars=yes', true);
}

/* Station schedule popup. */
function showStationSchedule(station_id)
{
    console.log('Station Upcoming Schedule: '+station_id);

    var url = DF_BaseUrl+'/index/upcoming/id/'+station_id;
    modalPopup(url, {
        'width': 400,
        'minWidth': 400,
        'maxWidth': 400,
        'maxHeight': 500
    });
}

/**
 * Utility Functions
 */

function notify(image, title, description, opts)
{
    // Defaults (including title/desc parameters).
    var options = {
        tag: 'pvlnotify',
        icon: image,
        body: description,
        timeout: 5,
        notifyClose: null,
        notifyClick: null
    };

    // Merge options.
    for (var i in opts) {
        if (opts.hasOwnProperty(i)) {
            options[i] = opts[i];
        }
    }

    if (DF_AppEnv != 'production') {
        var dev_env_name = DF_AppEnv.charAt(0).toUpperCase() + DF_AppEnv.slice(1);
        title = '('+dev_env_name+') '+title;
    }

    // Always request permission, skip to granted if already done.
    Notify.requestPermission(function() {

        console.log([title, options]);

        var notice = new Notify(title, options);
        notice.show();

    });
}