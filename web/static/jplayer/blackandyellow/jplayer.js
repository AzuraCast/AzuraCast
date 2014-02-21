/**
 * @file
 * Drupal behaviors for jPlayer.
 */

(function ($) {
  
  Drupal.jPlayer = Drupal.jPlayer || {};
  
  Drupal.behaviors.jPlayer = {
    attach: function(context, settings) {
      // Set time format settings
      $.jPlayer.timeFormat.showHour = Drupal.settings.jPlayer.showHour;
      $.jPlayer.timeFormat.showMin = Drupal.settings.jPlayer.showMin;
      $.jPlayer.timeFormat.showSec = Drupal.settings.jPlayer.showSec;
      
      $.jPlayer.timeFormat.padHour = Drupal.settings.jPlayer.padHour;
      $.jPlayer.timeFormat.padMin = Drupal.settings.jPlayer.padMin;
      $.jPlayer.timeFormat.padSec = Drupal.settings.jPlayer.padSec;
      
      $.jPlayer.timeFormat.sepHour = Drupal.settings.jPlayer.sepHour;
      $.jPlayer.timeFormat.sepMin = Drupal.settings.jPlayer.sepMin;
      $.jPlayer.timeFormat.sepSec = Drupal.settings.jPlayer.sepSec;
      
      // INITIALISE
      
      $('.jp-jplayer', context).each(function() {
        var wrapper = this.parentNode;
        var player = this;
        var playerId = $(this).attr('id');
        var playerSettings = Drupal.settings.jplayerInstances[playerId];
        var type = $(this).parent().attr('class');
        player.playerType = $(this).parent().attr('class');
        
        if (type == 'jp-type-single') {
          // Initialise single player
          $(player).jPlayer({
            ready: function() {
              $(this).jPlayer("setMedia", playerSettings.files);
              
              // Make sure we pause other players on play
              $(player).bind($.jPlayer.event.play, function() {
                $(this).jPlayer("pauseOthers");
              });

              // We can't use the play event as it's fired *after* jPlayer
              // attempts to download the audio.
              $(wrapper).find('a.jp-play').click(function() {
                if (Drupal.settings.jPlayer.protected) {
                  Drupal.jPlayer.authorize(wrapper, player);
                }
              });

              // Repeat?
              if (playerSettings.repeat != 'none') {
                $(player).bind($.jPlayer.event.ended, function() {
                  $(this).jPlayer("play");
                });
              }
              
              // Autoplay?
              if (playerSettings.autoplay == true) {
                $(this).jPlayer("play");
              }
            },
            swfPath: Drupal.settings.jPlayer.swfPath,
            cssSelectorAncestor: '#'+playerId+'_interface',
            solution: playerSettings.solution,
            supplied: playerSettings.supplied,
            preload: playerSettings.preload,
            volume: playerSettings.volume,
            muted: playerSettings.muted
          });
        }
        else {
          // Initialise playlist player
          $(player).jPlayer({
            ready: function() {
              Drupal.jPlayer.setFiles(wrapper, player, 0, playerSettings.autoplay);
              
              // Pause other players on play
              $(player).bind($.jPlayer.event.play, function() {
                $(this).jPlayer("pauseOthers");
              });

              // We can't use the play event as it's fired *after* jPlayer
              // attempts to download the audio.
              $(wrapper).find('a.jp-play').click(function() {
                if (Drupal.settings.jPlayer.protected) {
                  Drupal.jPlayer.authorize(wrapper, player);
                }
              });

              // Repeat?
              if (playerSettings.repeat != 'none') {
                $(player).bind($.jPlayer.event.ended, function() {
                  if (playerSettings.repeat == 'single') {
                    $(this).jPlayer("play");
                  }
                  else {
                    Drupal.jPlayer.next(wrapper, player);
                  }
                });
              }
              
              // Add playlist selection
              $('#'+playerId+'_playlist').find('a').click(function(){
                var index = $(this).attr('id').split('_')[2];
                Drupal.jPlayer.setFiles(wrapper, player, index, true);
                $(this).blur();
                if (Drupal.settings.jPlayer.protected) {
                  Drupal.jPlayer.authorize(wrapper, player);
                }
                return false;
              });
            },
            swfPath: Drupal.settings.jPlayer.swfPath,
            cssSelectorAncestor: '#'+playerId+'_interface',
            solution: playerSettings.solution,
            supplied: playerSettings.supplied,
            preload: playerSettings.preload,
            volume: playerSettings.volume,
            muted: playerSettings.muted
          });
          
          // Next
          $(wrapper).find('.jp-next').click(function() {
            $(this).blur();
            Drupal.jPlayer.next(wrapper, player);
            return false;
          });
          
          // Previous
          $(wrapper).find('.jp-previous').click(function() {
            $(this).blur();
            Drupal.jPlayer.previous(wrapper, player);
            return false;
          });
        }
      });
    }
  };
  
  Drupal.jPlayer.setFiles = function(wrapper, player, index, play) {
    var playerId = $(player).attr('id');
    var playerSettings = Drupal.settings.jplayerInstances[playerId];
    var type = $(wrapper).parent().attr('class');
    
    $(wrapper).find('.jp-playlist-current').removeClass('jp-playlist-current');
    $('#'+playerId+'_item_'+index).parent().addClass('jp-playlist-current');
    $('#'+playerId+'_item_'+index).addClass('jp-playlist-current');
    $(player).jPlayer("setMedia", playerSettings.files[index])
    
    for (key in playerSettings.files[index]) {
      if (key != 'poster') {
        type = key;
      }
    }
    
    if (type in {'m4v':'', 'mp4':'','ogv':'','webmv':''}) {
      var kind = 'video jp-video-360p';
    }
    else if (type in {'mp3':'', 'm4a':'','oga':'','webmv':'','wav':''}) {
      var kind = 'audio';
    }
    
    if (kind == 'audio') {
      $(wrapper).find('img').remove();
    }
    
    //$(wrapper).parent().attr('class', 'jp-'+kind);
    
    if (play == true) {
      $(player).jPlayer('play');
    }
  };
  
  Drupal.jPlayer.next = function(wrapper, player) {
    var playerId = $(player).attr('id');
    var playerSettings = Drupal.settings.jplayerInstances[playerId];
    
    var current = Number($(wrapper).find('a.jp-playlist-current').attr('id').split('_')[2]);
    var index = (current + 1 < playerSettings.files.length) ? current + 1 : 0;
    
    Drupal.jPlayer.setFiles(wrapper, player, index, true);
  }
  
  Drupal.jPlayer.previous = function(wrapper, player) {
    var playerId = $(player).attr('id');
    var playerSettings = Drupal.settings.jplayerInstances[playerId];
    
    var current = Number($(wrapper).find('a.jp-playlist-current').attr('id').split('_')[2]);
    var index = (current - 1 >= 0) ? current - 1 : playerSettings.files.length - 1;
    
    Drupal.jPlayer.setFiles(wrapper, player, index, true);
  }
  
  /**
   * Ping the authorization URL to gain access to protected files.
   */
  Drupal.jPlayer.authorize = function(wrapper, player) {
    // Generate the authorization URL to ping.
    var time = new Date();

    var track = "";
    if (player.playerType != 'jp-type-playlist') {
      // For a single track, it's easy to get the file to play.
      // TODO fix this for jPlayer 2.0.
      track = $(player).attr('rel');
    }
    else {
      // Get a reference to the current track using the <ul> list that is used
      // for the jPlayer playlist.
      track = $('#' + player.id + '_playlist .jp-playlist-current a').attr('href');
    }

    var authorize_url = Drupal.settings.basePath + 'jplayer/authorize/' + Drupal.jPlayer.base64Encode(track) + '/' + Drupal.jPlayer.base64Encode(parseInt(time.getTime() / 1000).toString());

    // Ping the authorization URL. We need to disable async so that this
    // command finishes before thisandler returns.

    $.ajax({
      url: authorize_url,
      success: function(data) {
        // Check to see if the access has expired. This could happen due to
        // clock sync differences between the server and the client.
        var seconds = parseInt(data);
        var expires = new Date(seconds * 1000);
        if ($('#jplayer-message').size() == 0) {
          $(wrapper).parent().prepend('<div id="jplayer-message" class="messages error"></div>');
          $('#jplayer-message').hide();
        }
        if (expires < time) {
          var message = Drupal.t('There was an error downloading the audio. Try <a href="@url">reloading the page</a>. If the error persists, check that your computer\'s clock is accurate.', {"@url" : window.location});
          $('#jplayer-message').fadeOut('fast').html("<ul><li>" + message + "</li></ul>").fadeIn('fast');
          $(wrapper).hide();
        }
        else {
          $('#jplayer-message').fadeOut('fast');
        }
      },
      async: false
    });
    return false;
  };

  Drupal.jPlayer.base64Encode = function(data) {
    // From http://phpjs.org/functions/base64_encode:358 where it is
    // dual licensed under GPL/MIT.
    //
    // http://kevin.vanzonneveld.net
    // +   original by: Tyler Akins (http://rumkin.com)
    // +   improved by: Bayron Guevara
    // +   improved by: Thunder.m
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Pellentesque Malesuada
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // -    depends on: utf8_encode
    // *     example 1: base64_encode('Kevin van Zonneveld');
    // *     returns 1: 'S2V2aW4gdmFuIFpvbm5ldmVsZA=='
    var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
        ac = 0,
        enc = "",
        tmp_arr = [];

    if (!data) {
        return data;
    }

    data = Drupal.jPlayer.utf8Encode(data + '');

    do { // pack three octets into four hexets
        o1 = data.charCodeAt(i++);
        o2 = data.charCodeAt(i++);
        o3 = data.charCodeAt(i++);

        bits = o1 << 16 | o2 << 8 | o3;

        h1 = bits >> 18 & 0x3f;
        h2 = bits >> 12 & 0x3f;
        h3 = bits >> 6 & 0x3f;
        h4 = bits & 0x3f;

        // use hexets to index into b64, and append result to encoded string
        tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);

    enc = tmp_arr.join('');

    switch (data.length % 3) {
    case 1:
        enc = enc.slice(0, -2) + '==';
        break;
    case 2:
        enc = enc.slice(0, -1) + '=';
        break;
    }

    return enc;
  };

  Drupal.jPlayer.utf8Encode = function(argString) {
    // From http://phpjs.org/functions/utf8_encode:577 where it is dual-licensed
    // under GPL/MIT.
    // http://kevin.vanzonneveld.net
    // +   original by: Webtoolkit.info (http://www.webtoolkit.info/)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   improved by: sowberry
    // +    tweaked by: Jack
    // +   bugfixed by: Onno Marsman
    // +   improved by: Yves Sucaet
    // +   bugfixed by: Onno Marsman
    // +   bugfixed by: Ulrich
    // +   bugfixed by: Rafal Kukawski
    // *     example 1: utf8_encode('Kevin van Zonneveld');
    // *     returns 1: 'Kevin van Zonneveld'

    if (argString === null || typeof argString === "undefined") {
        return "";
    }

    var string = (argString + ''); // .replace(/\r\n/g, "\n").replace(/\r/g, "\n");
    var utftext = "",
        start, end, stringl = 0;

    start = end = 0;
    stringl = string.length;
    for (var n = 0; n < stringl; n++) {
        var c1 = string.charCodeAt(n);
        var enc = null;

        if (c1 < 128) {
            end++;
        } else if (c1 > 127 && c1 < 2048) {
            enc = String.fromCharCode((c1 >> 6) | 192) + String.fromCharCode((c1 & 63) | 128);
        } else {
            enc = String.fromCharCode((c1 >> 12) | 224) + String.fromCharCode(((c1 >> 6) & 63) | 128) + String.fromCharCode((c1 & 63) | 128);
        }
        if (enc !== null) {
            if (end > start) {
                utftext += string.slice(start, end);
            }
            utftext += enc;
            start = end = n + 1;
        }
    }

    if (end > start) {
        utftext += string.slice(start, stringl);
    }

    return utftext;
  };
})(jQuery);

