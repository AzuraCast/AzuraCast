/**
 * Ponyville Live!
 * Video Player Script
 */

var video_np_cache;

$(function() {
    /* Websocket Interaction */
    if (socket)
    {
        socket.on('nowplaying_video', function(e) {
            console.log('Video Nowplaying updated.');

            video_np_cache = e;
            processVideoNowPlaying();

            console.log(video_np_cache);

            // Send message to iframe listeners.
            top.postMessage({
                type: 'nowplaying_video',
                body: JSON.stringify(e)
            }, '*');
        });
    }
});

function processVideoNowPlaying()
{
    var listener_total = 0;
    var online_stations = 0;

    _.each(video_np_cache, function(station_info, station_id)
    {
        var station = $('#channel_'+station_id);
        var station_exists = (station.length != 0);

        if (station_exists)
        {
            // Post listener count.
            var station_listeners = intOrZero(station_info.meta.listeners);

            if (station_listeners >= 0)
            {
                listener_total += station_listeners;
                station.find('.nowplaying-listeners').show().html('<i class="icon-user"></i>&nbsp;'+station_listeners);
            }
            else
            {
                station.find('.nowplaying-listeners').hide();
            }

            if (station_info.on_air.thumbnail)
            {
                station.find('img.video-thumbnail').attr('src', station_info.on_air.thumbnail);
            }

            // Style offline/online stations properly.
            if (station_info.meta.status == 'offline')
            {
                station.hide();
            }
            else
            {
                if (!station.is(':visible'))
                {
                    notify(station_info.station.image_url, station_info.station.name, 'Stream online!', { tag: 'nowplaying' });
                }

                station.show();
                online_stations++;
            }

            /*
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
            */
        }
    });

    if (online_stations == 0)
    {
        $('.video-listing').hide();
    }
    else
    {
        $('#nowplaying-listeners-video').html('<i class="icon-user"></i>&nbsp;'+listener_total);
        $('.video-listing').show();

        processMultiRows();
    }

}