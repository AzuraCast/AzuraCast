$(function() {

    $('body').append('<div id="fap"></div>');

    $('#fap').fullwidthAudioPlayer({
        wrapperColor: '#f0f0f0',
        mainColor: '#3c3c3c', 
        fillColor: '#e3e3e3',
        metaColor: '#666666', 
        fillColorHover: '#d1d1d1', 
        strokeColor: '#e0e0e0', 
        activeTrackColor: '#E8E8E8',
        wrapperPosition: 'bottom', 
        mainPosition: 'center', 
        layout: 'fullwidth',
        autoPlay: 0,
        socials: 1,
        volume: 1, 
        playlist: 1, 
        loopPlaylist: 0,
        autoPopup: 0, 
        sortable: true
    });

    $('body').initPage();

    $('#form-search').submit(function(e) {
        e.preventDefault();

        var query = $(this).find('input[type="text"]').val();
        var url = $(this).attr('action')+'/search/'+query;

        History.pushState(null,'Search Results',url);

        return false;
    });

});

$.fn.initPage = function(){
    
    var $this = $(this);

    $this.find('.btn-play-song').on('click', function(e) {

        e.preventDefault();

        var media_url = $(this).attr('href');
        $.fullwidthAudioPlayer.addTrack(media_url, $(this).data('title'), $(this).data('artist'), $(this).data('cover'), media_url, true);

        /*
        $('#jp_jplayer').jPlayer("setMedia", {
            mp3: $(this).attr('href')
        }).jPlayer("play");

        $('#jp_container').slideDown();

        $('#nowplaying-title').text($(this).data('title'));
        $('#nowplaying-artist').text($(this).data('artist'));
        */

    });
    
    // Chain
    return $this;
};