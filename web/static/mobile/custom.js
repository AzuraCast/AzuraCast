/**
 * Mobile Custom JS
 */

var is_playing = false;
var is_first_load = true;
var volume = 100;

var nowplaying_last_run = 0;
var nowplaying_timeout;
var nowplaying_interval;

$(document).on("pageinit", function() {
	if (is_first_load)
	{
		$('#btn_back').hide();

		$("#pvl-jplayer").jPlayer({
			swfPath: DF_ContentPath+'/jplayer/jplayer.swf',
			solution: (canPlayMp3()) ? 'html, flash' : 'flash',
			supplied: 'mp3',
			preload: 'none',
			volume: (volume / 100),
			muted: false,
			backgroundColor: '#000000',
			cssSelectorAncestor: '#pvl-jplayer-controls',
			errorAlerts: false,
			warningAlerts: false
		});

		$('#player_controls').hide();

		$('#player_volume').attr('value', volume);
		$('#player_volume').slider({
			stop: function(event, ui) {
				volume = parseInt(event.target.value);

				if (is_playing)
					$('#pvl-jplayer').jPlayer('volume', (volume / 100));
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

	if (!is_first_load)
		$('#btn_back').show();

	is_first_load = false;

	// Force old page to be deleted.
	$("[data-role='page']:not(.ui-page-active)").remove();

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

	// Detect autoplay station.
	checkNowPlaying(true);
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
		return;

	nowplaying_last_run = getUnixTimestamp();

	jQuery.ajax({
		cache: false,
		url: DF_ContentPath+'/api/nowplaying.json',
		dataType: 'json'
	}).done(function(data) {
		var listener_total = 0;
		var listeners_by_type = new Array();

		for (var station_id in data)
		{
			var station_info = data[station_id];
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

		nowplaying_timeout = setTimeout('checkNowPlaying()', 20000);
	});
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
			window.location.href = stream_url;
			return false;
		}
		else
		{
			startPlayer(stream_url);

			// Trigger an immediate now-playing check.
			checkNowPlaying(true);
		}
		
		// Log in Google Analytics
		ga('send', 'event', 'Station', 'Play', station.data('name'));
	}
}

function startPlayer(stream_url)
{
	var stream = {
		title: "Ponyville Live!",
		mp3: stream_url
	};

	is_playing = true;
	$('.btn_tunein').hide();

	$("#pvl-jplayer").jPlayer("setMedia", stream).jPlayer("play");
	$('#player_controls').show();
}

function stopAllPlayers()
{
	if ($("#pvl-jplayer").length > 0)
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

	is_playing = false;
	$('.btn_tunein').show();

	$('#player_controls').hide();
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

function getUnixTimestamp()
{
	return Math.round((new Date()).getTime() / 1000);
}