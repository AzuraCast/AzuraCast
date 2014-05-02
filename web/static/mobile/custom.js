/**
 * Mobile Custom JS
 */

var pvl_player;

var is_playing = false;

var jp_is_playing = false;


var is_first_load = true;
var volume = 100;

var nowplaying_data;
var nowplaying_last_run = 0;
var nowplaying_url;
var nowplaying_timeout;
var nowplaying_interval;

var check_interval;

$(document).on("pageinit", function() {
	if (is_first_load)
	{
		$('#btn_back').hide();

		if (!DF_IsApp)
		{
			$("#pvl-jplayer").jPlayer({
				play: function() {
					jp_is_playing = true;

					// Progress event doesn't fire on iOS.
					if (isIOS())
						$.mobile.loading("hide");
				},
				progress: function(event) {
					if (event.jPlayer.status.currentTime > 0)
						$.mobile.loading("hide");
				},
				suspend: function(event) { 
					console.log('Stream Suspended');
					jp_is_playing = false;
				},
				error: function(event) {
					var error_details = event.jPlayer.error;
					console.error(error_details.message+' - '+error_details.hint);
					jp_is_playing = false;
				},
				swfPath: DF_ContentPath+'/jplayer/jplayer.swf',
				solution: getPlaybackSolution(),
				supplied: 'mp3',
				preload: 'none',
				volume: (volume / 100),
				muted: false,
				backgroundColor: '#000000',
				cssSelectorAncestor: '#pvl-jplayer-controls',
				errorAlerts: false,
				warningAlerts: false
			});

			if (isIOS())
				$('#player_volume').prop('disabled', true);
		}

		$('#player_controls').hide();

		$('#player_volume').attr('value', volume);
		$('#player_volume').slider({
			stop: function(event, ui) {
				volume = parseInt(event.target.value);
				playerSetVolume(volume);
			}
		});

		$('#player_controls #player_pause').on("click", function(event, ui) {
			stopAllPlayers();
		});

		$('body').on('click', '.btn_tunein', function(event) {
			playStation($(this).closest('.station').attr('id'));
		});

		$("[data-role='navbar']").navbar();
		$("[data-role='header'], [data-role='footer']").toolbar();

		nowplaying_interval = setInterval('verifyNowPlaying()', 30000);
	}
});

$(window).on("pagecontainershow", function(event) {
	is_first_load = false;

	// Force old page to be deleted.
	$("[data-role='page']:not(.ui-page-active)").remove();

	// Add "Home" button.
	var page_url = $("[data-role='page']").jqmData("url");
	if (page_url != '/mobile')
		$('#btn_back').show();
	else
		$('#btn_back').hide();

	// Change the heading
	var current = $('#page').jqmData("title");
	$("[data-role='header'] h1").text(current);

	// Remove active class from nav buttons
	$("[data-role='navbar'] a.ui-btn-active").removeClass("ui-btn-active");

	// Add active class to current nav button
	var page_url = window.location.href;

	$("[data-role='navbar'] a").each(function() {
		if ($(this).attr('href') == page_url)
			$(this).addClass("ui-btn-active");
	});

	// Force a reload (or re-print) of now playing data.
	checkNowPlaying();
});

// Ensure now-playing is being checked, in spite of any interruptions.
function verifyNowPlaying()
{
	var current_timestamp = getUnixTimestamp();
	if (current_timestamp - nowplaying_last_run > 25)
		checkNowPlaying();
}

function checkNowPlaying(force_update)
{
	force_update = (typeof force_update !== 'undefined') ? force_update : false;

	// Only run now-playing once every 10 seconds max.
	var current_timestamp = getUnixTimestamp();
	if (current_timestamp - nowplaying_last_run < 10 && !force_update)
	{
		processNowPlaying();
	}
	else
	{
		jQuery.ajax({
			cache: false,
			url: DF_ContentPath+'/api/nowplaying.json',
			dataType: 'json'
		}).done(function(data) {
			nowplaying_data = data;
			processNowPlaying();
		});
	}
}

function processNowPlaying()
{
	if (typeof nowplaying_data === 'undefined')
		return;

	for (var station_id in nowplaying_data)
	{
		var station_info = nowplaying_data[station_id];
		var station = $('[data-role="page"].ui-page-active #station_'+station_id);
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

			// Post listener count.
			if (station_info.listeners)
				station.find('.nowplaying-listeners').show().html('<i class="fa fa-user"></i>&nbsp;'+station_info.listeners);
			else
				station.find('.nowplaying-listeners').hide();

			// Set station "live" status or "offline" status.
			if (station_info.text == 'Stream Offline')
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

			// Set event data.
			if (station_info.event)
			{
				var event_info = station_info.event;
				station.find('.nowplaying-onair').show().find('.nowplaying-onair-inner').html('<i class="fa fa-star"></i>&nbsp;On Air: '+event_info.title);
			}
			else if (station_info.event_upcoming)
			{
				var event_info = station_info.event_upcoming;
				station.find('.nowplaying-onair').show().find('.nowplaying-onair-inner').html('<i class="fa fa-star"></i>&nbsp;In '+event_info.minutes_until+' mins: '+event_info.title);
			}
			else
			{
				station.find('.nowplaying-onair').hide();
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

			if (station_info.song_id)
			{
				// Detect a change in song.
				var current_song_id = station.data('song_id');
				if (station_info.song_id != current_song_id)
				{
					station.find('.btn-like').removeClass('btn-disabled').addClass('btn-success');
				}

				station.data('song_id', station_info.song_id);
			}

		}
	}

	nowplaying_last_run = getUnixTimestamp();
	// nowplaying_timeout = setTimeout('checkNowPlaying()', 20000);
}

function playStation(id)
{
	console.log('Playing station: '+id);

	var station = $('[data-role="page"].ui-page-active #'+id);

	var stream_type = station.data('type');
	var stream_url = station.data('stream');

	var currently_playing = station.hasClass('playing');

	if (stream_url && stream_type)
	{
		stopAllPlayers();

		if (stream_type == "stream")
		{
			if (DF_IsApp)
				window.open(stream_url);
			else
				window.location.href = stream_url;
			return false;
		}
		else
		{
			nowplaying_url = stream_url;

			if (!DF_IsApp)
			{
				$.mobile.loading( "show", {
		            text: 'Playing Station...',
		            textVisible: true,
		            theme: 'b',
		            textonly: false
			    });
			}

			startPlayer();

			// Trigger an immediate now-playing check.
			checkNowPlaying(true);
		}
		
		// Log in Google Analytics
		try {
			ga('send', 'event', 'Station', 'Play', station.data('name'));
		} catch(e) {}
	}
}

function startPlayer()
{
	if (DF_IsApp)
	{
		pvl_player = new Media(nowplaying_url, function() {
			playerSetVolume(volume);
            console.log("playAudio(): Audio Success");
        },
        function(err) {
            console.error("playAudio(): Audio Error");
            console.error(err);
    	},
    	function(status) {
    		console.log(status);
    	});

		pvl_player.play();
	}
	else
	{
		var stream = {
			title: "Ponyville Live!",
			mp3: nowplaying_url
		};
		$("#pvl-jplayer").jPlayer("setMedia", stream);
		$("#pvl-jplayer").jPlayer("play");

		check_interval = setInterval('checkPlayer()', 1500);
	}

	is_playing = true;
	$('.btn_tunein').hide();

	$('#player_controls').show();
	$("[data-role='header'], [data-role='footer']").toolbar("updatePagePadding");
}

function checkPlayer()
{
	if (is_playing && !DF_IsApp && !jp_is_playing && !isIOS())
	{
		clearInterval(check_interval);
		startPlayer();
	}
}

function stopAllPlayers()
{
	if (is_playing)
	{
		if (DF_IsApp && typeof pvl_player !== 'undefined')
		{
			pvl_player.stop();
			pvl_player.release();
		}
		else if ($("#pvl-jplayer").length > 0)
		{
			try
			{
				$('#pvl-jplayer').jPlayer('stop');
			}
			catch(e) {}

			try
			{
				$('#pvl-jplayer').jPlayer("clearMedia");
			}
			catch(e) {}
		}
	}

	is_playing = false;
	if (!DF_IsApp)
		clearInterval(check_interval);

	$('.btn_tunein').show();

	$('#player_controls').hide();
	$.mobile.loading("hide");

	nowplaying_url = null;
}

function playerSetVolume(volume)
{
	if (DF_IsApp)
	{
		pvl_player.setVolume(volume / 100);
	}
	else if (is_playing)
	{
		$('#pvl-jplayer').jPlayer('volume', (volume / 100));
	}
}

function getPlaybackSolution()
{
	if (isIOS())
		return 'html';
	else if (canPlayMp3())
		return 'html, flash';
	else
		return 'flash';
}

function canPlayMp3()
{
	if (isIOS())
		return true;

	if (isIE() || isSteam())
		return false;

	var a = document.createElement('audio');
	var can_play_mp3 = !!(a.canPlayType && a.canPlayType('audio/mpeg;').replace(/no/, ''));
	return can_play_mp3;
}

function isIE () {
	var myNav = navigator.userAgent.toLowerCase();
	return (myNav.indexOf('msie') != -1) ? parseInt(myNav.split('msie')[1]) : false;
}
function isIOS() {
	return navigator.userAgent.match(/(iPod|iPhone|iPad)/i);
}
function isSteam() {
	var myNav = navigator.userAgent.toLowerCase();
	return (myNav.indexOf('gameoverlay') != -1) ? true : false;
}

function getUnixTimestamp()
{
	return Math.round((new Date()).getTime() / 1000);
}