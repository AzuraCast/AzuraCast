/**
 * Ponyville Live!
 * Radio Player Script
 */

var volume = 70;
var nowplaying_song = '';
var nowplaying_url = '';
var original_window_title;

var vote_ratelimit = false;

var nowplaying_last_run = 0;
var nowplaying_timeout;
var nowplaying_interval;

var is_playing;
var jp_is_playing;

$(function() {
	original_window_title = document.title;

	$('.nowplaying-status').hide();

	$('.station').click(function(e) {
		if (!$(this).closest('.station').hasClass('playing'))
		{
			e.preventDefault();
			initNotifications();

			if ($(this).data('popup'))
				playInPopUp($(this).data('id'));
			else
				playStation($(this).attr('id'));
		}
	});
	
	$('.station .station-player').click(function(e) {
		e.stopPropagation();
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
		setTimeout(function() {
			vote_ratelimit = false;
		}, 500);

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

		var remote_url = $(this).attr('href')+'/'+vote_function+'/sh_id/'+songhist_id;

		jQuery.ajax({
			'url': remote_url,
			'dataType': 'json'
		}).done(function(return_data) {
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

	$('#btn_browse_stations').click(function(e) {
		playInPopUp(0);
		e.preventDefault();
	});

	checkNowPlaying();
	nowplaying_interval = setInterval('verifyNowPlaying()', 30000);
});

// Ensure now-playing is being checked, in spite of any interruptions.
function verifyNowPlaying()
{
	var current_timestamp = getUnixTimestamp();
	if (current_timestamp - nowplaying_last_run > 25)
	{
		checkNowPlaying();
	}
}

function checkNowPlaying(force_update)
{
	force_update = (typeof force_update !== 'undefined') ? force_update : false;

	// Only run now-playing once every 10 seconds max.
	var current_timestamp = getUnixTimestamp();
	if (current_timestamp - nowplaying_last_run < 10 && !force_update)
		return;

	nowplaying_last_run = getUnixTimestamp();

	jQuery.ajax({
		cache: false,
		url: '//ponyvillelive.com/static/api/nowplaying.json',
		dataType: 'json'
	}).done(function(data) {
		var listener_total = 0;
		var listeners_by_type = new Array();

		for (var station_id in data)
		{
			var station_info = data[station_id];
			var station = $('#station_'+station_id);
			var station_exists = (station.length != 0);

			if (station_exists)
			{
				// Format title.
				if (!station_info.title)
				{
					station.find('.nowplaying-artist').text(station_info.text);
					station.find('.nowplaying-title').text('');
				}
				else
				{
					station.find('.nowplaying-artist').text(station_info.title);
					station.find('.nowplaying-title').text(station_info.artist);
				}

				// Trigger notification of song change.
				if (station.hasClass('playing'))
				{
					if (station_info.text != nowplaying_song)
						notify(station.data('image'), station_info.name, station_info.text);

					nowplaying_song = station_info.text;
				}

				// Post listener count.
				if (station_info.listeners)
				{
					listener_total += station_info.listeners;

					if (typeof(listeners_by_type[station_info.category]) == 'undefined')
						listeners_by_type[station_info.category] = 0;

					listeners_by_type[station_info.category] += station_info.listeners;

					station.find('.nowplaying-listeners').show().html('<i class="icon-user"></i>&nbsp;'+station_info.listeners);
				}
				else
				{
					station.find('.nowplaying-listeners').hide();
				}

				// Set station "live" status or "offline" status.
				if (station_info.is_live == 'true')
				{
					station.find('.nowplaying-live').show();
					station.removeClass('offline').addClass('live');

					if (!station.is(':visible'))
						station.show();
				}
				else if (station_info.status == 'offline')
				{
					station.find('.nowplaying-live').hide();
					station.removeClass('live').addClass('offline');

					if (station.data('inactive') == 'hide')
						station.hide();
				}
				else
				{
					station.find('.nowplaying-live').hide();
					station.removeClass('live offline');

					if (!station.is(':visible'))
						station.show();
				}

				// Set image, if supplied.
				if (station.hasClass('playing'))
				{
					station.find('img.media-object').attr('src', station_info.image);

					document.title = '\u25B6 '+station_info.name+' - '+station_info.text;
				}
				else
				{
					// Disabled for performance.
					// station.find('img.media-object').attr('src', station.data('image'));
				}

				// Set event data.
				if (station_info.event)
				{
					var event_info = station_info.event;

					if (station.is(':visible') && !station.find('.nowplaying-onair').is(':visible'))
						notify(station.data('image'), 'Now On Air: '+event_info.title, 'Tune in now on '+station_info.name);

					station.find('.nowplaying-onair').show().html('<i class="icon-star"></i>&nbsp;On Air: '+event_info.title);
				}
				else if (station_info.event_upcoming)
				{
					var event_info = station_info.event_upcoming;

					station.find('.nowplaying-onair').show().html('<i class="icon-star"></i>&nbsp;In '+event_info.minutes_until+' mins: '+event_info.title);
				}
				else
				{
					station.find('.nowplaying-onair').empty().hide();
				}

				// Set station history.
				if (station_info.song_history)
				{
					var history_block = '';

					for (var j in station_info.song_history)
					{
						var song_num = parseInt(j)+1;
						var history_row = station_info.song_history[j];

						history_block += '<div>#'+song_num+": "+history_row.text+'</div>';
					}

					station.find('.station-history').html(history_block);
				}

				var song_history_id = intOrZero(station_info.song_sh_id);
				station.data('historyid', song_history_id);

				if (station_info.song_id)
				{
					// Detect a change in song.
					var current_song_id = station.data('song_id');
					if (station_info.song_id != current_song_id)
					{
						station.find('.vote-wrapper a').removeClass('btn-active');

						var song_score = intOrZero(station_info.song_score);
						station.find('.nowplaying-score').data('original', song_score).text(song_score);
					}

					station.data('song_id', station_info.song_id);
				}

			}
		}

		for(type_name in listeners_by_type)
		{
			$('#nowplaying-listeners-'+type_name).html('<i class="icon-user"></i>&nbsp;'+listeners_by_type[type_name]);
		}
		$('#nowplaying-listeners-total').html('<i class="icon-user"></i>&nbsp;'+listener_total);

		nowplaying_timeout = setTimeout('checkNowPlaying()', 20000);
	});
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

			if (stream_type == "stream")
			{
				window.open(stream_url, 'pvlive_stream', 'width=980,height=700,location=yes,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=yes,toolbar=no');
			}
			else
			{
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

						stopAllPlayers();
					},
					volumechange: function(event) {
						volume = Math.round(event.jPlayer.options.volume * 100);
					},
					swfPath: DF_ContentPath+'/jplayer/jplayer.swf',
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

				// station.find('i.current-status').removeClass('icon-stop icon-play').addClass('icon-stop');
				// station.find('.station-play-button').html('<i class="icon-pause"></i>');

				$('#tunein_player').data('current_station', station.data('id'));

				// Trigger an immediate now-playing check.
				checkNowPlaying(true);
			}
			
			// Log in Google Analytics
			// _gaq.push(['_trackEvent', 'Station', 'Play', station.data('name') ]);
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

	clearInterval(check_interval);
	is_playing = false;

	// $('i.current-status').removeClass('icon-stop icon-play').addClass('icon-play');
	// $('.nowplaying-status').hide();

	$('.station .station-player-container').empty();
	$('.station-history').hide();
	$('.station').removeClass('playing');

	$('#tunein_player').removeData('current_station');

	/*
	// Reset now-playing images.
	$('.station').each(function() {
		$(this).find('.station-play-button').html('<i class="icon-play"></i>');
		$(this).find('img.media-object').attr('src', $(this).data('image'));
	});
	*/

	document.title = original_window_title;
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
	/*
	orig_url = "<?=$this->route(array('action' => 'tunein', 'origin' => 'home')) ?>";
	*/

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

/**
 * Utility Functions
 */

function intOrZero(number)
{
	return parseInt(number) || 0;
}

function addParameter(url, parameterName, parameterValue, atStart)
{
    replaceDuplicates = true;
    if(url.indexOf('#') > 0){
        var cl = url.indexOf('#');
        urlhash = url.substring(url.indexOf('#'),url.length);
    } else {
        urlhash = '';
        cl = url.length;
    }
    sourceUrl = url.substring(0,cl);

    var urlParts = sourceUrl.split("?");
    var newQueryString = "";

    if (urlParts.length > 1)
    {
        var parameters = urlParts[1].split("&");
        for (var i=0; (i < parameters.length); i++)
        {
            var parameterParts = parameters[i].split("=");
            if (!(replaceDuplicates && parameterParts[0] == parameterName))
            {
                if (newQueryString == "")
                    newQueryString = "?";
                else
                    newQueryString += "&";
                newQueryString += parameterParts[0] + "=" + (parameterParts[1]?parameterParts[1]:'');
            }
        }
    }
    if (newQueryString == "")
        newQueryString = "?";

    if(atStart){
        newQueryString = '?'+ parameterName + "=" + parameterValue + (newQueryString.length>1?'&'+newQueryString.substring(1):'');
    } else {
        if (newQueryString !== "" && newQueryString != '?')
            newQueryString += "&";
        newQueryString += parameterName + "=" + (parameterValue?parameterValue:'');
    }
    return urlParts[0] + newQueryString + urlhash;
};

function getUnixTimestamp()
{
	return Math.round((new Date()).getTime() / 1000);
}

function initNotifications()
{
	if (window.webkitNotifications)
	{
		var havePermission = window.webkitNotifications.checkPermission();

		if (havePermission != 0) // 0 is PERMISSION_ALLOWED
		{
			window.webkitNotifications.requestPermission();
		}
	}
}

function notify(image, title, description)
{
	if (window.webkitNotifications)
	{
		var havePermission = window.webkitNotifications.checkPermission();

		if (havePermission == 0) // 0 is PERMISSION_ALLOWED
		{
			var notification = window.webkitNotifications.createNotification(image, title, description);
			notification.onclick = function(x) {
				window.focus();
				this.cancel();
			};

			setTimeout(function() {
				notification.cancel();
			}, 5000);
			notification.show();
		}
	}
	else
	{
		try
		{
			Notification.requestPermission(function (perm)
			{
				console.log(perm);
				
				if (perm == 'granted')
				{
					var notification = new Notification(title, {
						body: description,
						dir: 'auto',
						lang: '',
						tag: 'pvl_radio',
						icon: image
					});

					notification.onclick = function(x) {
						window.focus();
					};
				}
			});
		}
		catch(e) { }
	}
}