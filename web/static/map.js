var geocoder;
var map;
var latlng;
var latlngbounds;
var markers = [];

$(function() {
    initialize();

    $('.event').each(function() {
    	var city = $(this).data('city');
    	var title = $(this).text() + ' (' + city + ')';
    	var url = $(this).find('a').attr('href');

    	codeAddress(city, title, url);
    });
});

function initialize() {
    geocoder = new google.maps.Geocoder();
    latlng = new google.maps.LatLng(38.85682, -97.77832);

    var mapOptions = {
        zoom: 3,
        center: latlng,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    }
    map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
}

function codeAddress(address, name, url) {
    geocoder.geocode({
        address: address
    }, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
        	var marker = new google.maps.Marker({
                map: map,
                title: name,
                url: url,
                position: results[0].geometry.location
            });
			google.maps.event.addListener(marker, 'click', function() {
				window.location.href = this.url;
			});
		} else if (status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {    
            setTimeout(function() {
                codeAddress(address, name, url);
            }, 200);
        } else {
            console.log("Geocode was not successful for the following reason: " + status);
        }
    });
}