/**
 * Ponyville Live! Header Banner Include
 * Requires jQuery 1.8+
 */

if (jQuery) {

	(function($) { 

		// Load supporting CSS.
		$("head").append("<link rel='stylesheet' href='//static.ponyvillelive.com/header/header.css' type='text/css' media='screen'>");

		$("body").prepend('<div class="pvl-navbar" style="height: 42px; overflow: hidden;"><div class="pvl-navbar-inner"><div class="pvl-container"><a href="http://www.ponyvillelive.com/" target="_blank"><img src="//static.ponyvillelive.com/header/header_dark.png" alt="This station is a proud partner of Ponyville Live! - Ponyville Live, Bringing Pony People Together."></div></div></div>');

	})(jQuery)
}