/**
 * Ponyville Live! Header Banner Include
 * Requires jQuery 1.8+
 */

if (jQuery) {

	(function($) { 

		// Load supporting CSS.
		$("head").append("<link rel='stylesheet' href='//www.ponyvillelive.com/static/header/header.css' type='text/css' media='screen'>");
		$("head").append("<link rel='stylesheet' href='//fonts.googleapis.com/css?family=Source+Sans+Pro:400,700'  type='text/css' media='screen'>");
		$("head").append("<link rel='stylesheet' href='//netdna.bootstrapcdn.com/font-awesome/3.1.1/css/font-awesome.css'  type='text/css' media='screen'>");	

		/*
		var imported = document.createElement('script');
		imported.src = '/path/to/imported/script';
		document.head.appendChild(imported);
		*/

		$("body").prepend('<div class="pvl-navbar" style="height: 42px; overflow: hidden;"><div class="pvl-navbar-inner"><div class="pvl-container"><a class="brand" href="http://www.ponyvillelive.com/"><img src="http://www.ponyvillelive.com/static/pvl_dark.png" alt="Ponyville Live!"></a><ul class="pvl-nav"><li class="active"><div><b>The Pony Community\'s Newest Media Network</b><br>24/7 Pony Music & Video, Podcasts and More</div></li><li><a href="http://www.ponyvillelive.com/" target="_blank"><i class="icon-home"></i> PVL Home</a></li><li><a href="http://www.ponyvillelive.com/about" target="_blank"><i class="icon-info-sign"></i> About PVL</a></li><li><a href="#" id="pvl-launch-player"><i class="icon-volume-up"></i> Launch Web Player</a></li></ul></div></div></div>');

		$('#pvl-launch-player').click(function(e) {
	    	e.preventDefault();

			orig_url = "http://www.ponyvillelive.com/index/tunein";
			window.open(orig_url, 'pvl_player', 'height=600,width=400,status=yes,scrollbars=yes', true);

	    	return false;
	    });

	})(jQuery)
}