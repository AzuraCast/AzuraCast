$(function() {

	handle_side_menu();
	add_browser_detection(jQuery);
	general_things();
	widget_boxes();
	
	//bootstrap v 2.3.1 prevents this event which firefox's middle mouse button "new tab link" action, so we off it!
	$(document).off('click.dropdown-menu');

	$('#jquery_jplayer').jPlayer({
		swfPath: '/static/jplayer/jplayer.swf',
		solution: ($.browser.webkit) ? 'flash, html' : 'flash',
		supplied: 'mp3',
		preload: 'metadata',
		volume: 0.8,
		muted: false,
		backgroundColor: '#000000',
		cssSelectorAncestor: '#jp_container',
		cssSelector: {
			videoPlay: '.jp-video-play',
			play: '.jp-play',
			pause: '.jp-pause',
			stop: '.jp-stop',
			seekBar: '.jp-seek-bar',
			playBar: '.jp-play-bar',
			mute: '.jp-mute',
			unmute: '.jp-unmute',
			volumeBar: '.jp-volume-bar',
			volumeBarValue: '.jp-volume-bar-value',
			volumeMax: '.jp-volume-max',
			currentTime: '.jp-current-time',
			duration: '.jp-duration',
			fullScreen: '.jp-full-screen',
			restoreScreen: '.jp-restore-screen',
			repeat: '.jp-repeat',
			repeatOff: '.jp-repeat-off',
			gui: '.jp-gui',
			noSolution: '.jp-no-solution'
		},
		errorAlerts: false,
		warningAlerts: false
	});

	$('body').initPage();

	$('form.form-search').submit(function(e) {
		e.preventDefault();

		var query = $(this).find('#nav-search-input').val();
		var url = $(this).attr('action')+'/search/'+query;

		History.pushState(null,'Search Results',url);

		return false;
	});

});

$.fn.initPage = function(){
	
	var $this = $(this);

	$this.find('.btn-play-song').on('click', function(e) {

		e.preventDefault();

		$('#jquery_jplayer').jPlayer("setMedia", {
			mp3: $(this).attr('href')
		}).jPlayer("play");

		$('#jp_container').slideDown();

		$('#nowplaying-title').text($(this).data('title'));
		$('#nowplaying-artist').text($(this).data('artist'));

	});
	
	// Chain
	return $this;
};

function handle_side_menu() {
	$('#menu-toggler').on('click', function() {
		$('#sidebar').toggleClass('display');
		$(this).toggleClass('display');
		return false;
	});

	//mini
	var $minimized = false;
	$('#sidebar-collapse').on('click', function(){
		$('#sidebar').toggleClass('menu-min');
		$(this.firstChild).toggleClass('icon-double-angle-right');
		
		$minimized = $('#sidebar').hasClass('menu-min');
		if($minimized) {
			$('.open > .submenu').removeClass('open');
		}
	});
	
	//opening submenu
	$('.nav-list .dropdown-toggle').each(function(){
		var sub = $(this).next().get(0);
		
		$(this).on('click', function(){
			if($minimized) {
				return false;
			}
			$('.open > .submenu').each(function(){
				if(this != sub && !$(this.parentNode).hasClass('active')) {
					$(this).slideUp(200).parent().removeClass('open');//.find('.arrow').removeClass('icon-chevron-down');
				}
			});
			
			$(sub).slideToggle(200).parent().toggleClass('open');//.find('.arrow').toggleClass('icon-chevron-down');
			return false;
		});
	})
}


function enable_search_ahead() {
	var inp = $('#nav-search-input');
	inp.typeahead({
		source: ["Alabama","Alaska","Arizona","Arkansas","California","Colorado","Connecticut","Delaware","Florida","Georgia","Hawaii","Idaho","Illinois","Indiana","Iowa","Kansas","Kentucky","Louisiana","Maine","Maryland","Massachusetts","Michigan","Minnesota","Mississippi","Missouri","Montana","Nebraska","Nevada","New Hampshire","New Jersey","New Mexico","New York","North Dakota","North Carolina","Ohio","Oklahoma","Oregon","Pennsylvania","Rhode Island","South Carolina","South Dakota","Tennessee","Texas","Utah","Vermont","Virginia","Washington","West Virginia","Wisconsin","Wyoming"],
		updater:function (item) {
			inp.focus();
			return item;
		}
	});
}


function general_things() {
 $('.ace-nav [class*="icon-animated-"]').closest('a').on('click', function(){
	var icon = $(this).find('[class*="icon-animated-"]').eq(0);
	var $match = icon.attr('class').match(/icon\-animated\-([\d\w]+)/);
	icon.removeClass($match[0]);
	$(this).off('click');
 });
 
 
 //simple settings

 $('#ace-settings-btn').on('click', function(){
	$(this).toggleClass('open');
	$('#ace-settings-box').toggleClass('open');
 });
 
 $('#ace-settings-header').removeAttr('checked').on('click', function(){
	if(this.checked) {
		$('.navbar.navbar-inverse').addClass('navbar-fixed-top');
		$(document.body).addClass('navbar-fixed');
	}
	else {
		$('.navbar.navbar-inverse').removeClass('navbar-fixed-top');
		$(document.body).removeClass('navbar-fixed');
		
		if($('#ace-settings-sidebar').get(0).checked) $('#ace-settings-sidebar').click();
	}
 });
 
 $('#ace-settings-sidebar').removeAttr('checked').on('click', function(){
	if(this.checked) {
		$('#sidebar').addClass('fixed');
		if(! $('#ace-settings-header').get(0).checked) $('#ace-settings-header').click();
	}
	else {
		$('#sidebar').removeClass('fixed');
	}
 });


 $('#btn-scroll-up').on('click', function(){
	var duration = Math.max(100, parseInt($('html').scrollTop() / 3));
	$('html,body').animate({scrollTop: 0}, duration);
	return false;
 });
}

function widget_boxes() {
	$('.widget-toolbar > a[data-action]').each(function() {
		var $this = $(this);
		var $action = $this.data('action');
		var $box = $this.closest('.widget-box');
		
		if($action == 'collapse') {
			var $body = $box.find('.widget-body');
			var $icon = $this.find('[class*=icon-]').eq(0);
			var $match = $icon.attr('class').match(/icon\-(.*)\-(up|down)/);
			var $icon_down = 'icon-'+$match[1]+'-down';
			var $icon_up = 'icon-'+$match[1]+'-up';
			
			
			$body = $body.wrapInner('<div class="widget-body-inner"></div>').find(':first-child').eq(0);
			$this.on('click', function(ev){
				if($box.hasClass('collapsed')) {
					if($icon) $icon.addClass($icon_up).removeClass($icon_down);
					$box.removeClass('collapsed');
					$body.slideDown(200);
				}
				else {
					if($icon) $icon.addClass($icon_down).removeClass($icon_up);
					$body.slideUp(300, function(){$box.addClass('collapsed')});
				}
				ev.preventDefault();
			});
			if($box.hasClass('collapsed') && $icon) $icon.addClass($icon_down).removeClass($icon_up);

		}
		else if($action == 'close') {
			$this.on('click', function(ev){
				$box.hide(300 , function(){$box.remove();});
				ev.preventDefault();
			});
		}
		else if($action == 'reload') {
			$this.on('click', function(ev){
				$this.blur();
				//var $body = $box.find('.widget-body');
				var $remove = false;
				if(!$box.hasClass('position-relative')) {$remove = true; $box.addClass('position-relative');}
				$box.append('<div class="widget-box-layer"><i class="icon-spinner icon-spin icon-2x white"></i></div>');
				setTimeout(function(){
					$box.find('> div:last-child').remove();
					if($remove) $box.removeClass('position-relative');
				}, parseInt(Math.random() * 1000 + 1000));
				ev.preventDefault();
			});
		}
		else if($action == 'settings') {
			$this.on('click', function(ev){
				ev.preventDefault();
			});
		}
		
	});
}




//code taken from http://code.jquery.com/jquery-1.8.3.js to provide simple browser detection for 1.9+ versions
function add_browser_detection($) {
	if(!$.browser) {
		var matched, browser;

		// Use of jQuery.browser is frowned upon.
		// More details: http://api.jquery.com/jQuery.browser
		// jQuery.uaMatch maintained for back-compat
		$.uaMatch = function( ua ) {
			ua = ua.toLowerCase();

			var match = /(chrome)[ \/]([\w.]+)/.exec( ua ) ||
				/(webkit)[ \/]([\w.]+)/.exec( ua ) ||
				/(opera)(?:.*version|)[ \/]([\w.]+)/.exec( ua ) ||
				/(msie) ([\w.]+)/.exec( ua ) ||
				ua.indexOf("compatible") < 0 && /(mozilla)(?:.*? rv:([\w.]+)|)/.exec( ua ) ||
				[];

			return {
				browser: match[ 1 ] || "",
				version: match[ 2 ] || "0"
			};
		};

		matched = $.uaMatch( navigator.userAgent );
		browser = {};

		if ( matched.browser ) {
			browser[ matched.browser ] = true;
			browser.version = matched.version;
		}

		// Chrome is Webkit, but Webkit is also Safari.
		if ( browser.chrome ) {
			browser.webkit = true;
		} else if ( browser.webkit ) {
			browser.safari = true;
		}

		$.browser = browser;

	}
}
