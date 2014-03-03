/*
* Fullwidth Audio Player V1.5.21
* Author: Rafael Dery
* Copyright 2011
*
* Only for the sale at the envato marketplaces
*
*/

;(function($) {
	$.fullwidthAudioPlayer = {version: '1.5.21', author: 'Rafael Dery'};

	jQuery.fn.fullwidthAudioPlayer = function(arg) {
		var options = $.extend({},$.fn.fullwidthAudioPlayer.defaults,arg);
		var $elem, $wrapper, $main, $uiWrapper, $metaWrapper, $playlistWrapper = null,
		    player, currentTime, totalHeight = 0,
			loadingIndex = -1, timeBarWidth = 180, volumeBarWidth = 50, currentIndex = -1, currentVolume = 100,
			paused, playlistCreated, playlistIsOpened = false, playAddedTrack = false, popupMode = false, isPopupWin = false,
			soundcloudKey = 'd2be7a47322c293cdaffc039a26e05d1', tracks = [];

		//*********************************************
		//************** LOADING CORE *****************
		//*********************************************

		function _init(elem) {

			if(options.storePlaylist && typeof amplify == 'undefined') {
				alert('Please include the amplify.min.js file in your document!');
				return false;
			}

			$elem = $(elem);
			$elem.hide();

			//check if script is executed in the popup window
			isPopupWin = elem.id == 'fap-popup';

			if(_detectMobileBrowsers()) {
				if(options.hideOnMobile) { return false; }
				//volume and playlist will be also disabled on mobile devices
				options.autoPlay = options.volume = options.playlist = false;
				options.wrapperPosition = 'top';
			}

			//check if a popup window exists
			playlistCreated = Boolean(window.fapPopupWin);
			if(!options.autoPopup) { playlistCreated = true; }
			paused = !options.autoPlay;

			_documentTrackHandler();

			totalHeight = options.playlist ? options.height+options.playlistHeight+options.offset : options.height;

			if(options.wrapperPosition == "popup" && !isPopupWin) {

				options.layout = 'fullwidth';
				popupMode = true;
				if(!options.playlist) { totalHeight = options.height; }
				if(options.autoPopup && !window.fapPopupWin) {
					_addTrackToPopup($elem.html(), options.autoPlay);
				}

				return false;
			}

			//init soundcloud
			SC.initialize({
				client_id: "d2be7a47322c293cdaffc039a26e05d1"
			});

			var fapDom = '<div id="fap-wrapper" class="'+(options.wrapperPosition == 'top' ? 'fap-wrapper-top' : 'fap-wrapper-bottom')+'" style="'+options.wrapperPosition+': 0; height: '+totalHeight+'px;"><div id="fap-main" style="color:'+options.mainColor+';"><div id="fap-wrapper-switcher" style="background: '+options.wrapperColor+'; border-color: '+options.strokeColor+'"></div><p id="fap-init-text">Creating Playlist...</p></div></div>';

			$('body').append(fapDom);

			$wrapper = $('body').children('#fap-wrapper');
			$main = $wrapper.children('#fap-main');

			if(options.layout == "fullwidth") {
				$wrapper.addClass('fap-fullwidth').css({background: options.wrapperColor, 'borderColor': options.strokeColor});
			}
			else if(options.layout == "boxed") {
				$wrapper.addClass('fap-boxed');
				$main.css({background: options.wrapperColor, 'borderColor': options.strokeColor});
			}

			if(isPopupWin) {
				$wrapper.addClass('fap-popup-skin');
			}

			//change wrapper css for mobile
			if(_detectMobileBrowsers()) {
				$wrapper.css({position: 'absolute'})
			}

			//position main wrapper
			if(isPopupWin) {
				$main.css({'marginLeft': 10, 'marginRight': 10});
			}
			else if(options.mainPosition == 'center') {
				$main.css({'marginLeft': 'auto', 'marginRight': 'auto'});
			}
			else if(options.mainPosition == 'right') {
				$main.css({'float': 'right', 'marginRight': 10});
			}
			else {
				$main.css({'marginLeft': 10});
			}

			options.wrapperPosition == 'top' ? $main.children('#fap-wrapper-switcher').addClass('fap-bordered-bottom').css({'bottom': -15, 'borderTop': 'none'}) : $main.children('#fap-wrapper-switcher').addClass('fap-bordered-top').css({'top': -15, 'borderBottom': 'none'});

			//set default wrapper position
			options.opened ? $.fullwidthAudioPlayer.setPlayerPosition('open', false) : $.fullwidthAudioPlayer.setPlayerPosition('close', false);

			//switcher handler
			$main.children('#fap-wrapper-switcher').click(function() {
				options.opened ? $.fullwidthAudioPlayer.setPlayerPosition('close', true) : $.fullwidthAudioPlayer.setPlayerPosition('open', true);
			});

			soundManager.onready(_onSoundManagerReady);
		};

		function _addTrackToPopup(html, playIt) {

			if( !window.fapPopupWin || window.fapPopupWin.closed ) {

				var windowWidth = 980;
				var centerWidth = (window.screen.width - windowWidth) / 2;
    			var centerHeight = (window.screen.height - totalHeight) / 2;
    			var isChrome = /Chrome/i.test(navigator.userAgent);

				window.fapPopupWin = window.open(options.popupUrl, '', 'menubar=no,toolbar=no,location=yes,status=no,width='+windowWidth+',height='+(isChrome ? totalHeight+30 : totalHeight)+',left='+centerWidth+',top='+centerHeight+'');

				if(window.fapPopupWin == null) {
					alert("Pop-Up Music Player can not be opened. Your browser is blocking Pop-Ups. Please allow Pop-Ups for this site to use the Music Player.");
				}
				$(window.fapPopupWin).load(function() {
					$(window.fapPopupWin).animate({innerHeight: totalHeight}, 500);
					$('.fap-single-track[data-autoenqueue="yes"]').each(function(i, item) {
						var node = $(item);
						html += _createHtmlFromNode(node);
				    });
					options.autoPlay = playIt;
					window.fapPopupWin.initPlayer(options, html);
					playlistCreated = true;
				});

			}
			else {
				var $node = $(html);
				$.fullwidthAudioPlayer.addTrack($node.attr('href'), $node.attr('title'), ($node.data('meta') ? $('body').find($node.data('meta')).html() : ''), $node.attr('rel'), $node.attr('target'), playIt);

			}

		}

		function _onSoundManagerReady() {

			if(options.playlist) {
				var playlistDom = '<ul id="fap-playlist"></ul>';
				$playlistWrapper = $(playlistDom).css('height', options.playlistHeight);
			}

			if(options.xmlPath) {
				//get playlists from xml file
				$.ajax({ type: "GET", url: options.xmlPath, dataType: "xml", cache: false, success: function(xml) {

					var playlists = $(xml).find('playlists'),
					    playlistId = options.xmlPlaylist ? playlistId = options.xmlPlaylist : playlistId = playlists.children('playlist:first').attr('id');

					_createInitPlaylist(playlists.children('playlist[id="'+playlistId+'"]').children('track'));

					//check if custom xml playlists are set in the HTML document
					$('.fap-xml-playlist').each(function(i, playlist) {
						var $playlist = $(playlist);
						$playlist.append('<h3>'+playlist.title+'</h3><ul class="fap-my-playlist"></ul>');
						//get the start playlist
						playlists.children('playlist[id="'+playlist.id+'"]').children('track').each(function(j, track) {
							var $track = $(track);
							var targetString = $track.attr('target') ? 'target="'+$track.attr('target')+'"' : '';
							var relString = $track.attr('rel') ? 'rel="'+$track.attr('rel')+'"' : '';
							var metaString = $track.find('meta') ? 'data-meta="#'+playlist.id+'-'+j+'"' : '';
							$playlist.children('ul').append('<li><a href="'+$track.attr('href')+'" title="'+$track.attr('title')+'" '+targetString+' '+relString+' '+metaString+'>'+$track.attr('title')+'</a></li>');
							$playlist.append('<span id="'+playlist.id+'-'+j+'">'+$track.find('meta').text()+'</span>');
						});
					});

				},
				error: function() {
					alert("XML file could not be loaded. Please check the XML path!");
				}
			  });
			}
			else {
				_createInitPlaylist($elem.children('a'));
			}
		};

		function _createInitPlaylist(initTracks) {

			if(options.storePlaylist) {
				var initFromBrowser = Boolean(amplify.store('fap-playlist'));
			}

			initTracks = initFromBrowser ? JSON.parse(amplify.store('fap-playlist')) : initTracks;


			$elem.bind('fap-tracks-stored', function() {

				++loadingIndex;
				if(loadingIndex < initTracks.length) {
					//get stored playlist from browser when available
					if(options.storePlaylist && initFromBrowser) {
						var initTrack = initTracks[loadingIndex];

						$.fullwidthAudioPlayer.addTrack(initTrack.stream_url, initTrack.title, initTrack.meta, initTrack.artwork_url, initTrack.permalink_url, options.autoPlay);
					}
					else {
						var initTrack = initTracks.eq(loadingIndex);

						$.fullwidthAudioPlayer.addTrack(initTrack.attr('href'), initTrack.attr('title'), options.xmlPath ? initTrack.children('meta').text() : $elem.find(initTrack.data('meta')).html(), initTrack.attr('rel'), initTrack.attr('target'), options.autoPlay);
					}
				}
				else {
					$elem.unbind('fap-tracks-stored');
					if(options.randomize) { _shufflePlaylist(); }

					_buildPlayer();
				}
			}).trigger('fap-tracks-stored');

		};



		//*********************************************
		//************** DOM INTERFACE ****************
		//*********************************************

		function _buildPlayer() {

			//remove init text
			$main.children('p').remove();

			//create meta wrapper
			$main.append('<div id="fap-meta-wrapper" class="clearfix"><img src="" id="fap-current-cover" style="width: '+options.coverSize[0]+'px; height:'+options.coverSize[1]+'px; border: 1px solid '+options.strokeColor+';" /><div id="fap-cover-replacement" style="width: '+options.coverSize[0]+'px; height:'+options.coverSize[1]+'px; border: 1px solid '+options.strokeColor+';"></div><p id="fap-current-title" style="color: '+options.mainColor+';"></p><p id="fap-current-meta" style="color: '+options.metaColor+';"></p></div>');

			$metaWrapper = $main.children('#fap-meta-wrapper').css('height', options.height-10);

			//add a cover replacement
			_createCoverReplacement(document.getElementById('fap-cover-replacement'), options.coverSize[0], options.coverSize[1]);

			//append social links if requested
			if(options.socials) {
				$metaWrapper.append('<p id="fap-social-links"><a href="" target="_blank" style="color: '+options.metaColor+';">'+options.facebookText+'</a><a href="" target="_blank" style="color: '+options.metaColor+';">'+options.twitterText+'</a><a href="" target="_blank" style="color: '+options.metaColor+';">'+options.downloadText+'</a><a href="" target="_blank" class="fap-soundcloud-link"></a></p>');
			}

			//create ui wrapper
			$uiWrapper = $main.append('<div id="fap-ui-wrapper"></div>').children('#fap-ui-wrapper').css('height', options.height);

			//append UI Wrapper
			var $uiNav = $uiWrapper.append('<div id="fap-ui-nav"></div>').children('#fap-ui-nav');

			$uiNav.css('margin-top', options.height * 0.5 - $uiNav.height() * 0.5);

			//append previous button
			$uiNav.append('<a href="#" id="fap-previous" style="background-color: '+options.fillColor+';"></a>').children('#fap-previous').click(function() {
				$.fullwidthAudioPlayer.previous();
				return false;
			});

			//append play/pause button
			$uiNav.append('<a href="#" id="fap-play-pause" style="background-color: '+options.fillColor+';"></a>').children('#fap-play-pause').click(function() {
				$.fullwidthAudioPlayer.toggle();
				return false;
			});

			//append next button
			$uiNav.append('<a href="#" id="fap-next" style="background-color: '+options.fillColor+';"></a>').children('#fap-next').click(function() {
				$.fullwidthAudioPlayer.next();
				return false;
			});

			//append time bar
			var rightPositionTimeBar = $uiWrapper.children('div:first').length ? $uiWrapper.width()-$uiWrapper.children('div:first').position().left+20 : 0;
			$uiWrapper.append('<div id="fap-time-bar" class="clearfix" style="width: '+timeBarWidth+'px; border: 1px solid '+options.fillColor+'; margin-top: '+(options.height*0.5-3)+'px; color: '+options.metaColor+';"><div id="fap-loading-bar" style="background: '+options.fillColor+';"></div><div id="fap-progress-bar" style="background: '+options.mainColor+';"></div><span id="fap-current-time">00:00:00</span><span id="fap-total-time">00:00:00</span></div>');

			$uiWrapper.find('#fap-loading-bar, #fap-progress-bar').click(function(evt) {
				var progress = (evt.pageX - $(this).parent().offset().left) / timeBarWidth;
				player.setPosition(progress * player.duration);
				_setSliderPosition(progress);
			});

			//append volume bar if requested - hidden for mobile browsers
			if(options.volume) {
				var rightPositionVolume = options.playlist || options.shuffle  ? 60 : 20;
				if(isPopupWin) { rightPositionVolume = options.shuffle ? 60 : 20; }
				$uiWrapper.append('<div id="fap-volume-bar" style="width: '+volumeBarWidth+'px; background: '+options.fillColor+'; border: 1px solid '+options.fillColor+'; margin-top: '+(options.height*0.5-3)+'px;"><div id="fap-volume-progress" style="background: '+options.mainColor+';"></div></div><div id="fap-volume-sign"></div>');

				$uiWrapper.children('#fap-volume-sign').css('margin-top', options.height * 0.5 - $uiWrapper.children('#fap-volume-sign').height() * 0.5);

				$uiWrapper.find('#fap-volume-bar').click(function(evt) {
					var value = (evt.pageX - $(this).offset().left) / volumeBarWidth;
					$.fullwidthAudioPlayer.volume(value);
				});
			}

			//create visual playlist if requested - hidden for mobile browsers
			if(options.playlist) {

				if(options.wrapperPosition == 'bottom') {
					$main.append('<div class="clear"></div>').append($playlistWrapper);
					$playlistWrapper.css({'marginTop': options.offset});
				}
				else {
					$main.prepend('<div class="clear"></div>').prepend($playlistWrapper);
					$playlistWrapper.css({'marginBottom': options.offset})
				}

				//init scroll bar
				$playlistWrapper.niceScroll({cursorcolor: options.mainColor, cursorborder: 'none', autohidemode: false});
				$playlistWrapper.getNiceScroll().hide();
				$('.nicescroll-rails').hover(
					function() {
						$playlistWrapper.getNiceScroll().resize().show();
					},
					function() {
						$playlistWrapper.getNiceScroll().resize().hide();
					}
				);
				$playlistWrapper.hover(
					function() {
						$playlistWrapper.getNiceScroll().resize().show();
					},
					function() {
						$playlistWrapper.getNiceScroll().resize().hide();
					}
				);

				//make playlist sortable
				if(options.sortable) {
					var oldIndex;
					$playlistWrapper.sortable().bind('sortstart', function(evt, ui) {
						ui.item.addClass('fap-prevent-click');
						oldIndex = $playlistWrapper.children('li').index(ui.item);
					});

					$playlistWrapper.sortable().bind('sortupdate', function(evt, ui) {
						var targetIndex = $playlistWrapper.children('li').index(ui.item);
						var item = tracks[oldIndex];
						var currentTitle = tracks[currentIndex].title;
						tracks.splice(oldIndex, 1);
						tracks.splice(targetIndex, 0, item);
						_updateTrackIndex(currentTitle);
						if(options.storePlaylist) { amplify.store('fap-playlist', JSON.stringify(tracks)); }
					});
				}

				if(!isPopupWin) {
					//playlist switcher
					$uiWrapper.append('<a href="#" id="fap-playlist-toggle" style="background-color:'+options.fillColor+'; margin-top: '+(options.height * 0.5 - 12)+'px;"></a>');
					$uiWrapper.children('#fap-playlist-toggle').click(function() {
						playlistIsOpened ? $.fullwidthAudioPlayer.setPlayerPosition('closePlaylist', true) : $.fullwidthAudioPlayer.setPlayerPosition('openPlaylist', true);
						return false;
					});

				}
				else {
					//open playlist when player is in the pop-up window
					$.fullwidthAudioPlayer.setPlayerPosition('openPlaylist', false);
				}

			}

			//append shuffle buttin if requested
			if(options.shuffle) {

				$uiWrapper.append('<a href="#" id="fap-playlist-shuffle" style="background-color:'+options.fillColor+'; margin-top: '+(options.height * 0.5 - 12)+'px;"></a>');

				$uiWrapper.children('#fap-playlist-shuffle').click(function() {
					_shufflePlaylist();
					return false;
				});

			}

			//hover for rounded buttons in the ui wrapper
			$uiWrapper.find('a').hover(
				function() {
					$(this).css('backgroundColor', options.fillColorHover);
				},
				function() {
					$(this).css('backgroundColor', options.fillColor);
				}
			);

			//register keyboard events
			if(options.keyboard) {
				$(document).keyup(function(evt) {
					switch (evt.which) {
						case 32:
						$.fullwidthAudioPlayer.toggle();
						break;
						case 39:
						$.fullwidthAudioPlayer.next();
						break;
						case 37:
						$.fullwidthAudioPlayer.previous();
						break;
						case 38:
						$.fullwidthAudioPlayer.volume((currentVolume / 100)+.05);
						break;
						case 40:
						$.fullwidthAudioPlayer.volume((currentVolume / 100)-.05);
						break;
					}
				});
			}

			//add margin for p elements in meta wrapper
			$metaWrapper.children('p').css('marginLeft', options.coverSize[0] + 10);

			//fire on ready handler
			$elem.trigger('onFapReady');
			playlistCreated = true;

			$('.fap-single-track[data-autoenqueue="yes"]').each(function(i, item) {
				var node = $(item);
		      	jQuery.fullwidthAudioPlayer.addTrack(node.attr('href'), node.attr('title'), $('body').find(node.data('meta')).html(), node.attr('rel'), node.attr('target'), false);

		    });

			//start playing track when addTrack method is called
			$elem.bind('fap-tracks-stored', function(evt, trackIndex) {
				if(playAddedTrack) { $.fullwidthAudioPlayer.selectTrack(trackIndex, playAddedTrack); }
			});

			//select first track when playlist has tracks
		    $.fullwidthAudioPlayer.selectTrack(0, /Android|webOS|iPhone|iPod|iPad|BlackBerry/i.test(navigator.userAgent) ? false : options.autoPlay);
			options.autoPlay ? $elem.trigger('onFapPlay') : $elem.trigger('onFapPause');

		};

		function _documentTrackHandler() {

			if($elem.jquery >= "1.7"){
				$('body').on('click', '.fap-my-playlist li a, .fap-single-track', _addTrackFromDocument);
				$('body').on('click', '.fap-add-playlist', _addTrackFromDocument);
			}
			else {
				$('body').delegate('.fap-my-playlist li a, .fap-single-track', 'click', _addTrackFromDocument);
				$('body').delegate('.fap-add-playlist', 'click', _addTrackFromDocument);
			}

			function _addTrackFromDocument() {
				if(!playlistCreated) { return false; }
				var node = $(this),
					playIt = true;

				if(node.data('enqueue')) {
					playIt = node.data('enqueue') == 'yes' ? false : true;
				}

				if(popupMode) {
					//adding whole plalist to the player
					if(node.hasClass('fap-add-playlist')) {
						var playlistId = node.data('playlist'),
							tracks = jQuery('ul[data-playlist="'+playlistId+'"]').first().children('li').find('.fap-single-track'),
							html = _createHtmlFromNode($(tracks.get(0)));

						if(tracks.size() == 0) { return false; }

						//add first track to pop-up to open it
						_addTrackToPopup(html, playIt);
						tracks.splice(0, 1);

						window.fapReady = window.fapPopupWin.addTrack != undefined;
						//start interval for adding the playlist into the pop-up player
						var interval = setInterval(function() {
							if(window.fapReady) {
								clearInterval(interval);
								tracks.each(function(i, item) {
									_addTrackToPopup(item, playIt);
							    });
							}
						}, 50);
					}
					//adding a single track to the player
					else {
						var html = _createHtmlFromNode(node);
						_addTrackToPopup(html, playIt);
					}

				}
				else {
					//adding whole plalist to the player
					if(node.hasClass('fap-add-playlist')) {
						var playlistId = node.data('playlist'),
							tracks = jQuery('ul[data-playlist="'+playlistId+'"]').first().children('li').find('.fap-single-track');

						if(tracks.size() == 0) { return false; }

						loadingIndex = -1;
						$elem.bind('fap-tracks-stored', function() {
							++loadingIndex;
							if(loadingIndex < tracks.size()) {
								var $track = tracks.eq(loadingIndex);
								$.fullwidthAudioPlayer.addTrack($track.attr('href'), $track.attr('title'), $('body').find($track.data('meta')).html(), $track.attr('rel'), $track.attr('target'), (loadingIndex == 0 && playIt));
							}
							else {
								$elem.unbind('fap-tracks-stored');
							}

						}).trigger('fap-tracks-stored');
					}
					//adding a single track to the player
					else {
						$.fullwidthAudioPlayer.addTrack(node.attr('href'), node.attr('title'), $('body').find(node.data('meta')).html(), node.attr('rel'), node.attr('target'), playIt);
					}

				}

				return false;
			};

		};


		//*********************************************
		//************** API METHODS ******************
		//*********************************************

		//global method for playing the current track
		$.fullwidthAudioPlayer.play = function() {
			if(currentIndex == -1) {
				$.fullwidthAudioPlayer.next();
			}
			if(tracks.length > 0) {
				if(player.playState) {
					player.resume();
				}
				else {
				    player.play();
				}

				$uiWrapper.find('#fap-play-pause').removeClass('fap-play').addClass('fap-pause');
				paused = false;
				$elem.trigger('onFapPlay');
			}
		};

		//global method for pausing the current track
		$.fullwidthAudioPlayer.pause = function() {
			if(tracks.length > 0) {
				player.pause();
				$uiWrapper.find('#fap-play-pause').removeClass('fap-pause').addClass('fap-play');
				paused = true;
				$elem.trigger('onFapPause');
			}
		};

		//global method for pausing/playing the current track
		$.fullwidthAudioPlayer.toggle = function() {
			if(paused) {
				$.fullwidthAudioPlayer.play();
			}
			else {
				$.fullwidthAudioPlayer.pause();
			}
		};

		//global method for playing the previous track
		$.fullwidthAudioPlayer.previous = function() {
			if(tracks.length > 0) {
				$.fullwidthAudioPlayer.selectTrack(currentIndex-1, true);
			}
		};

		//global method for playing the next track
		$.fullwidthAudioPlayer.next = function() {
			if(tracks.length > 0) {
				$.fullwidthAudioPlayer.selectTrack(currentIndex+1, true);
			}
		};

		$.fullwidthAudioPlayer.volume = function(value) {
			if(tracks.length > 0) {
				if(value < 0 ) value = 0;
				if(value > 1 ) value = 1;
				currentVolume = value * 100;
				if(player) { player.setVolume(currentVolume); }
				$uiWrapper.find('#fap-volume-progress').width(value * volumeBarWidth);
			}
		};

		//global method for adding a track to the playlist
		$.fullwidthAudioPlayer.addTrack = function(trackUrl, title, meta, cover, linkUrl, playIt) {
			if(trackUrl == null || trackUrl == '') {
				alert('The track with the title "'+title+'" does not contain a track URI to a MP3 file or to a soundcloud resource!');
				return false;
			}

			if ( title === undefined ) {
			   title = '';
			}
			if ( meta === undefined ) {
			   meta = '';
			}
			if ( cover === undefined ) {
			   cover = '';
			}
			if ( linkUrl === undefined ) {
			   linkUrl = '';
			}
			if ( playIt === undefined ) {
			   playIt = false;
			}

			if(popupMode && window.fapPopupWin && !window.fapPopupWin.closed) {
				window.fapPopupWin.addTrack(trackUrl,title,meta,cover,linkUrl, playIt);
				window.fapPopupWin.focus();
				return false;
			}

			if(options.base64) {
				trackUrl = _decodebase64(trackUrl);
			}

			playAddedTrack = playIt;
			if(RegExp('http(s?)://soundcloud').test(trackUrl) || RegExp('http(s?)://official.fm').test(trackUrl)) {
				_getTracksFromExternalSource(trackUrl);
			}
			else {
				var li = _storeTrackDatas({stream_url: trackUrl, title: title, meta: meta, artwork_url: cover, permalink_url:linkUrl});
				$elem.trigger('onFapTracksAdded', [tracks]);
				$elem.trigger('fap-tracks-stored', [li]);
			}

			if(!options.opened && playIt && !isPopupWin) {
				$.fullwidthAudioPlayer.setPlayerPosition('open', true);
			}
		};

		//select a track by index
		$.fullwidthAudioPlayer.selectTrack = function(index, playIt) {

			if(tracks.length <= 0) {
				$.fullwidthAudioPlayer.clear();
				return false;
			}

			if(index == currentIndex) {
				$.fullwidthAudioPlayer.toggle();
				return false;
			}
			else if(index < 0) { currentIndex = tracks.length - 1; }
			else if(index >= tracks.length) {
				currentIndex = 0;
				playIt = options.loopPlaylist;
			}
			else { currentIndex = index; }

			paused = !playIt;

			var isSoundcloud = RegExp('http(s?)://soundcloud').test(tracks[currentIndex].permalink_url);

			//reset
			$uiWrapper.find('#fap-progress-bar').width(0);
			$uiWrapper.find('#fap-total-time, #fap-current-time').text('00:00:00');

			$metaWrapper.children('#fap-current-cover').attr('src', tracks[currentIndex].artwork_url);
			$metaWrapper.children('#fap-current-title').html(tracks[currentIndex].title);
			$metaWrapper.children('#fap-current-meta').html(isSoundcloud ? tracks[currentIndex].genre : tracks[currentIndex].meta);

			if(!tracks[currentIndex].artwork_url) {
				$metaWrapper.children('#fap-current-cover').hide();
				$metaWrapper.children('#fap-cover-replacement').show();
			}
			else {
				$metaWrapper.children('#fap-current-cover').show();
				$metaWrapper.children('#fap-cover-replacement').hide();
			}

			if(tracks[currentIndex].permalink_url) {
				$metaWrapper.children('#fap-social-links').children('a').show();
				var facebookLink = 'http://www.facebook.com/sharer.php?u='+encodeURIComponent(tracks[currentIndex].permalink_url);
				var twitterLink = 'http://twitter.com/share?url='+encodeURIComponent(tracks[currentIndex].permalink_url)+'&text='+encodeURIComponent(tracks[currentIndex].title)+'';

				$metaWrapper.find('#fap-social-links a:eq(0)').attr('href', facebookLink);
				$metaWrapper.find('#fap-social-links a:eq(1)').attr('href', twitterLink);
				$metaWrapper.find('#fap-social-links a:eq(2)').attr('href', tracks[currentIndex].permalink_url+'/download');
				$metaWrapper.find('#fap-social-links a:eq(3)').attr('href', tracks[currentIndex].permalink_url);
			}
			else {
				$metaWrapper.children('#fap-social-links').children('a').hide();
			}

			if($playlistWrapper) {
				$playlistWrapper.children('li').css('background', 'none');
				$playlistWrapper.children('li').eq(currentIndex).css('background', options.activeTrackColor);
				$playlistWrapper.scrollTop($playlistWrapper.children('li').eq(0).outerHeight(true) * currentIndex);
			}

			if(playIt) {
				$uiWrapper.find('#fap-play-pause').removeClass('fap-play').addClass('fap-pause');
			}
			else {
				$uiWrapper.find('#fap-play-pause').removeClass('fap-pause').addClass('fap-play');
			}

			//destroy sound
			if(player) {
				player.destruct();
			}

			//options for soundmanager
			var soundManagerOptions = {
				id: 'fap_sound',
				autoPlay: playIt,
				autoLoad: options.autoLoad,
				volume: currentVolume,
				whileloading: _onLoading,
				whileplaying: _onPlaying,
				onfinish: _onFinish,
				onload: _onLoad
			};

			if(isSoundcloud) {
				$metaWrapper.children('#fap-social-links').children('a:eq(3)').show();
				if(tracks[currentIndex].downloadable) { $metaWrapper.children('#fap-social-links').children('a:eq(2)').show(); }
				else { $metaWrapper.children('#fap-social-links').children('a:eq(2)').hide(); }

				$.extend(soundManagerOptions, {id: "fap_sound", url: tracks[currentIndex].stream_url+'?client_id='+SC.options.client_id});
				/*SC.stream(tracks[currentIndex].stream_url,
					soundManagerOptions,
					function(sound){
						if(sound) {
							player = sound;
						}
						else {
							alert("Streaming could not be started. Please try again!");
						}
				  	}
				);*/
			}
			else {
				$metaWrapper.children('#fap-social-links').children('a:eq(2), a:eq(3)').hide();
				$.extend(soundManagerOptions, {id: "fap_sound", url: tracks[currentIndex].stream_url});
			}

			player = soundManager.createSound(soundManagerOptions);

			$elem.trigger('onFapTrackSelect', [ tracks[currentIndex] ]);

		};


		//removes all tracks from the playlist and stops playing - states: open, close, openPlaylist, closePlaylist
		$.fullwidthAudioPlayer.setPlayerPosition = function(state, animated) {
			if($wrapper.is(':animated')) { return false; }
			if(state == "open") {
				$main.children('#fap-wrapper-switcher').html('&times;');
				if(options.wrapperPosition == 'top') {
					$wrapper.animate({'top': -(totalHeight-options.height)}, animated ? 300 : 0);
				}
				else {
					$wrapper.animate({'bottom': -(totalHeight-options.height)}, animated ? 300 : 0);
				}
				options.opened = true;
			}
			else if(state == "close") {
				$main.children('#fap-wrapper-switcher').html('+');
				if(options.wrapperPosition == 'top') {
					$wrapper.animate({'top': -totalHeight-1}, animated ? 300 : 0);
				}
				else {
					$wrapper.animate({'bottom': -totalHeight-1}, animated ? 300 : 0);
				}
				options.opened = playlistIsOpened = false;
			}
			else if(state == "openPlaylist") {
				if(options.wrapperPosition == 'top') {
					$wrapper.animate({'top': 0}, 300, function() {
						$playlistWrapper.getNiceScroll().resize().show();
					});
				}
				else {
					$wrapper.animate({'bottom': 0}, 300, function() {
						$playlistWrapper.getNiceScroll().resize().show();
					});
				}
				playlistIsOpened = true;
			}
			else if(state == "closePlaylist") {
				$playlistWrapper.getNiceScroll().hide();
				if(options.wrapperPosition == 'top') {
					$wrapper.animate({'top': -(totalHeight-options.height)}, 300);
				}
				else {
					$wrapper.animate({'bottom': -(totalHeight-options.height)}, 300);
				}
				playlistIsOpened = false;
			}
		};

		//removes all tracks from the playlist and stops playing
		$.fullwidthAudioPlayer.clear = function() {

			//reset everything
			$metaWrapper.children('#fap-current-cover').hide();
			$metaWrapper.children('#fap-cover-replacement').show();
			$metaWrapper.children('#fap-current-title, #fap-current-meta').html('');
			$metaWrapper.children('#fap-social-links').children('a').attr('href', '').hide();
			$uiWrapper.find('#fap-progress-bar, #fap-loading-bar').width(0);
			$uiWrapper.find('#fap-current-time, #fap-total-time').text('00:00:00');
			$uiWrapper.find('#fap-play-pause').removeClass('fap-pause').addClass('fap-play');

			paused = true;
			currentIndex = -1;

			if($playlistWrapper) {
			    $playlistWrapper.empty();
			}
			tracks = [];
			if(player) { player.destruct(); }

			if(options.playlist) {
				$playlistWrapper.getNiceScroll().resize();
			}

			$elem.trigger('onFapClear');

		};

		//pop up player
		$.fullwidthAudioPlayer.popUp = function() {

			if(popupMode) {
				if(!window.fapPopupWin || window.fapPopupWin.closed) {
					_addTrackToPopup('', false);
				}
				else {
					window.fapPopupWin.focus();
				}
			}

		};


		//*********************************************
		//************** PRIVATE METHODS ******************
		//*********************************************

		function _createHtmlFromNode(node) {
			var html = '<a href="'+node.attr('href')+'" title="'+(node.attr('title') ? node.attr('title') : '')+'" target="'+(node.attr('target') ? node.attr('target') : '')+'" rel="'+(node.attr('rel') ? node.attr('rel') : '')+'" data-meta="'+(node.data('meta') ? node.data('meta') : '')+'"></a>';
			if(node.data('meta')) {
				var metaText = $('body').find(node.data('meta')).html() ? $('body').find(node.data('meta')).html() : '';
				html += '<span id="'+node.data('meta').substring(1)+'">'+metaText+'</span>';
			}
			return html;
		};

		//get track(s) from soundcloud link
		function _getTracksFromExternalSource(linkUrl) {

			if(RegExp('http(s?)://soundcloud').test(linkUrl)) {
				//replace likes with favorites
				linkUrl = linkUrl.replace("/likes", "/favorites");

				//load soundcloud data from tracks
	            SC.get('/resolve', {url: linkUrl}, function(data, error){
	            	if(error && error.message) {
		            	return false;
	            	}
	            	var loadIndex = -1, temp = -1;
	            	//favorites(likes)
	            	if($.isArray(data)) {
		            	for(var i=0; i < data.length; ++i) {
							temp = _storeTrackDatas(data[i]);
							loadIndex = temp < loadIndex ? temp : loadIndex;
							if(i == 0) { loadIndex = temp; }
						}
	            	}
	            	//sets
	            	else if(data.kind == "playlist") {
		            	for(var i=0; i < data.tracks.length; ++i) {
							temp = _storeTrackDatas(data.tracks[i]);
							loadIndex = temp < loadIndex ? temp : loadIndex;
							if(i == 0) { loadIndex = temp; }
						}
	            	}
	            	//user tracks
	            	else if(data.kind == "user") {
		            	SC.get("/users/"+data.id+"/tracks", function(data, error){

		            		for(var i=0; i < data.length; ++i) {
								temp = _storeTrackDatas(data[i]);
								loadIndex = temp < loadIndex ? temp : loadIndex;
								if(i == 0) { loadIndex = temp; }
							}
							$elem.trigger('onFapTracksAdded', [tracks]);
							$elem.trigger('fap-tracks-stored', [loadIndex]);
		            	});
	            	}
	            	//group tracks
	            	else if(data.kind == "group") {
		            	SC.get("/groups/"+data.id+"/tracks", function(data, error){
		            		for(var i=0; i < data.length; ++i) {
								temp = _storeTrackDatas(data[i]);
								loadIndex = temp < loadIndex ? temp : loadIndex;
								if(i == 0) { loadIndex = temp; }
							}
							$elem.trigger('onFapTracksAdded', [tracks]);
							$elem.trigger('fap-tracks-stored', [loadIndex]);
		            	});
	            	}
	            	//single track
	            	else {
		            	if(data.kind == "track") {
			            	loadIndex = _storeTrackDatas(data);
		            	}
	            	}
	            	if(loadIndex >= 0) {
		            	$elem.trigger('onFapTracksAdded', [tracks]);
						$elem.trigger('fap-tracks-stored', [loadIndex]);
	            	}

	            });
			}
			else if(RegExp('http(s?)://official.fm').test(linkUrl)) {
				var trackId = linkUrl.substr(linkUrl.lastIndexOf('/tracks')+8);
				$.getJSON('http://api.official.fm/tracks/'+trackId+'?fields=streaming,cover&api_version=2', function(data) {
					var track = data.track;
					var li = _storeTrackDatas({stream_url: track.streaming.http, title: track.artist + ' - ' + track.title, meta: track.project.name, artwork_url: track.cover.urls.small, permalink_url:track.page});
					$elem.trigger('onFapTracksAdded', [tracks]);
					$elem.trigger('fap-tracks-stored', [li]);
				});

			}

		};

		//store track datas from soundcloud
		function _storeTrackDatas(data) {
			//search if a track with a same title already exists
			var trackIndex = tracks.length;
			for(var i= 0; i < tracks.length; ++i) {
				if(data.title == tracks[i].title) {
					trackIndex = i;
					return trackIndex;
					break;

				}
			}

			tracks.push(data);
			_createPlaylistTrack(data.artwork_url, data.title);

			if(options.storePlaylist) { amplify.store('fap-playlist', JSON.stringify(tracks)); }

			return trackIndex;
		};


		//soundmanager loading
		function _onLoading() {

			$uiWrapper.find('#fap-loading-bar').width(( this.bytesLoaded / this.bytesTotal) * timeBarWidth);
		};

		//soundmaanger playing
		function _onPlaying() {
			_setTimes(this.position, this.duration);
		};

		//soundmanager finish
		function _onFinish() {
			if(options.playNextWhenFinished) {
				$.fullwidthAudioPlayer.next();
			}
			else {
				$.fullwidthAudioPlayer.pause();
				player.setPosition(0);
				_setSliderPosition(0);
			}
		};

		//soundmanager file load
		function _onLoad(state) {
			if(!state) {
				if(window.console && window.console.log) {
					console.log("Track could not be loaded! Please check the URL: "+this.url);
				}
			}
		};

		//create a new playlist item in the playlist
		function _createPlaylistTrack(cover, title) {

			if(!options.playlist) { return false; }
            var coverDom = cover ? '<img src="'+cover+'" style="border: 1px solid '+options.strokeColor+';" />' : '<div class="fap-cover-replace-small" style="background: '+options.wrapperColor+'; border: 1px solid '+options.strokeColor+';"></div>';

			$playlistWrapper.append('<li class="clearfix">'+coverDom+'<span>'+title+'</span><div class="fap-remove-track">&times;</div></li>');
			var listItem = $playlistWrapper.children('li').last().css({'marginBottom': 5, 'height': 22});

			if(navigator.appVersion.indexOf("MSIE 7.")==-1) {
				if(!cover) { _createCoverReplacement(listItem.children('.fap-cover-replace-small').get(0), 20, 20); }
			}

			//Playlist Item Event Handlers
			if($elem.jquery >= "1.7"){
				listItem.on('click', 'span', _selectTrackFromPlaylist);
				listItem.on('click', '.fap-remove-track', _removeTrackFromPlaylist);
			}
			else {
				listItem.delegate('span', 'click', _selectTrackFromPlaylist);
				listItem.delegate('.fap-remove-track', 'click', _removeTrackFromPlaylist);
			}

			function _selectTrackFromPlaylist() {
				var $listItem = $(this).parent();
				if($listItem.hasClass('fap-prevent-click')) {
					$listItem.removeClass('fap-prevent-click');
				}
				else {
					var index = $playlistWrapper.children('li').index($listItem);
					$.fullwidthAudioPlayer.selectTrack(index, true);
				}
			};

			function _removeTrackFromPlaylist() {
				var $this = $(this),
					index = $this.parent().parent().children('li').index($this.parent());

				tracks.splice(index, 1);
				$this.parent().remove();

				if(index == currentIndex) {
					currentIndex--;
					index = index == tracks.length ? 0 : index;
				    $.fullwidthAudioPlayer.selectTrack(index, paused ? false : true);
				}
				else if(index < currentIndex) {
					currentIndex--;
				}

				$playlistWrapper.getNiceScroll().resize();
				if(options.storePlaylist) { amplify.store('fap-playlist', JSON.stringify(tracks)); }
			};

			$playlistWrapper.getNiceScroll().resize();

		};

		//creates a cover replacement when track has no artwork image
		function _createCoverReplacement(container, width, height) {
			$(container).append('<span style="line-height: '+height+'px; color: '+options.metaColor+';">&hellip;</span>');
		};

		//set the time slider position
		function _setSliderPosition(playProgress) {
		    $uiWrapper.find('#fap-progress-bar').width(playProgress * timeBarWidth);
		};

		//update the current and total time
		function _setTimes(position, duration) {
			var time = _convertTime(position/1000);
			if(currentTime != time) {
				$uiWrapper.find('#fap-current-time').text(time);
				$uiWrapper.find('#fap-total-time').text(_convertTime(duration / 1000));
				_setSliderPosition(position / duration);
			}
			currentTime = time;
		};

		//converts seconds into a well formatted time
		function _convertTime(second) {
			second = Math.abs(second);
			var val = new Array();
			val[0] = Math.floor(second/3600%24);//hours
			val[1] = Math.floor(second/60%60);//mins
			val[2] = Math.floor(second%60);//secs
			var stopage = true;
			var cutIndex  = -1;
			for(var i = 0; i < val.length; i++) {
				if(val[i] < 10) val[i] = "0" + val[i];
				if( val[i] == "00" && i < (val.length - 2) && !stopage) cutIndex = i;
				else stopage = true;
			}
			val.splice(0, cutIndex + 1);
			return val.join(':');
		};

		function _shufflePlaylist() {
			if($playlistWrapper) {
				$playlistWrapper.empty();
			}
			//action for the shuffle button
			if(currentIndex != -1) {
				var tempTitle = tracks[currentIndex].title;
				tracks.shuffle();
				_updateTrackIndex(tempTitle);
				for(var i=0; i < tracks.length; ++i) {
					_createPlaylistTrack(tracks[i].artwork_url, tracks[i].title);
				}
				$main.find('#fap-playlist').children('li').eq(currentIndex).css('backgroundColor', options.fillColor);
				$main.find('#fap-playlist').scrollTop(0);
			}
			//action for randomize option
			else {
				tracks.shuffle();
				for(var i=0; i < tracks.length; ++i) {
					_createPlaylistTrack(tracks[i].artwork_url, tracks[i].title);
				}

			}
			if(options.storePlaylist) { amplify.store('fap-playlist', JSON.stringify(tracks)); }
		};

		//array shuffle
		function _arrayShuffle(){
		  var tmp, rand;
		  for(var i =0; i < this.length; i++){
			rand = Math.floor(Math.random() * this.length);
			tmp = this[i];
			this[i] = this[rand];
			this[rand] = tmp;
		  }
		};
		Array.prototype.shuffle = _arrayShuffle;

		function _updateTrackIndex(title) {
			for(var i=0; i < tracks.length; ++i) {
				if(tracks[i].title == title) { currentIndex = i; }
			}
		};

		function _decodebase64(input) {
			var _keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
		    var output = "";
		    var chr1, chr2, chr3;
		    var enc1, enc2, enc3, enc4;
		    var i = 0;

		    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

		    while (i < input.length) {

		        enc1 = _keyStr.indexOf(input.charAt(i++));
		        enc2 = _keyStr.indexOf(input.charAt(i++));
		        enc3 = _keyStr.indexOf(input.charAt(i++));
		        enc4 = _keyStr.indexOf(input.charAt(i++));

		        chr1 = (enc1 << 2) | (enc2 >> 4);
		        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
		        chr3 = ((enc3 & 3) << 6) | enc4;

		        output = output + String.fromCharCode(chr1);

		        if (enc3 != 64) {
		            output = output + String.fromCharCode(chr2);
		        }
		        if (enc4 != 64) {
		            output = output + String.fromCharCode(chr3);
		        }

		    }

		    output = _utf8_decode(output);

		    return output;
		};

		function _utf8_decode(utftext) {
		    var string = "";
		    var i = 0;
		    var c = c1 = c2 = 0;

		    while ( i < utftext.length ) {

		        c = utftext.charCodeAt(i);

		        if (c < 128) {
		            string += String.fromCharCode(c);
		            i++;
		        }
		        else if((c > 191) && (c < 224)) {
		            c2 = utftext.charCodeAt(i+1);
		            string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
		            i += 2;
		        }
		        else {
		            c2 = utftext.charCodeAt(i+1);
		            c3 = utftext.charCodeAt(i+2);
		            string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
		            i += 3;
		        }

		    }

		    return string;
		};

		function _detectMobileBrowsers() {
			return /Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent);
		};

		return this.each(function() {_init(this)});
	};


	//OPTIONS
	$.fn.fullwidthAudioPlayer.defaults = {
		wrapperPosition: 'bottom', //top, bottom or popup
		mainPosition: 'center', //left, center or right
		wrapperColor: '#f0f0f0', //background color of the wrapper
		mainColor: '#3c3c3c',
		fillColor: '#e3e3e3',
		metaColor: '#666666',
		strokeColor: '#e0e0e0',
		fillColorHover: '#d1d1d1',
		activeTrackColor: '#E8E8E8',
		twitterText: 'Share on Twitter',
		facebookText: 'Share on Facebook',
		downloadText: 'Download',
		layout: 'fullwidth', //V1.5 - fullwidth or boxed
		popupUrl: 'popup.html', //- since V1.3
		height: 80, // the height of the wrapper
		playlistHeight: 210, //set the playlist height for the scrolling
		coverSize: [65, 65], //size (width,height) of the cover
		offset: 20, //offset between playlist and upper content
		opened: true,
		volume: true, // show/hide volume control
		playlist: true, //show/hide playlist
		autoLoad: true, //loads the music file when soundmanager is ready
		autoPlay: false, //enable/disbale autoplay
		playNextWhenFinished: true, //plays the next track when current one has finished
		keyboard: true, //enable/disable the keyboard shortcuts
		socials: true, //hide/show social links
		autoPopup: false, //pop out player in a new window automatically - since V1.3
		randomize: false, //randomize default playlist - since V1.3
		shuffle: true, //show/hide shuffle button - since V1.3
		sortable: false, //sortable playlist
		base64: false, //set to true when you encode your mp3 urls with base64
		xmlPath: '', //the xml path
		xmlPlaylist: '', //the ID of the playlist which should be loaded into player from the XML file
		hideOnMobile: false, //1.4.1 - Hide the player on mobile devices
		loopPlaylist: true, //1.5 - When end of playlist has been reached, start from beginning
		storePlaylist: false //1.5 - Stores the playlist in the browser
	};

})(jQuery);

/*
    json2.js
    2013-05-26

    Public Domain.

    NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.

    See http://www.JSON.org/js.html


    This code should be minified before deployment.
    See http://javascript.crockford.com/jsmin.html

    USE YOUR OWN COPY. IT IS EXTREMELY UNWISE TO LOAD CODE FROM SERVERS YOU DO
    NOT CONTROL.


    This file creates a global JSON object containing two methods: stringify
    and parse.

        JSON.stringify(value, replacer, space)
            value       any JavaScript value, usually an object or array.

            replacer    an optional parameter that determines how object
                        values are stringified for objects. It can be a
                        function or an array of strings.

            space       an optional parameter that specifies the indentation
                        of nested structures. If it is omitted, the text will
                        be packed without extra whitespace. If it is a number,
                        it will specify the number of spaces to indent at each
                        level. If it is a string (such as '\t' or '&nbsp;'),
                        it contains the characters used to indent at each level.

            This method produces a JSON text from a JavaScript value.

            When an object value is found, if the object contains a toJSON
            method, its toJSON method will be called and the result will be
            stringified. A toJSON method does not serialize: it returns the
            value represented by the name/value pair that should be serialized,
            or undefined if nothing should be serialized. The toJSON method
            will be passed the key associated with the value, and this will be
            bound to the value

            For example, this would serialize Dates as ISO strings.

                Date.prototype.toJSON = function (key) {
                    function f(n) {
                        // Format integers to have at least two digits.
                        return n < 10 ? '0' + n : n;
                    }

                    return this.getUTCFullYear()   + '-' +
                         f(this.getUTCMonth() + 1) + '-' +
                         f(this.getUTCDate())      + 'T' +
                         f(this.getUTCHours())     + ':' +
                         f(this.getUTCMinutes())   + ':' +
                         f(this.getUTCSeconds())   + 'Z';
                };

            You can provide an optional replacer method. It will be passed the
            key and value of each member, with this bound to the containing
            object. The value that is returned from your method will be
            serialized. If your method returns undefined, then the member will
            be excluded from the serialization.

            If the replacer parameter is an array of strings, then it will be
            used to select the members to be serialized. It filters the results
            such that only members with keys listed in the replacer array are
            stringified.

            Values that do not have JSON representations, such as undefined or
            functions, will not be serialized. Such values in objects will be
            dropped; in arrays they will be replaced with null. You can use
            a replacer function to replace those with JSON values.
            JSON.stringify(undefined) returns undefined.

            The optional space parameter produces a stringification of the
            value that is filled with line breaks and indentation to make it
            easier to read.

            If the space parameter is a non-empty string, then that string will
            be used for indentation. If the space parameter is a number, then
            the indentation will be that many spaces.

            Example:

            text = JSON.stringify(['e', {pluribus: 'unum'}]);
            // text is '["e",{"pluribus":"unum"}]'


            text = JSON.stringify(['e', {pluribus: 'unum'}], null, '\t');
            // text is '[\n\t"e",\n\t{\n\t\t"pluribus": "unum"\n\t}\n]'

            text = JSON.stringify([new Date()], function (key, value) {
                return this[key] instanceof Date ?
                    'Date(' + this[key] + ')' : value;
            });
            // text is '["Date(---current time---)"]'


        JSON.parse(text, reviver)
            This method parses a JSON text to produce an object or array.
            It can throw a SyntaxError exception.

            The optional reviver parameter is a function that can filter and
            transform the results. It receives each of the keys and values,
            and its return value is used instead of the original value.
            If it returns what it received, then the structure is not modified.
            If it returns undefined then the member is deleted.

            Example:

            // Parse the text. Values that look like ISO date strings will
            // be converted to Date objects.

            myData = JSON.parse(text, function (key, value) {
                var a;
                if (typeof value === 'string') {
                    a =
/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2}(?:\.\d*)?)Z$/.exec(value);
                    if (a) {
                        return new Date(Date.UTC(+a[1], +a[2] - 1, +a[3], +a[4],
                            +a[5], +a[6]));
                    }
                }
                return value;
            });

            myData = JSON.parse('["Date(09/09/2001)"]', function (key, value) {
                var d;
                if (typeof value === 'string' &&
                        value.slice(0, 5) === 'Date(' &&
                        value.slice(-1) === ')') {
                    d = new Date(value.slice(5, -1));
                    if (d) {
                        return d;
                    }
                }
                return value;
            });


    This is a reference implementation. You are free to copy, modify, or
    redistribute.
*/

/*jslint evil: true, regexp: true */

/*members "", "\b", "\t", "\n", "\f", "\r", "\"", JSON, "\\", apply,
    call, charCodeAt, getUTCDate, getUTCFullYear, getUTCHours,
    getUTCMinutes, getUTCMonth, getUTCSeconds, hasOwnProperty, join,
    lastIndex, length, parse, prototype, push, replace, slice, stringify,
    test, toJSON, toString, valueOf
*/


// Create a JSON object only if one does not already exist. We create the
// methods in a closure to avoid creating global variables.

if (typeof JSON !== 'object') {
    JSON = {};
}

(function () {
    'use strict';

    function f(n) {
        // Format integers to have at least two digits.
        return n < 10 ? '0' + n : n;
    }

    if (typeof Date.prototype.toJSON !== 'function') {

        Date.prototype.toJSON = function () {

            return isFinite(this.valueOf())
                ? this.getUTCFullYear()     + '-' +
                    f(this.getUTCMonth() + 1) + '-' +
                    f(this.getUTCDate())      + 'T' +
                    f(this.getUTCHours())     + ':' +
                    f(this.getUTCMinutes())   + ':' +
                    f(this.getUTCSeconds())   + 'Z'
                : null;
        };

        String.prototype.toJSON      =
            Number.prototype.toJSON  =
            Boolean.prototype.toJSON = function () {
                return this.valueOf();
            };
    }

    var cx = /[\u0000\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        escapable = /[\\\"\x00-\x1f\x7f-\x9f\u00ad\u0600-\u0604\u070f\u17b4\u17b5\u200c-\u200f\u2028-\u202f\u2060-\u206f\ufeff\ufff0-\uffff]/g,
        gap,
        indent,
        meta = {    // table of character substitutions
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        rep;


    function quote(string) {

// If the string contains no control characters, no quote characters, and no
// backslash characters, then we can safely slap some quotes around it.
// Otherwise we must also replace the offending characters with safe escape
// sequences.

        escapable.lastIndex = 0;
        return escapable.test(string) ? '"' + string.replace(escapable, function (a) {
            var c = meta[a];
            return typeof c === 'string'
                ? c
                : '\\u' + ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
        }) + '"' : '"' + string + '"';
    }


    function str(key, holder) {

// Produce a string from holder[key].

        var i,          // The loop counter.
            k,          // The member key.
            v,          // The member value.
            length,
            mind = gap,
            partial,
            value = holder[key];

// If the value has a toJSON method, call it to obtain a replacement value.

        if (value && typeof value === 'object' &&
                typeof value.toJSON === 'function') {
            value = value.toJSON(key);
        }

// If we were called with a replacer function, then call the replacer to
// obtain a replacement value.

        if (typeof rep === 'function') {
            value = rep.call(holder, key, value);
        }

// What happens next depends on the value's type.

        switch (typeof value) {
        case 'string':
            return quote(value);

        case 'number':

// JSON numbers must be finite. Encode non-finite numbers as null.

            return isFinite(value) ? String(value) : 'null';

        case 'boolean':
        case 'null':

// If the value is a boolean or null, convert it to a string. Note:
// typeof null does not produce 'null'. The case is included here in
// the remote chance that this gets fixed someday.

            return String(value);

// If the type is 'object', we might be dealing with an object or an array or
// null.

        case 'object':

// Due to a specification blunder in ECMAScript, typeof null is 'object',
// so watch out for that case.

            if (!value) {
                return 'null';
            }

// Make an array to hold the partial results of stringifying this object value.

            gap += indent;
            partial = [];

// Is the value an array?

            if (Object.prototype.toString.apply(value) === '[object Array]') {

// The value is an array. Stringify every element. Use null as a placeholder
// for non-JSON values.

                length = value.length;
                for (i = 0; i < length; i += 1) {
                    partial[i] = str(i, value) || 'null';
                }

// Join all of the elements together, separated with commas, and wrap them in
// brackets.

                v = partial.length === 0
                    ? '[]'
                    : gap
                    ? '[\n' + gap + partial.join(',\n' + gap) + '\n' + mind + ']'
                    : '[' + partial.join(',') + ']';
                gap = mind;
                return v;
            }

// If the replacer is an array, use it to select the members to be stringified.

            if (rep && typeof rep === 'object') {
                length = rep.length;
                for (i = 0; i < length; i += 1) {
                    if (typeof rep[i] === 'string') {
                        k = rep[i];
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            } else {

// Otherwise, iterate through all of the keys in the object.

                for (k in value) {
                    if (Object.prototype.hasOwnProperty.call(value, k)) {
                        v = str(k, value);
                        if (v) {
                            partial.push(quote(k) + (gap ? ': ' : ':') + v);
                        }
                    }
                }
            }

// Join all of the member texts together, separated with commas,
// and wrap them in braces.

            v = partial.length === 0
                ? '{}'
                : gap
                ? '{\n' + gap + partial.join(',\n' + gap) + '\n' + mind + '}'
                : '{' + partial.join(',') + '}';
            gap = mind;
            return v;
        }
    }

// If the JSON object does not yet have a stringify method, give it one.

    if (typeof JSON.stringify !== 'function') {
        JSON.stringify = function (value, replacer, space) {

// The stringify method takes a value and an optional replacer, and an optional
// space parameter, and returns a JSON text. The replacer can be a function
// that can replace values, or an array of strings that will select the keys.
// A default replacer method can be provided. Use of the space parameter can
// produce text that is more easily readable.

            var i;
            gap = '';
            indent = '';

// If the space parameter is a number, make an indent string containing that
// many spaces.

            if (typeof space === 'number') {
                for (i = 0; i < space; i += 1) {
                    indent += ' ';
                }

// If the space parameter is a string, it will be used as the indent string.

            } else if (typeof space === 'string') {
                indent = space;
            }

// If there is a replacer, it must be a function or an array.
// Otherwise, throw an error.

            rep = replacer;
            if (replacer && typeof replacer !== 'function' &&
                    (typeof replacer !== 'object' ||
                    typeof replacer.length !== 'number')) {
                throw new Error('JSON.stringify');
            }

// Make a fake root object containing our value under the key of ''.
// Return the result of stringifying the value.

            return str('', {'': value});
        };
    }


// If the JSON object does not yet have a parse method, give it one.

    if (typeof JSON.parse !== 'function') {
        JSON.parse = function (text, reviver) {

// The parse method takes a text and an optional reviver function, and returns
// a JavaScript value if the text is a valid JSON text.

            var j;

            function walk(holder, key) {

// The walk method is used to recursively walk the resulting structure so
// that modifications can be made.

                var k, v, value = holder[key];
                if (value && typeof value === 'object') {
                    for (k in value) {
                        if (Object.prototype.hasOwnProperty.call(value, k)) {
                            v = walk(value, k);
                            if (v !== undefined) {
                                value[k] = v;
                            } else {
                                delete value[k];
                            }
                        }
                    }
                }
                return reviver.call(holder, key, value);
            }


// Parsing happens in four stages. In the first stage, we replace certain
// Unicode characters with escape sequences. JavaScript handles many characters
// incorrectly, either silently deleting them, or treating them as line endings.

            text = String(text);
            cx.lastIndex = 0;
            if (cx.test(text)) {
                text = text.replace(cx, function (a) {
                    return '\\u' +
                        ('0000' + a.charCodeAt(0).toString(16)).slice(-4);
                });
            }

// In the second stage, we run the text against regular expressions that look
// for non-JSON patterns. We are especially concerned with '()' and 'new'
// because they can cause invocation, and '=' because it can cause mutation.
// But just to be safe, we want to reject all unexpected forms.

// We split the second stage into 4 regexp operations in order to work around
// crippling inefficiencies in IE's and Safari's regexp engines. First we
// replace the JSON backslash pairs with '@' (a non-JSON character). Second, we
// replace all simple value tokens with ']' characters. Third, we delete all
// open brackets that follow a colon or comma or that begin the text. Finally,
// we look to see that the remaining characters are only whitespace or ']' or
// ',' or ':' or '{' or '}'. If that is so, then the text is safe for eval.

            if (/^[\],:{}\s]*$/
                    .test(text.replace(/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g, '@')
                        .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
                        .replace(/(?:^|:|,)(?:\s*\[)+/g, ''))) {

// In the third stage we use the eval function to compile the text into a
// JavaScript structure. The '{' operator is subject to a syntactic ambiguity
// in JavaScript: it can begin a block or an object literal. We wrap the text
// in parens to eliminate the ambiguity.

                j = eval('(' + text + ')');

// In the optional fourth stage, we recursively walk the new structure, passing
// each name/value pair to a reviver function for possible transformation.

                return typeof reviver === 'function'
                    ? walk({'': j}, '')
                    : j;
            }

// If the text is not JSON parseable, then a SyntaxError is thrown.

            throw new SyntaxError('JSON.parse');
        };
    }
}());


/* jquery.nicescroll 3.4.1 InuYaksa*2013 MIT http://areaaperta.com/nicescroll */(function(e){var D=!1,G=!1,N=5E3,O=2E3,C=0,J,u=document.getElementsByTagName("script"),u=u[u.length-1].src.split("?")[0];J=0<u.split("/").length?u.split("/").slice(0,-1).join("/")+"/":"";Array.prototype.forEach||(Array.prototype.forEach=function(e,d){for(var k=0,s=this.length;k<s;++k)e.call(d,this[k],k,this)});var w=window.requestAnimationFrame||!1,x=window.cancelAnimationFrame||!1;["ms","moz","webkit","o"].forEach(function(e){w||(w=window[e+"RequestAnimationFrame"]);x||(x=window[e+"CancelAnimationFrame"]||
window[e+"CancelRequestAnimationFrame"])});var E=window.MutationObserver||window.WebKitMutationObserver||!1,K={zindex:"auto",cursoropacitymin:0,cursoropacitymax:1,cursorcolor:"#424242",cursorwidth:"5px",cursorborder:"1px solid #fff",cursorborderradius:"5px",scrollspeed:60,mousescrollstep:24,touchbehavior:!1,hwacceleration:!0,usetransition:!0,boxzoom:!1,dblclickzoom:!0,gesturezoom:!0,grabcursorenabled:!0,autohidemode:!0,background:"",iframeautoresize:!0,cursorminheight:32,preservenativescrolling:!0,
railoffset:!1,bouncescroll:!0,spacebarenabled:!0,railpadding:{top:0,right:0,left:0,bottom:0},disableoutline:!0,horizrailenabled:!0,railalign:"right",railvalign:"bottom",enabletranslate3d:!0,enablemousewheel:!0,enablekeyboard:!0,smoothscroll:!0,sensitiverail:!0,enablemouselockapi:!0,cursorfixedheight:!1,directionlockdeadzone:6,hidecursordelay:400,nativeparentscrolling:!0,enablescrollonselection:!0,overflowx:!0,overflowy:!0,cursordragspeed:0.3,rtlmode:!1,cursordragontouch:!1},H=!1,P=function(j,d){function k(){var c=
b.win;if("zIndex"in c)return c.zIndex();for(;0<c.length&&9!=c[0].nodeType;){var g=c.css("zIndex");if(!isNaN(g)&&0!=g)return parseInt(g);c=c.parent()}return!1}function s(c,g,p){g=c.css(g);c=parseFloat(g);return isNaN(c)?(c=B[g]||0,p=3==c?p?b.win.outerHeight()-b.win.innerHeight():b.win.outerWidth()-b.win.innerWidth():1,b.isie8&&c&&(c+=1),p?c:0):c}function n(c,g,p,d){b._bind(c,g,function(b){b=b?b:window.event;var d={original:b,target:b.target||b.srcElement,type:"wheel",deltaMode:"MozMousePixelScroll"==
b.type?0:1,deltaX:0,deltaZ:0,preventDefault:function(){b.preventDefault?b.preventDefault():b.returnValue=!1;return!1},stopImmediatePropagation:function(){b.stopImmediatePropagation?b.stopImmediatePropagation():b.cancelBubble=!0}};"mousewheel"==g?(d.deltaY=-0.025*b.wheelDelta,b.wheelDeltaX&&(d.deltaX=-0.025*b.wheelDeltaX)):d.deltaY=b.detail;return p.call(c,d)},d)}function v(c,g,d){var f,e;0==c.deltaMode?(f=-Math.floor(c.deltaX*(b.opt.mousescrollstep/54)),e=-Math.floor(c.deltaY*(b.opt.mousescrollstep/
54))):1==c.deltaMode&&(f=-Math.floor(c.deltaX*b.opt.mousescrollstep),e=-Math.floor(c.deltaY*b.opt.mousescrollstep));g&&(0==f&&e)&&(f=e,e=0);f&&(b.scrollmom&&b.scrollmom.stop(),b.lastdeltax+=f,b.debounced("mousewheelx",function(){var c=b.lastdeltax;b.lastdeltax=0;b.rail.drag||b.doScrollLeftBy(c)},120));if(e){if(b.opt.nativeparentscrolling&&d&&!b.ispage&&!b.zoomactive)if(0>e){if(b.getScrollTop()>=b.page.maxh)return!0}else if(0>=b.getScrollTop())return!0;b.scrollmom&&b.scrollmom.stop();b.lastdeltay+=
e;b.debounced("mousewheely",function(){var c=b.lastdeltay;b.lastdeltay=0;b.rail.drag||b.doScrollBy(c)},120)}c.stopImmediatePropagation();return c.preventDefault()}var b=this;this.version="3.4.0";this.name="nicescroll";this.me=d;this.opt={doc:e("body"),win:!1};e.extend(this.opt,K);this.opt.snapbackspeed=80;if(j)for(var m in b.opt)"undefined"!=typeof j[m]&&(b.opt[m]=j[m]);this.iddoc=(this.doc=b.opt.doc)&&this.doc[0]?this.doc[0].id||"":"";this.ispage=/BODY|HTML/.test(b.opt.win?b.opt.win[0].nodeName:
this.doc[0].nodeName);this.haswrapper=!1!==b.opt.win;this.win=b.opt.win||(this.ispage?e(window):this.doc);this.docscroll=this.ispage&&!this.haswrapper?e(window):this.win;this.body=e("body");this.iframe=this.isfixed=this.viewport=!1;this.isiframe="IFRAME"==this.doc[0].nodeName&&"IFRAME"==this.win[0].nodeName;this.istextarea="TEXTAREA"==this.win[0].nodeName;this.forcescreen=!1;this.canshowonmouseevent="scroll"!=b.opt.autohidemode;this.page=this.view=this.onzoomout=this.onzoomin=this.onscrollcancel=
this.onscrollend=this.onscrollstart=this.onclick=this.ongesturezoom=this.onkeypress=this.onmousewheel=this.onmousemove=this.onmouseup=this.onmousedown=!1;this.scroll={x:0,y:0};this.scrollratio={x:0,y:0};this.cursorheight=20;this.scrollvaluemax=0;this.observerremover=this.observer=this.scrollmom=this.scrollrunning=this.checkrtlmode=!1;do this.id="ascrail"+O++;while(document.getElementById(this.id));this.hasmousefocus=this.hasfocus=this.zoomactive=this.zoom=this.selectiondrag=this.cursorfreezed=this.cursor=
this.rail=!1;this.visibility=!0;this.hidden=this.locked=!1;this.cursoractive=!0;this.overflowx=b.opt.overflowx;this.overflowy=b.opt.overflowy;this.nativescrollingarea=!1;this.checkarea=0;this.events=[];this.saved={};this.delaylist={};this.synclist={};this.lastdeltay=this.lastdeltax=0;if(H)m=H;else{m=document.createElement("DIV");var h={haspointerlock:"pointerLockElement"in document||"mozPointerLockElement"in document||"webkitPointerLockElement"in document};h.isopera="opera"in window;h.isopera12=h.isopera&&
"getUserMedia"in navigator;h.isie="all"in document&&"attachEvent"in m&&!h.isopera;h.isieold=h.isie&&!("msInterpolationMode"in m.style);h.isie7=h.isie&&!h.isieold&&(!("documentMode"in document)||7==document.documentMode);h.isie8=h.isie&&"documentMode"in document&&8==document.documentMode;h.isie9=h.isie&&"performance"in window&&9<=document.documentMode;h.isie10=h.isie&&"performance"in window&&10<=document.documentMode;h.isie9mobile=/iemobile.9/i.test(navigator.userAgent);h.isie9mobile&&(h.isie9=!1);
h.isie7mobile=!h.isie9mobile&&h.isie7&&/iemobile/i.test(navigator.userAgent);h.ismozilla="MozAppearance"in m.style;h.iswebkit="WebkitAppearance"in m.style;h.ischrome="chrome"in window;h.ischrome22=h.ischrome&&h.haspointerlock;h.ischrome26=h.ischrome&&"transition"in m.style;h.cantouch="ontouchstart"in document.documentElement||"ontouchstart"in window;h.hasmstouch=window.navigator.msPointerEnabled||!1;h.ismac=/^mac$/i.test(navigator.platform);h.isios=h.cantouch&&/iphone|ipad|ipod/i.test(navigator.platform);
h.isios4=h.isios&&!("seal"in Object);h.isandroid=/android/i.test(navigator.userAgent);h.trstyle=!1;h.hastransform=!1;h.hastranslate3d=!1;h.transitionstyle=!1;h.hastransition=!1;h.transitionend=!1;for(var q=["transform","msTransform","webkitTransform","MozTransform","OTransform"],r=0;r<q.length;r++)if("undefined"!=typeof m.style[q[r]]){h.trstyle=q[r];break}h.hastransform=!1!=h.trstyle;h.hastransform&&(m.style[h.trstyle]="translate3d(1px,2px,3px)",h.hastranslate3d=/translate3d/.test(m.style[h.trstyle]));
h.transitionstyle=!1;h.prefixstyle="";h.transitionend=!1;for(var q="transition webkitTransition MozTransition OTransition OTransition msTransition KhtmlTransition".split(" "),A=" -webkit- -moz- -o- -o -ms- -khtml-".split(" "),u="transitionend webkitTransitionEnd transitionend otransitionend oTransitionEnd msTransitionEnd KhtmlTransitionEnd".split(" "),r=0;r<q.length;r++)if(q[r]in m.style){h.transitionstyle=q[r];h.prefixstyle=A[r];h.transitionend=u[r];break}h.ischrome26&&(h.prefixstyle=A[1]);h.hastransition=
h.transitionstyle;b:{q=["-moz-grab","-webkit-grab","grab"];if(h.ischrome&&!h.ischrome22||h.isie)q=[];for(r=0;r<q.length;r++)if(A=q[r],m.style.cursor=A,m.style.cursor==A){q=A;break b}q="url(http://www.google.com/intl/en_ALL/mapfiles/openhand.cur),n-resize"}h.cursorgrabvalue=q;h.hasmousecapture="setCapture"in m;h.hasMutationObserver=!1!==E;m=null;m=H=h}this.detected=m;var f=e.extend({},this.detected);this.ishwscroll=(this.canhwscroll=f.hastransform&&b.opt.hwacceleration)&&b.haswrapper;this.istouchcapable=
!1;f.cantouch&&(f.ischrome&&!f.isios&&!f.isandroid)&&(this.istouchcapable=!0,f.cantouch=!1);f.cantouch&&(f.ismozilla&&!f.isios)&&(this.istouchcapable=!0,f.cantouch=!1);b.opt.enablemouselockapi||(f.hasmousecapture=!1,f.haspointerlock=!1);this.delayed=function(c,g,d,f){var e=b.delaylist[c],h=(new Date).getTime();if(!f&&e&&e.tt)return!1;e&&e.tt&&clearTimeout(e.tt);if(e&&e.last+d>h&&!e.tt)b.delaylist[c]={last:h+d,tt:setTimeout(function(){b.delaylist[c].tt=0;g.call()},d)};else if(!e||!e.tt)b.delaylist[c]=
{last:h,tt:0},setTimeout(function(){g.call()},0)};this.debounced=function(c,g,d){var e=b.delaylist[c];(new Date).getTime();b.delaylist[c]=g;e||setTimeout(function(){var g=b.delaylist[c];b.delaylist[c]=!1;g.call()},d)};this.synched=function(c,g){b.synclist[c]=g;b.onsync||(w(function(){b.onsync=!1;for(c in b.synclist){var g=b.synclist[c];g&&g.call(b);b.synclist[c]=!1}}),b.onsync=!0);return c};this.unsynched=function(c){b.synclist[c]&&(b.synclist[c]=!1)};this.css=function(c,g){for(var d in g)b.saved.css.push([c,
d,c.css(d)]),c.css(d,g[d])};this.scrollTop=function(c){return"undefined"==typeof c?b.getScrollTop():b.setScrollTop(c)};this.scrollLeft=function(c){return"undefined"==typeof c?b.getScrollLeft():b.setScrollLeft(c)};BezierClass=function(b,g,d,e,f,h,k){this.st=b;this.ed=g;this.spd=d;this.p1=e||0;this.p2=f||1;this.p3=h||0;this.p4=k||1;this.ts=(new Date).getTime();this.df=this.ed-this.st};BezierClass.prototype={B2:function(b){return 3*b*b*(1-b)},B3:function(b){return 3*b*(1-b)*(1-b)},B4:function(b){return(1-
b)*(1-b)*(1-b)},getNow:function(){var b=1-((new Date).getTime()-this.ts)/this.spd,g=this.B2(b)+this.B3(b)+this.B4(b);return 0>b?this.ed:this.st+Math.round(this.df*g)},update:function(b,g){this.st=this.getNow();this.ed=b;this.spd=g;this.ts=(new Date).getTime();this.df=this.ed-this.st;return this}};if(this.ishwscroll){this.doc.translate={x:0,y:0,tx:"0px",ty:"0px"};f.hastranslate3d&&f.isios&&this.doc.css("-webkit-backface-visibility","hidden");var z=function(){var c=b.doc.css(f.trstyle);return c&&"matrix"==
c.substr(0,6)?c.replace(/^.*\((.*)\)$/g,"$1").replace(/px/g,"").split(/, +/):!1};this.getScrollTop=function(c){if(!c){if(c=z())return 16==c.length?-c[13]:-c[5];if(b.timerscroll&&b.timerscroll.bz)return b.timerscroll.bz.getNow()}return b.doc.translate.y};this.getScrollLeft=function(c){if(!c){if(c=z())return 16==c.length?-c[12]:-c[4];if(b.timerscroll&&b.timerscroll.bh)return b.timerscroll.bh.getNow()}return b.doc.translate.x};this.notifyScrollEvent=document.createEvent?function(b){var g=document.createEvent("UIEvents");
g.initUIEvent("scroll",!1,!0,window,1);b.dispatchEvent(g)}:document.fireEvent?function(b){var g=document.createEventObject();b.fireEvent("onscroll");g.cancelBubble=!0}:function(){};f.hastranslate3d&&b.opt.enabletranslate3d?(this.setScrollTop=function(c,g){b.doc.translate.y=c;b.doc.translate.ty=-1*c+"px";b.doc.css(f.trstyle,"translate3d("+b.doc.translate.tx+","+b.doc.translate.ty+",0px)");g||b.notifyScrollEvent(b.win[0])},this.setScrollLeft=function(c,g){b.doc.translate.x=c;b.doc.translate.tx=-1*c+
"px";b.doc.css(f.trstyle,"translate3d("+b.doc.translate.tx+","+b.doc.translate.ty+",0px)");g||b.notifyScrollEvent(b.win[0])}):(this.setScrollTop=function(c,g){b.doc.translate.y=c;b.doc.translate.ty=-1*c+"px";b.doc.css(f.trstyle,"translate("+b.doc.translate.tx+","+b.doc.translate.ty+")");g||b.notifyScrollEvent(b.win[0])},this.setScrollLeft=function(c,g){b.doc.translate.x=c;b.doc.translate.tx=-1*c+"px";b.doc.css(f.trstyle,"translate("+b.doc.translate.tx+","+b.doc.translate.ty+")");g||b.notifyScrollEvent(b.win[0])})}else this.getScrollTop=
function(){return b.docscroll.scrollTop()},this.setScrollTop=function(c){return b.docscroll.scrollTop(c)},this.getScrollLeft=function(){return b.docscroll.scrollLeft()},this.setScrollLeft=function(c){return b.docscroll.scrollLeft(c)};this.getTarget=function(b){return!b?!1:b.target?b.target:b.srcElement?b.srcElement:!1};this.hasParent=function(b,g){if(!b)return!1;for(var d=b.target||b.srcElement||b||!1;d&&d.id!=g;)d=d.parentNode||!1;return!1!==d};var B={thin:1,medium:3,thick:5};this.getOffset=function(){if(b.isfixed)return{top:parseFloat(b.win.css("top")),
left:parseFloat(b.win.css("left"))};if(!b.viewport)return b.win.offset();var c=b.win.offset(),g=b.viewport.offset();return{top:c.top-g.top+b.viewport.scrollTop(),left:c.left-g.left+b.viewport.scrollLeft()}};this.updateScrollBar=function(c){if(b.ishwscroll)b.rail.css({height:b.win.innerHeight()}),b.railh&&b.railh.css({width:b.win.innerWidth()});else{var g=b.getOffset(),d=g.top,e=g.left,d=d+s(b.win,"border-top-width",!0);b.win.outerWidth();b.win.innerWidth();var e=e+(b.rail.align?b.win.outerWidth()-
s(b.win,"border-right-width")-b.rail.width:s(b.win,"border-left-width")),f=b.opt.railoffset;f&&(f.top&&(d+=f.top),b.rail.align&&f.left&&(e+=f.left));b.locked||b.rail.css({top:d,left:e,height:c?c.h:b.win.innerHeight()});b.zoom&&b.zoom.css({top:d+1,left:1==b.rail.align?e-20:e+b.rail.width+4});b.railh&&!b.locked&&(d=g.top,e=g.left,c=b.railh.align?d+s(b.win,"border-top-width",!0)+b.win.innerHeight()-b.railh.height:d+s(b.win,"border-top-width",!0),e+=s(b.win,"border-left-width"),b.railh.css({top:c,left:e,
width:b.railh.width}))}};this.doRailClick=function(c,g,d){var e;b.locked||(b.cancelEvent(c),g?(g=d?b.doScrollLeft:b.doScrollTop,e=d?(c.pageX-b.railh.offset().left-b.cursorwidth/2)*b.scrollratio.x:(c.pageY-b.rail.offset().top-b.cursorheight/2)*b.scrollratio.y,g(e)):(g=d?b.doScrollLeftBy:b.doScrollBy,e=d?b.scroll.x:b.scroll.y,c=d?c.pageX-b.railh.offset().left:c.pageY-b.rail.offset().top,d=d?b.view.w:b.view.h,e>=c?g(d):g(-d)))};b.hasanimationframe=w;b.hascancelanimationframe=x;b.hasanimationframe?b.hascancelanimationframe||
(x=function(){b.cancelAnimationFrame=!0}):(w=function(b){return setTimeout(b,15-Math.floor(+new Date/1E3)%16)},x=clearInterval);this.init=function(){b.saved.css=[];if(f.isie7mobile)return!0;f.hasmstouch&&b.css(b.ispage?e("html"):b.win,{"-ms-touch-action":"none"});b.zindex="auto";b.zindex=!b.ispage&&"auto"==b.opt.zindex?k()||"auto":b.opt.zindex;!b.ispage&&"auto"!=b.zindex&&b.zindex>C&&(C=b.zindex);b.isie&&(0==b.zindex&&"auto"==b.opt.zindex)&&(b.zindex="auto");if(!b.ispage||!f.cantouch&&!f.isieold&&
!f.isie9mobile){var c=b.docscroll;b.ispage&&(c=b.haswrapper?b.win:b.doc);f.isie9mobile||b.css(c,{"overflow-y":"hidden"});b.ispage&&f.isie7&&("BODY"==b.doc[0].nodeName?b.css(e("html"),{"overflow-y":"hidden"}):"HTML"==b.doc[0].nodeName&&b.css(e("body"),{"overflow-y":"hidden"}));f.isios&&(!b.ispage&&!b.haswrapper)&&b.css(e("body"),{"-webkit-overflow-scrolling":"touch"});var g=e(document.createElement("div"));g.css({position:"relative",top:0,"float":"right",width:b.opt.cursorwidth,height:"0px","background-color":b.opt.cursorcolor,
border:b.opt.cursorborder,"background-clip":"padding-box","-webkit-border-radius":b.opt.cursorborderradius,"-moz-border-radius":b.opt.cursorborderradius,"border-radius":b.opt.cursorborderradius});g.hborder=parseFloat(g.outerHeight()-g.innerHeight());b.cursor=g;var d=e(document.createElement("div"));d.attr("id",b.id);d.addClass("nicescroll-rails");var h,j,y=["left","right"],s;for(s in y)j=y[s],(h=b.opt.railpadding[j])?d.css("padding-"+j,h+"px"):b.opt.railpadding[j]=0;d.append(g);d.width=Math.max(parseFloat(b.opt.cursorwidth),
g.outerWidth())+b.opt.railpadding.left+b.opt.railpadding.right;d.css({width:d.width+"px",zIndex:b.zindex,background:b.opt.background,cursor:"default"});d.visibility=!0;d.scrollable=!0;d.align="left"==b.opt.railalign?0:1;b.rail=d;g=b.rail.drag=!1;b.opt.boxzoom&&(!b.ispage&&!f.isieold)&&(g=document.createElement("div"),b.bind(g,"click",b.doZoom),b.zoom=e(g),b.zoom.css({cursor:"pointer","z-index":b.zindex,backgroundImage:"url("+J+"zoomico.png)",height:18,width:18,backgroundPosition:"0px 0px"}),b.opt.dblclickzoom&&
b.bind(b.win,"dblclick",b.doZoom),f.cantouch&&b.opt.gesturezoom&&(b.ongesturezoom=function(c){1.5<c.scale&&b.doZoomIn(c);0.8>c.scale&&b.doZoomOut(c);return b.cancelEvent(c)},b.bind(b.win,"gestureend",b.ongesturezoom)));b.railh=!1;if(b.opt.horizrailenabled){b.css(c,{"overflow-x":"hidden"});g=e(document.createElement("div"));g.css({position:"relative",top:0,height:b.opt.cursorwidth,width:"0px","background-color":b.opt.cursorcolor,border:b.opt.cursorborder,"background-clip":"padding-box","-webkit-border-radius":b.opt.cursorborderradius,
"-moz-border-radius":b.opt.cursorborderradius,"border-radius":b.opt.cursorborderradius});g.wborder=parseFloat(g.outerWidth()-g.innerWidth());b.cursorh=g;var l=e(document.createElement("div"));l.attr("id",b.id+"-hr");l.addClass("nicescroll-rails");l.height=Math.max(parseFloat(b.opt.cursorwidth),g.outerHeight());l.css({height:l.height+"px",zIndex:b.zindex,background:b.opt.background});l.append(g);l.visibility=!0;l.scrollable=!0;l.align="top"==b.opt.railvalign?0:1;b.railh=l;b.railh.drag=!1}b.ispage?
(d.css({position:"fixed",top:"0px",height:"100%"}),d.align?d.css({right:"0px"}):d.css({left:"0px"}),b.body.append(d),b.railh&&(l.css({position:"fixed",left:"0px",width:"100%"}),l.align?l.css({bottom:"0px"}):l.css({top:"0px"}),b.body.append(l))):(b.ishwscroll?("static"==b.win.css("position")&&b.css(b.win,{position:"relative"}),c="HTML"==b.win[0].nodeName?b.body:b.win,b.zoom&&(b.zoom.css({position:"absolute",top:1,right:0,"margin-right":d.width+4}),c.append(b.zoom)),d.css({position:"absolute",top:0}),
d.align?d.css({right:0}):d.css({left:0}),c.append(d),l&&(l.css({position:"absolute",left:0,bottom:0}),l.align?l.css({bottom:0}):l.css({top:0}),c.append(l))):(b.isfixed="fixed"==b.win.css("position"),c=b.isfixed?"fixed":"absolute",b.isfixed||(b.viewport=b.getViewport(b.win[0])),b.viewport&&(b.body=b.viewport,!1==/relative|absolute/.test(b.viewport.css("position"))&&b.css(b.viewport,{position:"relative"})),d.css({position:c}),b.zoom&&b.zoom.css({position:c}),b.updateScrollBar(),b.body.append(d),b.zoom&&
b.body.append(b.zoom),b.railh&&(l.css({position:c}),b.body.append(l))),f.isios&&b.css(b.win,{"-webkit-tap-highlight-color":"rgba(0,0,0,0)","-webkit-touch-callout":"none"}),f.isie&&b.opt.disableoutline&&b.win.attr("hideFocus","true"),f.iswebkit&&b.opt.disableoutline&&b.win.css({outline:"none"}));!1===b.opt.autohidemode?(b.autohidedom=!1,b.rail.css({opacity:b.opt.cursoropacitymax}),b.railh&&b.railh.css({opacity:b.opt.cursoropacitymax})):!0===b.opt.autohidemode?(b.autohidedom=e().add(b.rail),f.isie8&&
(b.autohidedom=b.autohidedom.add(b.cursor)),b.railh&&(b.autohidedom=b.autohidedom.add(b.railh)),b.railh&&f.isie8&&(b.autohidedom=b.autohidedom.add(b.cursorh))):"scroll"==b.opt.autohidemode?(b.autohidedom=e().add(b.rail),b.railh&&(b.autohidedom=b.autohidedom.add(b.railh))):"cursor"==b.opt.autohidemode?(b.autohidedom=e().add(b.cursor),b.railh&&(b.autohidedom=b.autohidedom.add(b.cursorh))):"hidden"==b.opt.autohidemode&&(b.autohidedom=!1,b.hide(),b.locked=!1);if(f.isie9mobile)b.scrollmom=new L(b),b.onmangotouch=
function(){var c=b.getScrollTop(),d=b.getScrollLeft();if(c==b.scrollmom.lastscrolly&&d==b.scrollmom.lastscrollx)return!0;var g=c-b.mangotouch.sy,e=d-b.mangotouch.sx;if(0!=Math.round(Math.sqrt(Math.pow(e,2)+Math.pow(g,2)))){var f=0>g?-1:1,p=0>e?-1:1,h=+new Date;b.mangotouch.lazy&&clearTimeout(b.mangotouch.lazy);80<h-b.mangotouch.tm||b.mangotouch.dry!=f||b.mangotouch.drx!=p?(b.scrollmom.stop(),b.scrollmom.reset(d,c),b.mangotouch.sy=c,b.mangotouch.ly=c,b.mangotouch.sx=d,b.mangotouch.lx=d,b.mangotouch.dry=
f,b.mangotouch.drx=p,b.mangotouch.tm=h):(b.scrollmom.stop(),b.scrollmom.update(b.mangotouch.sx-e,b.mangotouch.sy-g),b.mangotouch.tm=h,g=Math.max(Math.abs(b.mangotouch.ly-c),Math.abs(b.mangotouch.lx-d)),b.mangotouch.ly=c,b.mangotouch.lx=d,2<g&&(b.mangotouch.lazy=setTimeout(function(){b.mangotouch.lazy=!1;b.mangotouch.dry=0;b.mangotouch.drx=0;b.mangotouch.tm=0;b.scrollmom.doMomentum(30)},100)))}},d=b.getScrollTop(),l=b.getScrollLeft(),b.mangotouch={sy:d,ly:d,dry:0,sx:l,lx:l,drx:0,lazy:!1,tm:0},b.bind(b.docscroll,
"scroll",b.onmangotouch);else{if(f.cantouch||b.istouchcapable||b.opt.touchbehavior||f.hasmstouch){b.scrollmom=new L(b);b.ontouchstart=function(c){if(c.pointerType&&2!=c.pointerType)return!1;if(!b.locked){if(f.hasmstouch)for(var d=c.target?c.target:!1;d;){var g=e(d).getNiceScroll();if(0<g.length&&g[0].me==b.me)break;if(0<g.length)return!1;if("DIV"==d.nodeName&&d.id==b.id)break;d=d.parentNode?d.parentNode:!1}b.cancelScroll();if((d=b.getTarget(c))&&/INPUT/i.test(d.nodeName)&&/range/i.test(d.type))return b.stopPropagation(c);
!("clientX"in c)&&"changedTouches"in c&&(c.clientX=c.changedTouches[0].clientX,c.clientY=c.changedTouches[0].clientY);b.forcescreen&&(g=c,c={original:c.original?c.original:c},c.clientX=g.screenX,c.clientY=g.screenY);b.rail.drag={x:c.clientX,y:c.clientY,sx:b.scroll.x,sy:b.scroll.y,st:b.getScrollTop(),sl:b.getScrollLeft(),pt:2,dl:!1};if(b.ispage||!b.opt.directionlockdeadzone)b.rail.drag.dl="f";else{var g=e(window).width(),p=e(window).height(),h=Math.max(document.body.scrollWidth,document.documentElement.scrollWidth),
k=Math.max(document.body.scrollHeight,document.documentElement.scrollHeight),p=Math.max(0,k-p),g=Math.max(0,h-g);b.rail.drag.ck=!b.rail.scrollable&&b.railh.scrollable?0<p?"v":!1:b.rail.scrollable&&!b.railh.scrollable?0<g?"h":!1:!1;b.rail.drag.ck||(b.rail.drag.dl="f")}b.opt.touchbehavior&&(b.isiframe&&f.isie)&&(g=b.win.position(),b.rail.drag.x+=g.left,b.rail.drag.y+=g.top);b.hasmoving=!1;b.lastmouseup=!1;b.scrollmom.reset(c.clientX,c.clientY);if(!f.cantouch&&!this.istouchcapable&&!f.hasmstouch){if(!d||
!/INPUT|SELECT|TEXTAREA/i.test(d.nodeName))return!b.ispage&&f.hasmousecapture&&d.setCapture(),b.cancelEvent(c);/SUBMIT|CANCEL|BUTTON/i.test(e(d).attr("type"))&&(pc={tg:d,click:!1},b.preventclick=pc)}}};b.ontouchend=function(c){if(c.pointerType&&2!=c.pointerType)return!1;if(b.rail.drag&&2==b.rail.drag.pt&&(b.scrollmom.doMomentum(),b.rail.drag=!1,b.hasmoving&&(b.hasmoving=!1,b.lastmouseup=!0,b.hideCursor(),f.hasmousecapture&&document.releaseCapture(),!f.cantouch)))return b.cancelEvent(c)};var n=b.opt.touchbehavior&&
b.isiframe&&!f.hasmousecapture;b.ontouchmove=function(c,d){if(c.pointerType&&2!=c.pointerType)return!1;if(b.rail.drag&&2==b.rail.drag.pt){if(f.cantouch&&"undefined"==typeof c.original)return!0;b.hasmoving=!0;b.preventclick&&!b.preventclick.click&&(b.preventclick.click=b.preventclick.tg.onclick||!1,b.preventclick.tg.onclick=b.onpreventclick);c=e.extend({original:c},c);"changedTouches"in c&&(c.clientX=c.changedTouches[0].clientX,c.clientY=c.changedTouches[0].clientY);if(b.forcescreen){var g=c;c={original:c.original?
c.original:c};c.clientX=g.screenX;c.clientY=g.screenY}g=ofy=0;if(n&&!d){var p=b.win.position(),g=-p.left;ofy=-p.top}var h=c.clientY+ofy,p=h-b.rail.drag.y,k=c.clientX+g,j=k-b.rail.drag.x,t=b.rail.drag.st-p;b.ishwscroll&&b.opt.bouncescroll?0>t?t=Math.round(t/2):t>b.page.maxh&&(t=b.page.maxh+Math.round((t-b.page.maxh)/2)):(0>t&&(h=t=0),t>b.page.maxh&&(t=b.page.maxh,h=0));if(b.railh&&b.railh.scrollable){var l=b.rail.drag.sl-j;b.ishwscroll&&b.opt.bouncescroll?0>l?l=Math.round(l/2):l>b.page.maxw&&(l=b.page.maxw+
Math.round((l-b.page.maxw)/2)):(0>l&&(k=l=0),l>b.page.maxw&&(l=b.page.maxw,k=0))}g=!1;if(b.rail.drag.dl)g=!0,"v"==b.rail.drag.dl?l=b.rail.drag.sl:"h"==b.rail.drag.dl&&(t=b.rail.drag.st);else{var p=Math.abs(p),j=Math.abs(j),y=b.opt.directionlockdeadzone;if("v"==b.rail.drag.ck){if(p>y&&j<=0.3*p)return b.rail.drag=!1,!0;j>y&&(b.rail.drag.dl="f",e("body").scrollTop(e("body").scrollTop()))}else if("h"==b.rail.drag.ck){if(j>y&&p<=0.3*az)return b.rail.drag=!1,!0;p>y&&(b.rail.drag.dl="f",e("body").scrollLeft(e("body").scrollLeft()))}}b.synched("touchmove",
function(){b.rail.drag&&2==b.rail.drag.pt&&(b.prepareTransition&&b.prepareTransition(0),b.rail.scrollable&&b.setScrollTop(t),b.scrollmom.update(k,h),b.railh&&b.railh.scrollable?(b.setScrollLeft(l),b.showCursor(t,l)):b.showCursor(t),f.isie10&&document.selection.clear())});f.ischrome&&b.istouchcapable&&(g=!1);if(g)return b.cancelEvent(c)}}}b.onmousedown=function(c,d){if(!(b.rail.drag&&1!=b.rail.drag.pt)){if(b.locked)return b.cancelEvent(c);b.cancelScroll();b.rail.drag={x:c.clientX,y:c.clientY,sx:b.scroll.x,
sy:b.scroll.y,pt:1,hr:!!d};var g=b.getTarget(c);!b.ispage&&f.hasmousecapture&&g.setCapture();b.isiframe&&!f.hasmousecapture&&(b.saved.csspointerevents=b.doc.css("pointer-events"),b.css(b.doc,{"pointer-events":"none"}));return b.cancelEvent(c)}};b.onmouseup=function(c){if(b.rail.drag&&(f.hasmousecapture&&document.releaseCapture(),b.isiframe&&!f.hasmousecapture&&b.doc.css("pointer-events",b.saved.csspointerevents),1==b.rail.drag.pt))return b.rail.drag=!1,b.cancelEvent(c)};b.onmousemove=function(c){if(b.rail.drag&&
1==b.rail.drag.pt){if(f.ischrome&&0==c.which)return b.onmouseup(c);b.cursorfreezed=!0;if(b.rail.drag.hr){b.scroll.x=b.rail.drag.sx+(c.clientX-b.rail.drag.x);0>b.scroll.x&&(b.scroll.x=0);var d=b.scrollvaluemaxw;b.scroll.x>d&&(b.scroll.x=d)}else b.scroll.y=b.rail.drag.sy+(c.clientY-b.rail.drag.y),0>b.scroll.y&&(b.scroll.y=0),d=b.scrollvaluemax,b.scroll.y>d&&(b.scroll.y=d);b.synched("mousemove",function(){b.rail.drag&&1==b.rail.drag.pt&&(b.showCursor(),b.rail.drag.hr?b.doScrollLeft(Math.round(b.scroll.x*
b.scrollratio.x),b.opt.cursordragspeed):b.doScrollTop(Math.round(b.scroll.y*b.scrollratio.y),b.opt.cursordragspeed))});return b.cancelEvent(c)}};if(f.cantouch||b.opt.touchbehavior)b.onpreventclick=function(c){if(b.preventclick)return b.preventclick.tg.onclick=b.preventclick.click,b.preventclick=!1,b.cancelEvent(c)},b.bind(b.win,"mousedown",b.ontouchstart),b.onclick=f.isios?!1:function(c){return b.lastmouseup?(b.lastmouseup=!1,b.cancelEvent(c)):!0},b.opt.grabcursorenabled&&f.cursorgrabvalue&&(b.css(b.ispage?
b.doc:b.win,{cursor:f.cursorgrabvalue}),b.css(b.rail,{cursor:f.cursorgrabvalue}));else{var m=function(c){if(b.selectiondrag){if(c){var d=b.win.outerHeight();c=c.pageY-b.selectiondrag.top;0<c&&c<d&&(c=0);c>=d&&(c-=d);b.selectiondrag.df=c}0!=b.selectiondrag.df&&(b.doScrollBy(2*-Math.floor(b.selectiondrag.df/6)),b.debounced("doselectionscroll",function(){m()},50))}};b.hasTextSelected="getSelection"in document?function(){return 0<document.getSelection().rangeCount}:"selection"in document?function(){return"None"!=
document.selection.type}:function(){return!1};b.onselectionstart=function(){b.ispage||(b.selectiondrag=b.win.offset())};b.onselectionend=function(){b.selectiondrag=!1};b.onselectiondrag=function(c){b.selectiondrag&&b.hasTextSelected()&&b.debounced("selectionscroll",function(){m(c)},250)}}f.hasmstouch&&(b.css(b.rail,{"-ms-touch-action":"none"}),b.css(b.cursor,{"-ms-touch-action":"none"}),b.bind(b.win,"MSPointerDown",b.ontouchstart),b.bind(document,"MSPointerUp",b.ontouchend),b.bind(document,"MSPointerMove",
b.ontouchmove),b.bind(b.cursor,"MSGestureHold",function(b){b.preventDefault()}),b.bind(b.cursor,"contextmenu",function(b){b.preventDefault()}));this.istouchcapable&&(b.bind(b.win,"touchstart",b.ontouchstart),b.bind(document,"touchend",b.ontouchend),b.bind(document,"touchcancel",b.ontouchend),b.bind(document,"touchmove",b.ontouchmove));b.bind(b.cursor,"mousedown",b.onmousedown);b.bind(b.cursor,"mouseup",b.onmouseup);b.railh&&(b.bind(b.cursorh,"mousedown",function(c){b.onmousedown(c,!0)}),b.bind(b.cursorh,
"mouseup",function(c){if(!(b.rail.drag&&2==b.rail.drag.pt))return b.rail.drag=!1,b.hasmoving=!1,b.hideCursor(),f.hasmousecapture&&document.releaseCapture(),b.cancelEvent(c)}));if(b.opt.cursordragontouch||!f.cantouch&&!b.opt.touchbehavior)b.rail.css({cursor:"default"}),b.railh&&b.railh.css({cursor:"default"}),b.jqbind(b.rail,"mouseenter",function(){b.canshowonmouseevent&&b.showCursor();b.rail.active=!0}),b.jqbind(b.rail,"mouseleave",function(){b.rail.active=!1;b.rail.drag||b.hideCursor()}),b.opt.sensitiverail&&
(b.bind(b.rail,"click",function(c){b.doRailClick(c,!1,!1)}),b.bind(b.rail,"dblclick",function(c){b.doRailClick(c,!0,!1)}),b.bind(b.cursor,"click",function(c){b.cancelEvent(c)}),b.bind(b.cursor,"dblclick",function(c){b.cancelEvent(c)})),b.railh&&(b.jqbind(b.railh,"mouseenter",function(){b.canshowonmouseevent&&b.showCursor();b.rail.active=!0}),b.jqbind(b.railh,"mouseleave",function(){b.rail.active=!1;b.rail.drag||b.hideCursor()}),b.opt.sensitiverail&&(b.bind(b.railh,"click",function(c){b.doRailClick(c,
!1,!0)}),b.bind(b.railh,"dblclick",function(c){b.doRailClick(c,!0,!0)}),b.bind(b.cursorh,"click",function(c){b.cancelEvent(c)}),b.bind(b.cursorh,"dblclick",function(c){b.cancelEvent(c)})));!f.cantouch&&!b.opt.touchbehavior?(b.bind(f.hasmousecapture?b.win:document,"mouseup",b.onmouseup),b.bind(document,"mousemove",b.onmousemove),b.onclick&&b.bind(document,"click",b.onclick),!b.ispage&&b.opt.enablescrollonselection&&(b.bind(b.win[0],"mousedown",b.onselectionstart),b.bind(document,"mouseup",b.onselectionend),
b.bind(b.cursor,"mouseup",b.onselectionend),b.cursorh&&b.bind(b.cursorh,"mouseup",b.onselectionend),b.bind(document,"mousemove",b.onselectiondrag)),b.zoom&&(b.jqbind(b.zoom,"mouseenter",function(){b.canshowonmouseevent&&b.showCursor();b.rail.active=!0}),b.jqbind(b.zoom,"mouseleave",function(){b.rail.active=!1;b.rail.drag||b.hideCursor()}))):(b.bind(f.hasmousecapture?b.win:document,"mouseup",b.ontouchend),b.bind(document,"mousemove",b.ontouchmove),b.onclick&&b.bind(document,"click",b.onclick),b.opt.cursordragontouch&&
(b.bind(b.cursor,"mousedown",b.onmousedown),b.bind(b.cursor,"mousemove",b.onmousemove),b.cursorh&&b.bind(b.cursorh,"mousedown",b.onmousedown),b.cursorh&&b.bind(b.cursorh,"mousemove",b.onmousemove)));b.opt.enablemousewheel&&(b.isiframe||b.bind(f.isie&&b.ispage?document:b.docscroll,"mousewheel",b.onmousewheel),b.bind(b.rail,"mousewheel",b.onmousewheel),b.railh&&b.bind(b.railh,"mousewheel",b.onmousewheelhr));!b.ispage&&(!f.cantouch&&!/HTML|BODY/.test(b.win[0].nodeName))&&(b.win.attr("tabindex")||b.win.attr({tabindex:N++}),
b.jqbind(b.win,"focus",function(c){D=b.getTarget(c).id||!0;b.hasfocus=!0;b.canshowonmouseevent&&b.noticeCursor()}),b.jqbind(b.win,"blur",function(){D=!1;b.hasfocus=!1}),b.jqbind(b.win,"mouseenter",function(c){G=b.getTarget(c).id||!0;b.hasmousefocus=!0;b.canshowonmouseevent&&b.noticeCursor()}),b.jqbind(b.win,"mouseleave",function(){G=!1;b.hasmousefocus=!1}))}b.onkeypress=function(c){if(b.locked&&0==b.page.maxh)return!0;c=c?c:window.e;var d=b.getTarget(c);if(d&&/INPUT|TEXTAREA|SELECT|OPTION/.test(d.nodeName)&&
(!d.getAttribute("type")&&!d.type||!/submit|button|cancel/i.tp))return!0;if(b.hasfocus||b.hasmousefocus&&!D||b.ispage&&!D&&!G){d=c.keyCode;if(b.locked&&27!=d)return b.cancelEvent(c);var g=c.ctrlKey||!1,e=c.shiftKey||!1,f=!1;switch(d){case 38:case 63233:b.doScrollBy(72);f=!0;break;case 40:case 63235:b.doScrollBy(-72);f=!0;break;case 37:case 63232:b.railh&&(g?b.doScrollLeft(0):b.doScrollLeftBy(72),f=!0);break;case 39:case 63234:b.railh&&(g?b.doScrollLeft(b.page.maxw):b.doScrollLeftBy(-72),f=!0);break;
case 33:case 63276:b.doScrollBy(b.view.h);f=!0;break;case 34:case 63277:b.doScrollBy(-b.view.h);f=!0;break;case 36:case 63273:b.railh&&g?b.doScrollPos(0,0):b.doScrollTo(0);f=!0;break;case 35:case 63275:b.railh&&g?b.doScrollPos(b.page.maxw,b.page.maxh):b.doScrollTo(b.page.maxh);f=!0;break;case 32:b.opt.spacebarenabled&&(e?b.doScrollBy(b.view.h):b.doScrollBy(-b.view.h),f=!0);break;case 27:b.zoomactive&&(b.doZoom(),f=!0)}if(f)return b.cancelEvent(c)}};b.opt.enablekeyboard&&b.bind(document,f.isopera&&
!f.isopera12?"keypress":"keydown",b.onkeypress);b.bind(window,"resize",b.lazyResize);b.bind(window,"orientationchange",b.lazyResize);b.bind(window,"load",b.lazyResize);if(f.ischrome&&!b.ispage&&!b.haswrapper){var r=b.win.attr("style"),d=parseFloat(b.win.css("width"))+1;b.win.css("width",d);b.synched("chromefix",function(){b.win.attr("style",r)})}b.onAttributeChange=function(){b.lazyResize(250)};!b.ispage&&!b.haswrapper&&(!1!==E?(b.observer=new E(function(c){c.forEach(b.onAttributeChange)}),b.observer.observe(b.win[0],
{childList:!0,characterData:!1,attributes:!0,subtree:!1}),b.observerremover=new E(function(c){c.forEach(function(c){if(0<c.removedNodes.length)for(var d in c.removedNodes)if(c.removedNodes[d]==b.win[0])return b.remove()})}),b.observerremover.observe(b.win[0].parentNode,{childList:!0,characterData:!1,attributes:!1,subtree:!1})):(b.bind(b.win,f.isie&&!f.isie9?"propertychange":"DOMAttrModified",b.onAttributeChange),f.isie9&&b.win[0].attachEvent("onpropertychange",b.onAttributeChange),b.bind(b.win,"DOMNodeRemoved",
function(c){c.target==b.win[0]&&b.remove()})));!b.ispage&&b.opt.boxzoom&&b.bind(window,"resize",b.resizeZoom);b.istextarea&&b.bind(b.win,"mouseup",b.lazyResize);b.checkrtlmode=!0;b.lazyResize(30)}if("IFRAME"==this.doc[0].nodeName){var q=function(){b.iframexd=!1;try{var c="contentDocument"in this?this.contentDocument:this.contentWindow.document}catch(d){b.iframexd=!0,c=!1}if(b.iframexd)return"console"in window&&console.log("NiceScroll error: policy restriced iframe"),!0;b.forcescreen=!0;b.isiframe&&
(b.iframe={doc:e(c),html:b.doc.contents().find("html")[0],body:b.doc.contents().find("body")[0]},b.getContentSize=function(){return{w:Math.max(b.iframe.html.scrollWidth,b.iframe.body.scrollWidth),h:Math.max(b.iframe.html.scrollHeight,b.iframe.body.scrollHeight)}},b.docscroll=e(b.iframe.body));if(!f.isios&&b.opt.iframeautoresize&&!b.isiframe){b.win.scrollTop(0);b.doc.height("");var g=Math.max(c.getElementsByTagName("html")[0].scrollHeight,c.body.scrollHeight);b.doc.height(g)}b.lazyResize(30);f.isie7&&
b.css(e(b.iframe.html),{"overflow-y":"hidden"});b.css(e(b.iframe.body),{"overflow-y":"hidden"});"contentWindow"in this?b.bind(this.contentWindow,"scroll",b.onscroll):b.bind(c,"scroll",b.onscroll);b.opt.enablemousewheel&&b.bind(c,"mousewheel",b.onmousewheel);b.opt.enablekeyboard&&b.bind(c,f.isopera?"keypress":"keydown",b.onkeypress);if(f.cantouch||b.opt.touchbehavior)b.bind(c,"mousedown",b.onmousedown),b.bind(c,"mousemove",function(c){b.onmousemove(c,!0)}),b.opt.grabcursorenabled&&f.cursorgrabvalue&&
b.css(e(c.body),{cursor:f.cursorgrabvalue});b.bind(c,"mouseup",b.onmouseup);b.zoom&&(b.opt.dblclickzoom&&b.bind(c,"dblclick",b.doZoom),b.ongesturezoom&&b.bind(c,"gestureend",b.ongesturezoom))};this.doc[0].readyState&&"complete"==this.doc[0].readyState&&setTimeout(function(){q.call(b.doc[0],!1)},500);b.bind(this.doc,"load",q)}};this.showCursor=function(c,d){b.cursortimeout&&(clearTimeout(b.cursortimeout),b.cursortimeout=0);if(b.rail){b.autohidedom&&(b.autohidedom.stop().css({opacity:b.opt.cursoropacitymax}),
b.cursoractive=!0);if(!b.rail.drag||1!=b.rail.drag.pt)"undefined"!=typeof c&&!1!==c&&(b.scroll.y=Math.round(1*c/b.scrollratio.y)),"undefined"!=typeof d&&(b.scroll.x=Math.round(1*d/b.scrollratio.x));b.cursor.css({height:b.cursorheight,top:b.scroll.y});b.cursorh&&(!b.rail.align&&b.rail.visibility?b.cursorh.css({width:b.cursorwidth,left:b.scroll.x+b.rail.width}):b.cursorh.css({width:b.cursorwidth,left:b.scroll.x}),b.cursoractive=!0);b.zoom&&b.zoom.stop().css({opacity:b.opt.cursoropacitymax})}};this.hideCursor=
function(c){!b.cursortimeout&&(b.rail&&b.autohidedom)&&(b.cursortimeout=setTimeout(function(){if(!b.rail.active||!b.showonmouseevent)b.autohidedom.stop().animate({opacity:b.opt.cursoropacitymin}),b.zoom&&b.zoom.stop().animate({opacity:b.opt.cursoropacitymin}),b.cursoractive=!1;b.cursortimeout=0},c||b.opt.hidecursordelay))};this.noticeCursor=function(c,d,e){b.showCursor(d,e);b.rail.active||b.hideCursor(c)};this.getContentSize=b.ispage?function(){return{w:Math.max(document.body.scrollWidth,document.documentElement.scrollWidth),
h:Math.max(document.body.scrollHeight,document.documentElement.scrollHeight)}}:b.haswrapper?function(){return{w:b.doc.outerWidth()+parseInt(b.win.css("paddingLeft"))+parseInt(b.win.css("paddingRight")),h:b.doc.outerHeight()+parseInt(b.win.css("paddingTop"))+parseInt(b.win.css("paddingBottom"))}}:function(){return{w:b.docscroll[0].scrollWidth,h:b.docscroll[0].scrollHeight}};this.onResize=function(c,d){if(!b.win)return!1;if(!b.haswrapper&&!b.ispage){if("none"==b.win.css("display"))return b.visibility&&
b.hideRail().hideRailHr(),!1;!b.hidden&&!b.visibility&&b.showRail().showRailHr()}var e=b.page.maxh,f=b.page.maxw,h=b.view.w;b.view={w:b.ispage?b.win.width():parseInt(b.win[0].clientWidth),h:b.ispage?b.win.height():parseInt(b.win[0].clientHeight)};b.page=d?d:b.getContentSize();b.page.maxh=Math.max(0,b.page.h-b.view.h);b.page.maxw=Math.max(0,b.page.w-b.view.w);if(b.page.maxh==e&&b.page.maxw==f&&b.view.w==h){if(b.ispage)return b;e=b.win.offset();if(b.lastposition&&(f=b.lastposition,f.top==e.top&&f.left==
e.left))return b;b.lastposition=e}0==b.page.maxh?(b.hideRail(),b.scrollvaluemax=0,b.scroll.y=0,b.scrollratio.y=0,b.cursorheight=0,b.setScrollTop(0),b.rail.scrollable=!1):b.rail.scrollable=!0;0==b.page.maxw?(b.hideRailHr(),b.scrollvaluemaxw=0,b.scroll.x=0,b.scrollratio.x=0,b.cursorwidth=0,b.setScrollLeft(0),b.railh.scrollable=!1):b.railh.scrollable=!0;b.locked=0==b.page.maxh&&0==b.page.maxw;if(b.locked)return b.ispage||b.updateScrollBar(b.view),!1;!b.hidden&&!b.visibility?b.showRail().showRailHr():
!b.hidden&&!b.railh.visibility&&b.showRailHr();b.istextarea&&(b.win.css("resize")&&"none"!=b.win.css("resize"))&&(b.view.h-=20);b.cursorheight=Math.min(b.view.h,Math.round(b.view.h*(b.view.h/b.page.h)));b.cursorheight=b.opt.cursorfixedheight?b.opt.cursorfixedheight:Math.max(b.opt.cursorminheight,b.cursorheight);b.cursorwidth=Math.min(b.view.w,Math.round(b.view.w*(b.view.w/b.page.w)));b.cursorwidth=b.opt.cursorfixedheight?b.opt.cursorfixedheight:Math.max(b.opt.cursorminheight,b.cursorwidth);b.scrollvaluemax=
b.view.h-b.cursorheight-b.cursor.hborder;b.railh&&(b.railh.width=0<b.page.maxh?b.view.w-b.rail.width:b.view.w,b.scrollvaluemaxw=b.railh.width-b.cursorwidth-b.cursorh.wborder);b.checkrtlmode&&b.railh&&(b.checkrtlmode=!1,b.opt.rtlmode&&0==b.scroll.x&&b.setScrollLeft(b.page.maxw));b.ispage||b.updateScrollBar(b.view);b.scrollratio={x:b.page.maxw/b.scrollvaluemaxw,y:b.page.maxh/b.scrollvaluemax};b.getScrollTop()>b.page.maxh?b.doScrollTop(b.page.maxh):(b.scroll.y=Math.round(b.getScrollTop()*(1/b.scrollratio.y)),
b.scroll.x=Math.round(b.getScrollLeft()*(1/b.scrollratio.x)),b.cursoractive&&b.noticeCursor());b.scroll.y&&0==b.getScrollTop()&&b.doScrollTo(Math.floor(b.scroll.y*b.scrollratio.y));return b};this.resize=b.onResize;this.lazyResize=function(c){c=isNaN(c)?30:c;b.delayed("resize",b.resize,c);return b};this._bind=function(c,d,e,f){b.events.push({e:c,n:d,f:e,b:f,q:!1});c.addEventListener?c.addEventListener(d,e,f||!1):c.attachEvent?c.attachEvent("on"+d,e):c["on"+d]=e};this.jqbind=function(c,d,f){b.events.push({e:c,
n:d,f:f,q:!0});e(c).bind(d,f)};this.bind=function(c,d,e,h){var k="jquery"in c?c[0]:c;"mousewheel"==d?"onwheel"in b.win?b._bind(k,"wheel",e,h||!1):(c="undefined"!=typeof document.onmousewheel?"mousewheel":"DOMMouseScroll",n(k,c,e,h||!1),"DOMMouseScroll"==c&&n(k,"MozMousePixelScroll",e,h||!1)):k.addEventListener?(f.cantouch&&/mouseup|mousedown|mousemove/.test(d)&&b._bind(k,"mousedown"==d?"touchstart":"mouseup"==d?"touchend":"touchmove",function(b){if(b.touches){if(2>b.touches.length){var c=b.touches.length?
b.touches[0]:b;c.original=b;e.call(this,c)}}else b.changedTouches&&(c=b.changedTouches[0],c.original=b,e.call(this,c))},h||!1),b._bind(k,d,e,h||!1),f.cantouch&&"mouseup"==d&&b._bind(k,"touchcancel",e,h||!1)):b._bind(k,d,function(c){if((c=c||window.event||!1)&&c.srcElement)c.target=c.srcElement;"pageY"in c||(c.pageX=c.clientX+document.documentElement.scrollLeft,c.pageY=c.clientY+document.documentElement.scrollTop);return!1===e.call(k,c)||!1===h?b.cancelEvent(c):!0})};this._unbind=function(b,d,e,f){b.removeEventListener?
b.removeEventListener(d,e,f):b.detachEvent?b.detachEvent("on"+d,e):b["on"+d]=!1};this.unbindAll=function(){for(var c=0;c<b.events.length;c++){var d=b.events[c];d.q?d.e.unbind(d.n,d.f):b._unbind(d.e,d.n,d.f,d.b)}};this.cancelEvent=function(b){b=b.original?b.original:b?b:window.event||!1;if(!b)return!1;b.preventDefault&&b.preventDefault();b.stopPropagation&&b.stopPropagation();b.preventManipulation&&b.preventManipulation();b.cancelBubble=!0;b.cancel=!0;return b.returnValue=!1};this.stopPropagation=
function(b){b=b.original?b.original:b?b:window.event||!1;if(!b)return!1;if(b.stopPropagation)return b.stopPropagation();b.cancelBubble&&(b.cancelBubble=!0);return!1};this.showRail=function(){if(0!=b.page.maxh&&(b.ispage||"none"!=b.win.css("display")))b.visibility=!0,b.rail.visibility=!0,b.rail.css("display","block");return b};this.showRailHr=function(){if(!b.railh)return b;if(0!=b.page.maxw&&(b.ispage||"none"!=b.win.css("display")))b.railh.visibility=!0,b.railh.css("display","block");return b};this.hideRail=
function(){b.visibility=!1;b.rail.visibility=!1;b.rail.css("display","none");return b};this.hideRailHr=function(){if(!b.railh)return b;b.railh.visibility=!1;b.railh.css("display","none");return b};this.show=function(){b.hidden=!1;b.locked=!1;return b.showRail().showRailHr()};this.hide=function(){b.hidden=!0;b.locked=!0;return b.hideRail().hideRailHr()};this.toggle=function(){return b.hidden?b.show():b.hide()};this.remove=function(){b.stop();b.cursortimeout&&clearTimeout(b.cursortimeout);b.doZoomOut();
b.unbindAll();!1!==b.observer&&b.observer.disconnect();!1!==b.observerremover&&b.observerremover.disconnect();b.events=[];b.cursor&&b.cursor.remove();b.cursorh&&b.cursorh.remove();b.rail&&b.rail.remove();b.railh&&b.railh.remove();b.zoom&&b.zoom.remove();for(var c=0;c<b.saved.css.length;c++){var d=b.saved.css[c];d[0].css(d[1],"undefined"==typeof d[2]?"":d[2])}b.saved=!1;b.me.data("__nicescroll","");e.nicescroll.remove(b);for(var f in b)b[f]=null,delete b[f];b=null};this.scrollstart=function(c){this.onscrollstart=
c;return b};this.scrollend=function(c){this.onscrollend=c;return b};this.scrollcancel=function(c){this.onscrollcancel=c;return b};this.zoomin=function(c){this.onzoomin=c;return b};this.zoomout=function(c){this.onzoomout=c;return b};this.isScrollable=function(b){b=b.target?b.target:b;if("OPTION"==b.nodeName)return!0;for(;b&&1==b.nodeType&&!/BODY|HTML/.test(b.nodeName);){var d=e(b),d=d.css("overflowY")||d.css("overflowX")||d.css("overflow")||"";if(/scroll|auto/.test(d))return b.clientHeight!=b.scrollHeight;
b=b.parentNode?b.parentNode:!1}return!1};this.getViewport=function(b){for(b=b&&b.parentNode?b.parentNode:!1;b&&1==b.nodeType&&!/BODY|HTML/.test(b.nodeName);){var d=e(b),f=d.css("overflowY")||d.css("overflowX")||d.css("overflow")||"";if(/scroll|auto/.test(f)&&b.clientHeight!=b.scrollHeight||0<d.getNiceScroll().length)return d;b=b.parentNode?b.parentNode:!1}return!1};this.onmousewheel=function(c){if(b.locked)return!0;if(b.rail.drag)return b.cancelEvent(c);if(!b.rail.scrollable)return b.railh&&b.railh.scrollable?
b.onmousewheelhr(c):!0;var d=+new Date,e=!1;b.opt.preservenativescrolling&&b.checkarea+600<d&&(b.nativescrollingarea=b.isScrollable(c),e=!0);b.checkarea=d;if(b.nativescrollingarea)return!0;if(c=v(c,!1,e))b.checkarea=0;return c};this.onmousewheelhr=function(c){if(b.locked||!b.railh.scrollable)return!0;if(b.rail.drag)return b.cancelEvent(c);var d=+new Date,e=!1;b.opt.preservenativescrolling&&b.checkarea+600<d&&(b.nativescrollingarea=b.isScrollable(c),e=!0);b.checkarea=d;return b.nativescrollingarea?
!0:b.locked?b.cancelEvent(c):v(c,!0,e)};this.stop=function(){b.cancelScroll();b.scrollmon&&b.scrollmon.stop();b.cursorfreezed=!1;b.scroll.y=Math.round(b.getScrollTop()*(1/b.scrollratio.y));b.noticeCursor();return b};this.getTransitionSpeed=function(c){var d=Math.round(10*b.opt.scrollspeed);c=Math.min(d,Math.round(c/20*b.opt.scrollspeed));return 20<c?c:0};b.opt.smoothscroll?b.ishwscroll&&f.hastransition&&b.opt.usetransition?(this.prepareTransition=function(c,d){var e=d?20<c?c:0:b.getTransitionSpeed(c),
h=e?f.prefixstyle+"transform "+e+"ms ease-out":"";if(!b.lasttransitionstyle||b.lasttransitionstyle!=h)b.lasttransitionstyle=h,b.doc.css(f.transitionstyle,h);return e},this.doScrollLeft=function(c,d){var e=b.scrollrunning?b.newscrolly:b.getScrollTop();b.doScrollPos(c,e,d)},this.doScrollTop=function(c,d){var e=b.scrollrunning?b.newscrollx:b.getScrollLeft();b.doScrollPos(e,c,d)},this.doScrollPos=function(c,d,e){var h=b.getScrollTop(),k=b.getScrollLeft();(0>(b.newscrolly-h)*(d-h)||0>(b.newscrollx-k)*
(c-k))&&b.cancelScroll();!1==b.opt.bouncescroll&&(0>d?d=0:d>b.page.maxh&&(d=b.page.maxh),0>c?c=0:c>b.page.maxw&&(c=b.page.maxw));if(b.scrollrunning&&c==b.newscrollx&&d==b.newscrolly)return!1;b.newscrolly=d;b.newscrollx=c;b.newscrollspeed=e||!1;if(b.timer)return!1;b.timer=setTimeout(function(){var e=b.getScrollTop(),h=b.getScrollLeft(),k,p;k=c-h;p=d-e;k=Math.round(Math.sqrt(Math.pow(k,2)+Math.pow(p,2)));k=b.newscrollspeed&&1<b.newscrollspeed?b.newscrollspeed:b.getTransitionSpeed(k);b.newscrollspeed&&
1>=b.newscrollspeed&&(k*=b.newscrollspeed);b.prepareTransition(k,!0);b.timerscroll&&b.timerscroll.tm&&clearInterval(b.timerscroll.tm);0<k&&(!b.scrollrunning&&b.onscrollstart&&b.onscrollstart.call(b,{type:"scrollstart",current:{x:h,y:e},request:{x:c,y:d},end:{x:b.newscrollx,y:b.newscrolly},speed:k}),f.transitionend?b.scrollendtrapped||(b.scrollendtrapped=!0,b.bind(b.doc,f.transitionend,b.onScrollEnd,!1)):(b.scrollendtrapped&&clearTimeout(b.scrollendtrapped),b.scrollendtrapped=setTimeout(b.onScrollEnd,
k)),b.timerscroll={bz:new BezierClass(e,b.newscrolly,k,0,0,0.58,1),bh:new BezierClass(h,b.newscrollx,k,0,0,0.58,1)},b.cursorfreezed||(b.timerscroll.tm=setInterval(function(){b.showCursor(b.getScrollTop(),b.getScrollLeft())},60)));b.synched("doScroll-set",function(){b.timer=0;b.scrollendtrapped&&(b.scrollrunning=!0);b.setScrollTop(b.newscrolly);b.setScrollLeft(b.newscrollx);if(!b.scrollendtrapped)b.onScrollEnd()})},50)},this.cancelScroll=function(){if(!b.scrollendtrapped)return!0;var c=b.getScrollTop(),
d=b.getScrollLeft();b.scrollrunning=!1;f.transitionend||clearTimeout(f.transitionend);b.scrollendtrapped=!1;b._unbind(b.doc,f.transitionend,b.onScrollEnd);b.prepareTransition(0);b.setScrollTop(c);b.railh&&b.setScrollLeft(d);b.timerscroll&&b.timerscroll.tm&&clearInterval(b.timerscroll.tm);b.timerscroll=!1;b.cursorfreezed=!1;b.showCursor(c,d);return b},this.onScrollEnd=function(){b.scrollendtrapped&&b._unbind(b.doc,f.transitionend,b.onScrollEnd);b.scrollendtrapped=!1;b.prepareTransition(0);b.timerscroll&&
b.timerscroll.tm&&clearInterval(b.timerscroll.tm);b.timerscroll=!1;var c=b.getScrollTop(),d=b.getScrollLeft();b.setScrollTop(c);b.railh&&b.setScrollLeft(d);b.noticeCursor(!1,c,d);b.cursorfreezed=!1;0>c?c=0:c>b.page.maxh&&(c=b.page.maxh);0>d?d=0:d>b.page.maxw&&(d=b.page.maxw);if(c!=b.newscrolly||d!=b.newscrollx)return b.doScrollPos(d,c,b.opt.snapbackspeed);b.onscrollend&&b.scrollrunning&&b.onscrollend.call(b,{type:"scrollend",current:{x:d,y:c},end:{x:b.newscrollx,y:b.newscrolly}});b.scrollrunning=
!1}):(this.doScrollLeft=function(c,d){var e=b.scrollrunning?b.newscrolly:b.getScrollTop();b.doScrollPos(c,e,d)},this.doScrollTop=function(c,d){var e=b.scrollrunning?b.newscrollx:b.getScrollLeft();b.doScrollPos(e,c,d)},this.doScrollPos=function(c,d,e){function f(){if(b.cancelAnimationFrame)return!0;b.scrollrunning=!0;if(n=1-n)return b.timer=w(f)||1;var c=0,d=sy=b.getScrollTop();if(b.dst.ay){var d=b.bzscroll?b.dst.py+b.bzscroll.getNow()*b.dst.ay:b.newscrolly,e=d-sy;if(0>e&&d<b.newscrolly||0<e&&d>b.newscrolly)d=
b.newscrolly;b.setScrollTop(d);d==b.newscrolly&&(c=1)}else c=1;var g=sx=b.getScrollLeft();if(b.dst.ax){g=b.bzscroll?b.dst.px+b.bzscroll.getNow()*b.dst.ax:b.newscrollx;e=g-sx;if(0>e&&g<b.newscrollx||0<e&&g>b.newscrollx)g=b.newscrollx;b.setScrollLeft(g);g==b.newscrollx&&(c+=1)}else c+=1;2==c?(b.timer=0,b.cursorfreezed=!1,b.bzscroll=!1,b.scrollrunning=!1,0>d?d=0:d>b.page.maxh&&(d=b.page.maxh),0>g?g=0:g>b.page.maxw&&(g=b.page.maxw),g!=b.newscrollx||d!=b.newscrolly?b.doScrollPos(g,d):b.onscrollend&&b.onscrollend.call(b,
{type:"scrollend",current:{x:sx,y:sy},end:{x:b.newscrollx,y:b.newscrolly}})):b.timer=w(f)||1}d="undefined"==typeof d||!1===d?b.getScrollTop(!0):d;if(b.timer&&b.newscrolly==d&&b.newscrollx==c)return!0;b.timer&&x(b.timer);b.timer=0;var h=b.getScrollTop(),k=b.getScrollLeft();(0>(b.newscrolly-h)*(d-h)||0>(b.newscrollx-k)*(c-k))&&b.cancelScroll();b.newscrolly=d;b.newscrollx=c;if(!b.bouncescroll||!b.rail.visibility)0>b.newscrolly?b.newscrolly=0:b.newscrolly>b.page.maxh&&(b.newscrolly=b.page.maxh);if(!b.bouncescroll||
!b.railh.visibility)0>b.newscrollx?b.newscrollx=0:b.newscrollx>b.page.maxw&&(b.newscrollx=b.page.maxw);b.dst={};b.dst.x=c-k;b.dst.y=d-h;b.dst.px=k;b.dst.py=h;var j=Math.round(Math.sqrt(Math.pow(b.dst.x,2)+Math.pow(b.dst.y,2)));b.dst.ax=b.dst.x/j;b.dst.ay=b.dst.y/j;var l=0,s=j;0==b.dst.x?(l=h,s=d,b.dst.ay=1,b.dst.py=0):0==b.dst.y&&(l=k,s=c,b.dst.ax=1,b.dst.px=0);j=b.getTransitionSpeed(j);e&&1>=e&&(j*=e);b.bzscroll=0<j?b.bzscroll?b.bzscroll.update(s,j):new BezierClass(l,s,j,0,1,0,1):!1;if(!b.timer){(h==
b.page.maxh&&d>=b.page.maxh||k==b.page.maxw&&c>=b.page.maxw)&&b.checkContentSize();var n=1;b.cancelAnimationFrame=!1;b.timer=1;b.onscrollstart&&!b.scrollrunning&&b.onscrollstart.call(b,{type:"scrollstart",current:{x:k,y:h},request:{x:c,y:d},end:{x:b.newscrollx,y:b.newscrolly},speed:j});f();(h==b.page.maxh&&d>=h||k==b.page.maxw&&c>=k)&&b.checkContentSize();b.noticeCursor()}},this.cancelScroll=function(){b.timer&&x(b.timer);b.timer=0;b.bzscroll=!1;b.scrollrunning=!1;return b}):(this.doScrollLeft=function(c,
d){var e=b.getScrollTop();b.doScrollPos(c,e,d)},this.doScrollTop=function(c,d){var e=b.getScrollLeft();b.doScrollPos(e,c,d)},this.doScrollPos=function(c,d){var e=c>b.page.maxw?b.page.maxw:c;0>e&&(e=0);var f=d>b.page.maxh?b.page.maxh:d;0>f&&(f=0);b.synched("scroll",function(){b.setScrollTop(f);b.setScrollLeft(e)})},this.cancelScroll=function(){});this.doScrollBy=function(c,d){var e=0,e=d?Math.floor((b.scroll.y-c)*b.scrollratio.y):(b.timer?b.newscrolly:b.getScrollTop(!0))-c;if(b.bouncescroll){var f=
Math.round(b.view.h/2);e<-f?e=-f:e>b.page.maxh+f&&(e=b.page.maxh+f)}b.cursorfreezed=!1;py=b.getScrollTop(!0);if(0>e&&0>=py)return b.noticeCursor();if(e>b.page.maxh&&py>=b.page.maxh)return b.checkContentSize(),b.noticeCursor();b.doScrollTop(e)};this.doScrollLeftBy=function(c,d){var e=0,e=d?Math.floor((b.scroll.x-c)*b.scrollratio.x):(b.timer?b.newscrollx:b.getScrollLeft(!0))-c;if(b.bouncescroll){var f=Math.round(b.view.w/2);e<-f?e=-f:e>b.page.maxw+f&&(e=b.page.maxw+f)}b.cursorfreezed=!1;px=b.getScrollLeft(!0);
if(0>e&&0>=px||e>b.page.maxw&&px>=b.page.maxw)return b.noticeCursor();b.doScrollLeft(e)};this.doScrollTo=function(c,d){d&&Math.round(c*b.scrollratio.y);b.cursorfreezed=!1;b.doScrollTop(c)};this.checkContentSize=function(){var c=b.getContentSize();(c.h!=b.page.h||c.w!=b.page.w)&&b.resize(!1,c)};b.onscroll=function(){b.rail.drag||b.cursorfreezed||b.synched("scroll",function(){b.scroll.y=Math.round(b.getScrollTop()*(1/b.scrollratio.y));b.railh&&(b.scroll.x=Math.round(b.getScrollLeft()*(1/b.scrollratio.x)));
b.noticeCursor()})};b.bind(b.docscroll,"scroll",b.onscroll);this.doZoomIn=function(c){if(!b.zoomactive){b.zoomactive=!0;b.zoomrestore={style:{}};var d="position top left zIndex backgroundColor marginTop marginBottom marginLeft marginRight".split(" "),h=b.win[0].style,k;for(k in d){var j=d[k];b.zoomrestore.style[j]="undefined"!=typeof h[j]?h[j]:""}b.zoomrestore.style.width=b.win.css("width");b.zoomrestore.style.height=b.win.css("height");b.zoomrestore.padding={w:b.win.outerWidth()-b.win.width(),h:b.win.outerHeight()-
b.win.height()};f.isios4&&(b.zoomrestore.scrollTop=e(window).scrollTop(),e(window).scrollTop(0));b.win.css({position:f.isios4?"absolute":"fixed",top:0,left:0,"z-index":C+100,margin:"0px"});d=b.win.css("backgroundColor");(""==d||/transparent|rgba\(0, 0, 0, 0\)|rgba\(0,0,0,0\)/.test(d))&&b.win.css("backgroundColor","#fff");b.rail.css({"z-index":C+101});b.zoom.css({"z-index":C+102});b.zoom.css("backgroundPosition","0px -18px");b.resizeZoom();b.onzoomin&&b.onzoomin.call(b);return b.cancelEvent(c)}};this.doZoomOut=
function(c){if(b.zoomactive)return b.zoomactive=!1,b.win.css("margin",""),b.win.css(b.zoomrestore.style),f.isios4&&e(window).scrollTop(b.zoomrestore.scrollTop),b.rail.css({"z-index":b.zindex}),b.zoom.css({"z-index":b.zindex}),b.zoomrestore=!1,b.zoom.css("backgroundPosition","0px 0px"),b.onResize(),b.onzoomout&&b.onzoomout.call(b),b.cancelEvent(c)};this.doZoom=function(c){return b.zoomactive?b.doZoomOut(c):b.doZoomIn(c)};this.resizeZoom=function(){if(b.zoomactive){var c=b.getScrollTop();b.win.css({width:e(window).width()-
b.zoomrestore.padding.w+"px",height:e(window).height()-b.zoomrestore.padding.h+"px"});b.onResize();b.setScrollTop(Math.min(b.page.maxh,c))}};this.init();e.nicescroll.push(this)},L=function(e){var d=this;this.nc=e;this.steptime=this.lasttime=this.speedy=this.speedx=this.lasty=this.lastx=0;this.snapy=this.snapx=!1;this.demuly=this.demulx=0;this.lastscrolly=this.lastscrollx=-1;this.timer=this.chky=this.chkx=0;this.time=function(){return+new Date};this.reset=function(e,j){d.stop();var n=d.time();d.steptime=
0;d.lasttime=n;d.speedx=0;d.speedy=0;d.lastx=e;d.lasty=j;d.lastscrollx=-1;d.lastscrolly=-1};this.update=function(e,j){var n=d.time();d.steptime=n-d.lasttime;d.lasttime=n;var n=j-d.lasty,v=e-d.lastx,b=d.nc.getScrollTop(),m=d.nc.getScrollLeft(),b=b+n,m=m+v;d.snapx=0>m||m>d.nc.page.maxw;d.snapy=0>b||b>d.nc.page.maxh;d.speedx=v;d.speedy=n;d.lastx=e;d.lasty=j};this.stop=function(){d.nc.unsynched("domomentum2d");d.timer&&clearTimeout(d.timer);d.timer=0;d.lastscrollx=-1;d.lastscrolly=-1};this.doSnapy=function(e,
j){var n=!1;0>j?(j=0,n=!0):j>d.nc.page.maxh&&(j=d.nc.page.maxh,n=!0);0>e?(e=0,n=!0):e>d.nc.page.maxw&&(e=d.nc.page.maxw,n=!0);n&&d.nc.doScrollPos(e,j,d.nc.opt.snapbackspeed)};this.doMomentum=function(e){var j=d.time(),n=e?j+e:d.lasttime;e=d.nc.getScrollLeft();var v=d.nc.getScrollTop(),b=d.nc.page.maxh,m=d.nc.page.maxw;d.speedx=0<m?Math.min(60,d.speedx):0;d.speedy=0<b?Math.min(60,d.speedy):0;n=n&&50>=j-n;if(0>v||v>b||0>e||e>m)n=!1;e=d.speedx&&n?d.speedx:!1;if(d.speedy&&n&&d.speedy||e){var h=Math.max(16,
d.steptime);50<h&&(e=h/50,d.speedx*=e,d.speedy*=e,h=50);d.demulxy=0;d.lastscrollx=d.nc.getScrollLeft();d.chkx=d.lastscrollx;d.lastscrolly=d.nc.getScrollTop();d.chky=d.lastscrolly;var q=d.lastscrollx,r=d.lastscrolly,u=function(){var e=600<d.time()-j?0.04:0.02;if(d.speedx&&(q=Math.floor(d.lastscrollx-d.speedx*(1-d.demulxy)),d.lastscrollx=q,0>q||q>m))e=0.1;if(d.speedy&&(r=Math.floor(d.lastscrolly-d.speedy*(1-d.demulxy)),d.lastscrolly=r,0>r||r>b))e=0.1;d.demulxy=Math.min(1,d.demulxy+e);d.nc.synched("domomentum2d",
function(){d.speedx&&(d.nc.getScrollLeft()!=d.chkx&&d.stop(),d.chkx=q,d.nc.setScrollLeft(q));d.speedy&&(d.nc.getScrollTop()!=d.chky&&d.stop(),d.chky=r,d.nc.setScrollTop(r));d.timer||(d.nc.hideCursor(),d.doSnapy(q,r))});1>d.demulxy?d.timer=setTimeout(u,h):(d.stop(),d.nc.hideCursor(),d.doSnapy(q,r))};u()}else d.doSnapy(d.nc.getScrollLeft(),d.nc.getScrollTop())}},z=e.fn.scrollTop;e.cssHooks.pageYOffset={get:function(j){var d=e.data(j,"__nicescroll")||!1;return d&&d.ishwscroll?d.getScrollTop():z.call(j)},
set:function(j,d){var k=e.data(j,"__nicescroll")||!1;k&&k.ishwscroll?k.setScrollTop(parseInt(d)):z.call(j,d);return this}};e.fn.scrollTop=function(j){if("undefined"==typeof j){var d=this[0]?e.data(this[0],"__nicescroll")||!1:!1;return d&&d.ishwscroll?d.getScrollTop():z.call(this)}return this.each(function(){var d=e.data(this,"__nicescroll")||!1;d&&d.ishwscroll?d.setScrollTop(parseInt(j)):z.call(e(this),j)})};var B=e.fn.scrollLeft;e.cssHooks.pageXOffset={get:function(j){var d=e.data(j,"__nicescroll")||
!1;return d&&d.ishwscroll?d.getScrollLeft():B.call(j)},set:function(j,d){var k=e.data(j,"__nicescroll")||!1;k&&k.ishwscroll?k.setScrollLeft(parseInt(d)):B.call(j,d);return this}};e.fn.scrollLeft=function(j){if("undefined"==typeof j){var d=this[0]?e.data(this[0],"__nicescroll")||!1:!1;return d&&d.ishwscroll?d.getScrollLeft():B.call(this)}return this.each(function(){var d=e.data(this,"__nicescroll")||!1;d&&d.ishwscroll?d.setScrollLeft(parseInt(j)):B.call(e(this),j)})};for(var F=function(j){var d=this;
this.length=0;this.name="nicescrollarray";this.each=function(e){for(var j=0,k=0;k<d.length;k++)e.call(d[k],j++);return d};this.push=function(e){d[d.length]=e;d.length++};this.remove=function(e){d.each(function(j){this.id===e.id&&(delete d[j],d.length--)})};this.eq=function(e){return d[e]};if(j)for(a=0;a<j.length;a++){var k=e.data(j[a],"__nicescroll")||!1;k&&(this[this.length]=k,this.length++)}return this},u=F.prototype,M="show hide toggle onResize resize remove stop doScrollPos".split(" "),Q=function(e,
d){e[d]=function(){var e=arguments;return this.each(function(){this[d].apply(this,e)})}},I=0;I<M.length;I++)Q(u,M[I]);e.fn.getNiceScroll=function(j){return"undefined"==typeof j?new F(this):e.data(this[j],"__nicescroll")||!1};e.extend(e.expr[":"],{nicescroll:function(j){return e.data(j,"__nicescroll")?!0:!1}});e.fn.niceScroll=function(j,d){"undefined"==typeof d&&("object"==typeof j&&!("jquery"in j))&&(d=j,j=!1);var k=new F;"undefined"==typeof d&&(d={});j&&(d.doc=e(j),d.win=e(this));var s=!("doc"in
d);!s&&!("win"in d)&&(d.win=e(this));this.each(function(){var j=e(this).data("__nicescroll")||!1;j||(d.doc=s?e(this):d.doc,j=new P(d,e(this)),e(this).data("__nicescroll",j));k.push(j)});return 1==k.length?k[0]:k};window.NiceScroll={getjQuery:function(){return e}};e.nicescroll||(e.nicescroll=new F,e.nicescroll.options=K)})(jQuery);