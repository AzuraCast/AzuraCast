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

    $('.btn-show-song-info').on('click', function(e) {
        e.preventDefault();

        var song_id = $(this).data('id');
        showSongInfo(song_id);
    });
});

/* Song biography popup. */
function showSongInfo(song_id)
{
    console.log('Song information for ID: '+song_id);

    // All Song ID hashes are uniform length.
    if (song_id.length != 32)
        return false;

    var info_url = '/song/index/id/'+song_id;

    // Detect iframe.
    if (window!=window.top)
    {
        window.open(info_url,'PVLSongInfo','menubar=no,toolbar=no,resizable=yes,scrollbars=yes,height=500,width=600');
    }
    else
    {
        $.fancybox.open({
            'type': 'ajax',
            'href': info_url,
            'width': 600,
            'height': 500,
            'minWidth': 600,
            'minHeight': 500,
            'maxWidth': 600,
            'maxHeight': 500,
            onComplete: function() {
                cleanUpSongInfo();
            }
        });
    }
}