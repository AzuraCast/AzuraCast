$(function() {

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

	$('.carousel').carousel();

});