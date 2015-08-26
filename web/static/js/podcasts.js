/**
 * Ponyville Live!
 * Podcast Episode Management
 */

$(function() {

    $('a.podcast-episode').each(function() {

        var link_href = $(this).attr('href');

        if (link_href.indexOf('youtube') != -1)
        {
            $(this).addClass('fancybox fancybox.media');
        }
        else if (link_href.indexOf('soundcloud') != -1)
        {
            $(this).addClass('fancybox fancybox.iframe');
        }
        else
        {
            $(this).attr('target', '_blank');
        }

        // Trigger AJAX call to log play on video, even if played modally or launched in new page.
        $(this).on('click', function(e) {
            var analytics_url = $(this).data('log');

            if (analytics_url)
            {
                jQuery.ajax({
                    'type': 'GET',
                    'url': analytics_url,
                    'dataType': 'json'
                }).done(function(return_data) {
                    console.log(return_data);
                });
            }
        });
    });

});