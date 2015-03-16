/**
 * Ponyville Live! Global JavaScript
 * Made with love for the ponyfolk. /)
 */

$(function() {

    processMultiRows();

	if ($('html').hasClass('theme_dark'))
	{
		$('.btn.btn-inverse').addClass('btn-normal');
		$('.btn').not('.btn-inverse,.btn-primary,.btn-success,.btn-warning,.btn-error').addClass('btn-inverse');
		$('.btn.btn-normal').removeClass('btn-inverse btn-normal');

        $('div.navbar').addClass('navbar-inverse');
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
		$(this).find('[rel="'+active+'"]').addClass('active');
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
	$('.carousel').carousel({
        interval: 10000
    });

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

    if ($.fn.nanoScroller)
    {
        $(".nano").nanoScroller();
    }

    /* Song Information button. */
    $('.btn-show-song-info').on('click', function(e) {
        e.preventDefault();

        var song_id = $(this).data('id');
        showSongInfo(song_id);
    });

    /* Time synchronization. */
    updateTime();
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
var clock_timeout;
var clock_interval = 60000;

function updateTime()
{
    clearTimeout(clock_timeout);

    var new_current_time = moment().utcOffset(PVL_UtcOffset).format('h:mmA');
    $('.current_time').text(new_current_time);

    clock_timeout = setTimeout('updateTime()', clock_interval);
}

/* Fancybox (or iFrame if fancybox isn't available). */
function modalPopup(popup_url, params)
{
    closeAllDropdowns();

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

function intOrZero(number)
{
    return parseInt(number) || 0;
}

function getUnixTimestamp()
{
    return Math.round((new Date()).getTime() / 1000);
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
}

function processMultiRows()
{
    // Add support for multiple rows for all divisible-by-12 columns.
    _.forEach([2, 3, 4, 6, 12], function(j) {

        var num_per_row = 12 / j;

        $('.row-multiple').each(function() {
            var i = 0;

            $(this).find('.span'+j).removeClass('start-of-row');
            $(this).find('.span'+j+':visible').each(function() {
                i++;
                if (i % num_per_row == 1)
                    $(this).addClass('start-of-row');
            });
        });

    });
}

function closeAllDropdowns()
{
    $('.dropdown.open').removeClass('open');
    $('.btn-group.open').removeClass('open');
}