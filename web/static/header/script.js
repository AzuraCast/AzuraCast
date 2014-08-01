/**
 * Ponyville Live! Header Banner Include
 * Requires jQuery 1.8+
 *
 * Usage:
 * Immediately after <body> tag, add:
 * <a id="pvl_header_link" href="http://www.ponyvillelive.com/" target="_blank">Ponyville Live!</a>
 * <script type="text/javascript" src="http://www.ponyvillelive.com/static/header/script.js"></script>
 */

if (jQuery) {

	(function($) { 

		// Load supporting CSS.
		$("head").append("<link rel='stylesheet' href='//static.ponyvillelive.com/header/header.css' type='text/css' media='screen'>");

        // Move existing link to top of body if detected, or recreate.
        if ($('#pvl_header_link').length > 0)
            $(document.body).prepend($('#pvl_header_link').detach());
        else
            $(document.body).prepend('<a id="pvl_header_link" href="http://www.ponyvillelive.com/" target="_blank">Ponyville Live!</a>');

        var header_link = $('#pvl_header_link');
        header_link.wrap('<div class="pvl-navbar-inner" />').wrap('<div class="pvl-navbar" />');
        header_link.html('<img src="//ponyvillelive.com/static/header/header_dark.png" alt="This station is a proud partner of Ponyville Live! - Ponyville Live, Bringing Pony People Together.">');

	})(jQuery);

}