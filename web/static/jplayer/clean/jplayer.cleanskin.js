(function ($) { $.fn.slider=function(options,flag){var EVENT=window.navigator.msPointerEnabled?2:"ontouchend" in document?3:1;if(window.debug&&console){console.log(EVENT)}function call(f,scope,args){if(typeof f==="function"){f.call(scope,args)}}var percentage={to:function(range,value){value=range[0]<0?value+Math.abs(range[0]):value-range[0];return(value*100)/this._length(range)},from:function(range,value){return(value*100)/this._length(range)},is:function(range,value){return((value*this._length(range))/100)+range[0]},_length:function(range){return(range[0]>range[1]?range[0]-range[1]:range[1]-range[0])}};function correct(proposal,slider,handle){var setup=slider.data("setup"),handles=setup.handles,settings=setup.settings,pos=setup.pos;proposal=proposal<0?0:proposal>100?100:proposal;if(settings.handles==2){if(handle.is(":first-child")){var other=parseFloat(handles[1][0].style[pos])-settings.margin;proposal=proposal>other?other:proposal}else{var other=parseFloat(handles[0][0].style[pos])+settings.margin;proposal=proposal<other?other:proposal}}if(settings.step){var per=percentage.from(settings.range,settings.step);proposal=Math.round(proposal/per)*per}return proposal}function client(f){try{return[(f.clientX||f.originalEvent.clientX||f.originalEvent.touches[0].clientX),(f.clientY||f.originalEvent.clientY||f.originalEvent.touches[0].clientY)]}catch(e){return["x","y"]}}function place(handle,pos){return parseFloat(handle[0].style[pos])}var defaults={handles:1,serialization:{to:["",""],resolution:0.01}};methods={create:function(){return this.each(function(){function setHandle(handle,to,slider){handle.css(pos,to+"%").data("input").val(percentage.is(settings.range,to).toFixed(res))}var settings=$.extend(defaults,options),handlehtml="<a><div></div></a>",slider=$(this).data("_isnS_",true),handles=[],pos,orientation,classes="",num=function(e){return !isNaN(parseFloat(e))&&isFinite(e)},split=(settings.serialization.resolution=settings.serialization.resolution||0.01).toString().split("."),res=split[0]==1?0:split[1].length;settings.start=num(settings.start)?[settings.start,0]:settings.start;$.each(settings,function(a,b){if(num(b)){settings[a]=parseFloat(b)}else{if(typeof b=="object"&&num(b[0])){b[0]=parseFloat(b[0]);if(num(b[1])){b[1]=parseFloat(b[1])}}}var e=false;b=typeof b=="undefined"?"x":b;switch(a){case"range":case"start":e=b.length!=2||!num(b[0])||!num(b[1]);break;case"handles":e=(b<1||b>2||!num(b));break;case"connect":e=b!="lower"&&b!="upper"&&typeof b!="boolean";break;case"orientation":e=(b!="vertical"&&b!="horizontal");break;case"margin":case"step":e=typeof b!="undefined"&&!num(b);break;case"serialization":e=typeof b!="object"||!num(b.resolution)||(typeof b.to=="object"&&b.to.length<settings.handles);break;case"slide":e=typeof b!="function";break}if(e&&console){console.error("Bad input for "+a+" on slider:",slider)}});settings.margin=settings.margin?percentage.from(settings.range,settings.margin):0;if(settings.serialization.to instanceof jQuery||typeof settings.serialization.to=="string"||settings.serialization.to===false){settings.serialization.to=[settings.serialization.to]}if(settings.orientation=="vertical"){classes+="vertical";pos="top";orientation=1}else{classes+="horizontal";pos="left";orientation=0}classes+=settings.connect?settings.connect=="lower"?" connect lower":" connect":"";slider.addClass(classes);for(var i=0;i<settings.handles;i++){handles[i]=slider.append(handlehtml).children(":last");var setTo=percentage.to(settings.range,settings.start[i]);handles[i].css(pos,setTo+"%");if(setTo==100&&handles[i].is(":first-child")){handles[i].css("z-index",2)}var bind=".slider",onEvent=(EVENT===1?"mousedown":EVENT===2?"MSPointerDown":"touchstart")+bind+"X",moveEvent=(EVENT===1?"mousemove":EVENT===2?"MSPointerMove":"touchmove")+bind,offEvent=(EVENT===1?"mouseup":EVENT===2?"MSPointerUp":"touchend")+bind;handles[i].find("div").on(onEvent,function(e){$("body").bind("selectstart"+bind,function(){return false});if(!slider.hasClass("disabled")){$("body").addClass("TOUCH");var handle=$(this).addClass("active").parent(),unbind=handle.add($(document)).add("body"),originalPosition=parseFloat(handle[0].style[pos]),originalClick=client(e),previousClick=originalClick,previousProposal=false;$(document).on(moveEvent,function(f){f.preventDefault();var currentClick=client(f);if(currentClick[0]=="x"){return}currentClick[0]-=originalClick[0];currentClick[1]-=originalClick[1];var movement=[previousClick[0]!=currentClick[0],previousClick[1]!=currentClick[1]],proposal=originalPosition+((currentClick[orientation]*100)/(orientation?slider.height():slider.width()));proposal=correct(proposal,slider,handle);if(movement[orientation]&&proposal!=previousProposal){handle.css(pos,proposal+"%").data("input").val(percentage.is(settings.range,proposal).toFixed(res));call(settings.slide,slider.data("_n",true));previousProposal=proposal;handle.css("z-index",handles.length==2&&proposal==100&&handle.is(":first-child")?2:1)}previousClick=currentClick}).on(offEvent,function(){unbind.off(bind);$("body").removeClass("TOUCH");if(slider.find(".active").removeClass("active").end().data("_n")){slider.data("_n",false).change()}})}}).on("click",function(e){e.stopPropagation()})}if(EVENT==1){slider.on("click",function(f){if(!slider.hasClass("disabled")){var currentClick=client(f),proposal=((currentClick[orientation]-slider.offset()[pos])*100)/(orientation?slider.height():slider.width()),handle=handles.length>1?(currentClick[orientation]<(handles[0].offset()[pos]+handles[1].offset()[pos])/2?handles[0]:handles[1]):handles[0];setHandle(handle,correct(proposal,slider,handle),slider);call(settings.slide,slider);slider.change()}})}for(var i=0;i<handles.length;i++){var val=percentage.is(settings.range,place(handles[i],pos)).toFixed(res);if(typeof settings.serialization.to[i]=="string"){handles[i].data("input",slider.append('<input type="hidden" name="'+settings.serialization.to[i]+'">').find("input:last").val(val).change(function(a){a.stopPropagation()}))}else{if(settings.serialization.to[i]==false){handles[i].data("input",{val:function(a){if(typeof a!="undefined"){this.handle.data("noUiVal",a)}else{return this.handle.data("noUiVal")}},handle:handles[i]})}else{handles[i].data("input",settings.serialization.to[i].data("handleNR",i).val(val).change(function(){var arr=[null,null];arr[$(this).data("handleNR")]=$(this).val();slider.val(arr)}))}}}$(this).data("setup",{settings:settings,handles:handles,pos:pos,res:res})})},val:function(){if(typeof arguments[0]!=="undefined"){var val=typeof arguments[0]=="number"?[arguments[0]]:arguments[0];return this.each(function(){var setup=$(this).data("setup");for(var i=0;i<setup.handles.length;i++){if(val[i]!=null){var proposal=correct(percentage.to(setup.settings.range,val[i]),$(this),setup.handles[i]);setup.handles[i].css(setup.pos,proposal+"%").data("input").val(percentage.is(setup.settings.range,proposal).toFixed(setup.res))}}})}else{var handles=$(this).data("setup").handles,re=[];for(var i=0;i<handles.length;i++){re.push(parseFloat(handles[i].data("input").val()))}return re.length==1?re[0]:re}},disabled:function(){return flag?$(this).addClass("disabled"):$(this).removeClass("disabled")}};var $_val=jQuery.fn.val;jQuery.fn.val=function(){return this.data("_isnS_")?methods.val.apply(this,arguments):$_val.apply(this,arguments)};return options=="disabled"?methods.disabled.apply(this):methods.create.apply(this)}})(jQuery);

(function ($) {
	
	$.fn.videoPlayer = function(extras) {
		
		var playerGUI = "#" + $(this).attr('id');
		var playerID  = "#" + $(this).find('.videoPlayer').attr('id');
		
		try {
			var settings  =  $.parseJSON($(this).find('.playerData').text());
		} catch (err) {
			console.log('JSON parse ERROR, fall back to JS!');
			var settings = extras;
		}
		
		$(this).find('.playerData').remove();
		$(this).append('<div class="playerScreen">\
<a tabindex="1" href="#" class="video-play noload"></a>\
</div>\
<div class="controls">\
<div class="leftblock">\
<a tabindex="1" href="#" class="play smooth noload"></a>\
<a tabindex="1" href="#" class="pause smooth noload"></a>\
</div>\
<div class="progress">\
<span>' + settings.name + '</span>\
<div class="progressbar">\
<div class="seekBar">\
<div class="playBar"></div>\
</div>\
</div>\
<div class="time current">00:00</div>\
<div class="time duration">00:00</div>\
</div>\
<div class="rightblock">\
<div class="volumeBar">\
<div class="currentVolume"><div class="curvol"></div></div>\
</div>\
<div class="volumeText">Volume: 50</div>\
<a href="#" tabindex="1" class="fullScreen smooth noload"></a>\
<a href="#" tabindex="1" class="fullScreenOFF smooth noload"></a>\
</div>');
		
		if ($(this).hasClass('audioPlayer')) { 
			$(this).find('.fullScreen').remove();
			$(this).find('.fullScreenOFF').remove();
		}
		
		createPlayer(playerGUI, playerID, settings, extras);
		
	}
	
	
	function createPlayer(playerGUI, mainPlayer, settings, extras) {
		
		// Get supplied media from MEDIA array
		var supplied = new Array;
		$.each(settings.media, function(key, value) { if (key != 'poster') {supplied.push(key);}});
		formats = supplied.join(', ');
		
		var options = {
			
			ready: function () {
				$(this).jPlayer("setMedia", settings.media);
				if (settings.autoplay != null) {
					$(mainPlayer).jPlayer('play');
				}
			},
			
			// Extra Settings
			swfPath: "/projects/clean-jplayer-skin/Jplayer.swf",
			supplied: formats,
			solution: 'html, flash',
			volume: 0.5,
			size: settings.size,
			smoothPlayBar: false,
			keyEnabled: true,
			
			// CSS Selectors
			cssSelectorAncestor: playerGUI,
			cssSelector: {
				videoPlay: ".video-play",
				play: ".play",
				pause: ".pause",
				seekBar: ".seekBar",
				playBar: ".playBar",
				volumeBar: ".currentVolume",
				volumeBarValue: ".currentVolume .curvol",
				currentTime: ".time.current",
				duration: ".time.duration",
				fullScreen: ".fullScreen",
				restoreScreen: ".fullScreenOFF",
				gui: ".controls",
				noSolution: ".noSolution"
			},
			
			error: function(event) {
				if(event.jPlayer.error.type === $.jPlayer.error.URL_NOT_SET) {
					// Setup the media stream again and play it.
					$(this).jPlayer("setMedia", settings.media).jPlayer('play');
				}
			},
			
			play: function() {
				$(playerGUI + ' .video-play').fadeOut();
				$(this).on('click', function() { $(mainPlayer).jPlayer('pause');});
				$(this).jPlayer("pauseOthers");
			},
			
			pause: function() {
				$(playerGUI + ' .video-play').fadeIn();
				$(playerGUI + ' .playerScreen').unbind('click');
			},
			
			volumechange: function(event) {        
				if(event.jPlayer.options.muted) {
					$(playerGUI + ' .currentVolume').val(0);
				} else {
					$(playerGUI + ' .currentVolume').val(event.jPlayer.options.volume);
				}   
			},
			
			timeupdate: function(event) {
				$(playerGUI + ' .seekBar').val(event.jPlayer.status.currentPercentRelative);
			},
			
			progress: function(event) {
				$(playerGUI + ' .seekBar').val(event.jPlayer.status.currentPercentRelative);
			},
			
			ended: function() {
				$(this).jPlayer("setMedia", settings.media);
			}
			
			
		};
		
		// Create the volume slider control
		$(playerGUI + ' .currentVolume').slider({
			range: [0, 1],
			step: 0.01,
			start : 0.5,
			handles: 1,
			slide: function() {
				var value = $(this).val();
				$(mainPlayer).jPlayer("option", "muted", false);
				$(mainPlayer).jPlayer("option", "volume", value);
				$(playerGUI + ' .volumeText').html('Volume: ' + (value * 100).toFixed(0) + '');
			}
		});
		
		$(playerGUI + ' .seekBar').slider({
			range: [0,100],
			step: 0.01,
			start: 0,
			handles: 1,
			slide: function() {
				var value = $(this).val();
				$(mainPlayer).jPlayer("playHead", value);
			}
			
		});
		
		// Initialize Player
		$.extend(options, extras);
		$(mainPlayer).jPlayer(options);
		
	}
	
	
})(jQuery);