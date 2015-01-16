/**
 * Ponyville Live! Global JavaScript
 * Made with love for the ponyfolk. /)
 */

var clock_timeout;
var clock_interval = 90000;

$(function() {

	if ($('html').hasClass('theme_dark'))
	{
		$('.btn.btn-inverse').addClass('btn-normal');
		$('.btn').not('.btn-inverse,.btn-primary,.btn-success,.btn-warning,.btn-error').addClass('btn-inverse');
		$('.btn.btn-normal').removeClass('btn-inverse btn-normal');
	}

	$('#btn-tune-in,.btn-tune-in').click(function(e) {
		var href = $(this).attr('href');
		window.open(href, "pvlplayer", "width=400,height=600,menubar=0,toolbar=0,location=0,status=1");

		e.preventDefault();
		return false;
	});

	/* Indicate Flash support */
	$('html').addClass(typeof swfobject !== 'undefined' && swfobject.getFlashPlayerVersion().major !== 0 ? 'flash' : 'no-flash');

	/* Autoselect */
	$('.autoselect').each(function() {
		var active = $(this).attr('rel');
		$(this).find('li[rel="'+active+'"]').addClass('active');
	});
	
	/* Link fixes for iOS Apps */
	if (('standalone' in window.navigator) && window.navigator.standalone) {
		$('a').live('click', function() {
			if(!$(this).hasClass('noeffect')) {
			    window.location = $(this).attr('href');
			    return false;
			}
		});
	}

    /* Carousel and touch support. */
	$('.carousel').carousel();

	if ($().hammer)
	{
		var hammer_options = {};
		$('.carousel')
			.hammer(hammer_options)
			.on("swipeleft", function(ev) {
				$(this).carousel('next');
			})
			.on("swiperight", function(ev) {
				$(this).carousel('prev');
			});
	}

    /* Song Information button. */
    $('.btn-show-song-info').on('click', function(e) {
        e.preventDefault();

        var song_id = $(this).data('id');
        showSongInfo(song_id);
    });

    /* Time synchronization. */
    clock_timeout = setTimeout('updateTime()', clock_interval);
});

/* Song biography popup. */
function showSongInfo(song_id)
{
    console.log('Song information for ID: '+song_id);

    // All Song ID hashes are uniform length.
    if (song_id.length != 32)
        return false;

    var info_url = DF_BaseUrl+'/song/index/id/'+song_id;
    modalPopup(info_url, {
        'width': 600,
        'minWidth': 600,
        'maxWidth': 600,
        'height': 500,
        'minHeight': 500,
        'maxHeight': 500,
        'onComplete': function() {
            cleanUpSongInfo(); // Loaded in remote URL.
        }
    });
}

/* Clock synchronization. */
function updateTime()
{
    clearInterval(clock_interval);

    jQuery.ajax({
        cache: false,
        url: DF_BaseUrl+'/api/index/time/client/pvlwebapp',
        dataType: 'json'
    }).done(function(data) {
        var time_info = data.result;
        var new_current_time = time_info.local_time+' '+time_info.local_timezone_abbr;

        $('.current_time').text(new_current_time);

        clock_timeout = setTimeout('updateTime()', clock_interval);
    });
}

/* Fancybox (or iFrame if fancybox isn't available). */
function modalPopup(popup_url, params)
{
    params = $.extend({
        'type': 'ajax',
        'href': popup_url,
        'width': 600,
        'height': 500
    }, params);

    console.log(params);

    // Detect iframe.
    if (window!=window.top || !$.fn.fancybox)
        window.open(popup_url,'PVLModalPopup','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height='+params.height+',width='+params.width);
    else
        $.fancybox.open(params);
}