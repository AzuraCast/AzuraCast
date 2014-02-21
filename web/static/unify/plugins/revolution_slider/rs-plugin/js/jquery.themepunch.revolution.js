/**************************************************************************
 * jquery.themepunch.revolution.js - jQuery Plugin for kenburn Slider
 * @version: 2.3.9 (03.04.2013)
 * @requires jQuery v1.7 or later (tested on 1.9)
 * @author ThemePunch
**************************************************************************/


(function($,undefined){


	////////////////////////////////////////
	// THE REVOLUTION PLUGIN STARTS HERE //
	///////////////////////////////////////

	$.fn.extend({

		// OUR PLUGIN HERE :)
		revolution: function(options) {



				////////////////////////////////
				// SET DEFAULT VALUES OF ITEM //
				////////////////////////////////
				$.fn.revolution.defaults = {
					delay:9000,
					startheight:500,
					startwidth:960,

					hideThumbs:200,

					thumbWidth:100,							// Thumb With and Height and Amount (only if navigation Tyope set to thumb !)
					thumbHeight:50,
					thumbAmount:5,

					navigationType:"bullet",				// bullet, thumb, none
					navigationArrows:"withbullet",			// nextto, solo, none

					navigationStyle:"round",				// round,square,navbar,round-old,square-old,navbar-old, or any from the list in the docu (choose between 50+ different item),

					navigationHAlign:"center",				// Vertical Align top,center,bottom
					navigationVAlign:"bottom",					// Horizontal Align left,center,right
					navigationHOffset:0,
					navigationVOffset:20,

					soloArrowLeftHalign:"left",
					soloArrowLeftValign:"center",
					soloArrowLeftHOffset:20,
					soloArrowLeftVOffset:0,

					soloArrowRightHalign:"right",
					soloArrowRightValign:"center",
					soloArrowRightHOffset:20,
					soloArrowRightVOffset:0,

					touchenabled:"on",						// Enable Swipe Function : on/off
					onHoverStop:"on",						// Stop Banner Timet at Hover on Slide on/off


					stopAtSlide:-1,							// Stop Timer if Slide "x" has been Reached. If stopAfterLoops set to 0, then it stops already in the first Loop at slide X which defined. -1 means do not stop at any slide. stopAfterLoops has no sinn in this case.
					stopAfterLoops:-1,						// Stop Timer if All slides has been played "x" times. IT will stop at THe slide which is defined via stopAtSlide:x, if set to -1 slide never stop automatic

					hideCaptionAtLimit:0,					// It Defines if a caption should be shown under a Screen Resolution ( Basod on The Width of Browser)
					hideAllCaptionAtLilmit:0,				// Hide all The Captions if Width of Browser is less then this value
					hideSliderAtLimit:0,					// Hide the whole slider, and stop also functions if Width of Browser is less than this value

					shadow:1,								//0 = no Shadow, 1,2,3 = 3 Different Art of Shadows  (No Shadow in Fullwidth Version !)
					fullWidth:"off"							// Turns On or Off the Fullwidth Image Centering in FullWidth Modus

				};

					options = $.extend({}, $.fn.revolution.defaults, options);




					return this.each(function() {

						var opt=options;
						var container=$(this);
						if (!container.hasClass("revslider-initialised")) {

									container.addClass("revslider-initialised");

									// CHECK IF FIREFOX 13 IS ON WAY.. IT HAS A STRANGE BUG, CSS ANIMATE SHOULD NOT BE USED

									

									opt.firefox13 = false;
									opt.ie = !$.support.opacity;
									opt.ie9 = (document.documentMode == 9);


									// CHECK THE jQUERY VERSION
									var version = $.fn.jquery.split('.'),
										versionTop = parseFloat(version[0]),
										versionMinor = parseFloat(version[1]),
										versionIncrement = parseFloat(version[2] || '0');

									if (versionTop==1 && versionMinor < 7) {
										container.html('<div style="text-align:center; padding:40px 0px; font-size:20px; color:#992222;"> The Current Version of jQuery:'+version+' <br>Please update your jQuery Version to min. 1.7 in Case you wish to use the Revolution Slider Plugin</div>');
									}

									// Delegate .transition() calls to .animate()
									// if the browser can't do CSS transitions.
									if (!$.support.transition)
										$.fn.transition = $.fn.animate;




									$.cssEase['bounce'] = 'cubic-bezier(0,1,0.5,1.3)';

									// CATCH THE CONTAINER
									//var container=$(this);
									//container.css({'display':'block'});

									 // LOAD THE YOUTUBE API IF NECESSARY

									container.find('.caption').each(function() { $(this).addClass('tp-caption')});
									container.find('.tp-caption iframe').each(function() {
										try {
												if ($(this).attr('src').indexOf('you')>0) {

													var s = document.createElement("script");
													s.src = "http://www.youtube.com/player_api"; /* Load Player API*/
													var before = document.getElementsByTagName("script")[0];
													before.parentNode.insertBefore(s, before);
												}
											} catch(e) {}
									});



									 // LOAD THE VIMEO API
									 container.find('.tp-caption iframe').each(function() {
										try{
												if ($(this).attr('src').indexOf('vim')>0) {

													var f = document.createElement("script");
													f.src = "http://a.vimeocdn.com/js/froogaloop2.min.js"; /* Load Player API*/
													var before = document.getElementsByTagName("script")[0];
													before.parentNode.insertBefore(f, before);
												}
											} catch(e) {}
									});

									// SHUFFLE MODE
									if (opt.shuffle=="on") {
										for (var u=0;u<container.find('>ul:first-child >li').length;u++) {
											var it = Math.round(Math.random()*container.find('>ul:first-child >li').length);
											container.find('>ul:first-child >li:eq('+it+')').prependTo(container.find('>ul:first-child'));
										}
									}


									// CREATE SOME DEFAULT OPTIONS FOR LATER
									opt.slots=4;
									opt.act=-1;
									opt.next=0;

									// IF START SLIDE IS SET
									if (opt.startWithSlide !=undefined) opt.next=opt.startWithSlide;

									// IF DEEPLINK HAS BEEN SET
									var deeplink = getUrlVars("#")[0];																	
									if (deeplink.length<9) {
										if (deeplink.split('slide').length>1) {
											var dslide=parseInt(deeplink.split('slide')[1],0);											
											if (dslide<1) dslide=1;
											if (dslide>container.find('>ul:first >li').length) dslide=container.find('>ul:first >li').length;
											opt.next=dslide-1;
										}
									}


									opt.origcd=opt.delay;

									opt.firststart=1;






									// BASIC OFFSET POSITIONS OF THE BULLETS
									if (opt.navigationHOffset==undefined) opt.navOffsetHorizontal=0;
									if (opt.navigationVOffset==undefined) opt.navOffsetVertical=0;





									container.append('<div class="tp-loader"></div>');

									// RESET THE TIMER
									if (container.find('.tp-bannertimer').length==0) container.append('<div class="tp-bannertimer" style="visibility:hidden"></div>');
									var bt=container.find('.tp-bannertimer');
									if (bt.length>0) {
										bt.css({'width':'0%'});
									};


									// WE NEED TO ADD A BASIC CLASS FOR SETTINGS.CSS
									container.addClass("tp-simpleresponsive");
									opt.container=container;

									//if (container.height()==0) container.height(opt.startheight);

									// AMOUNT OF THE SLIDES
									opt.slideamount = container.find('>ul:first >li').length;


									// A BASIC GRID MUST BE DEFINED. IF NO DEFAULT GRID EXIST THAN WE NEED A DEFAULT VALUE, ACTUAL SIZE OF CONAINER
									if (container.height()==0) container.height(opt.startheight);
									if (opt.startwidth==undefined || opt.startwidth==0) opt.startwidth=container.width();
									if (opt.startheight==undefined || opt.startheight==0) opt.startheight=container.height();

									// OPT WIDTH && HEIGHT SHOULD BE SET
									opt.width=container.width();
									opt.height=container.height();

									// DEFAULT DEPENDECIES
									opt.bw = opt.startwidth / container.width();
									opt.bh = opt.startheight / container.height();

									// IF THE ITEM ALREADY IN A RESIZED FORM
									if (opt.width!=opt.startwidth) {

										opt.height = Math.round(opt.startheight * (opt.width/opt.startwidth));
										container.height(opt.height);

									}

									// LETS SEE IF THERE IS ANY SHADOW
									if (opt.shadow!=0) {
										container.parent().append('<div class="tp-bannershadow tp-shadow'+opt.shadow+'"></div>');

										container.parent().find('.tp-bannershadow').css({'width':opt.width});
									}


									container.find('ul').css({'display':'none'});

									// IF IMAGES HAS BEEN LOADED
									container.waitForImages(function() {
											// PREPARE THE SLIDES
											container.find('ul').css({'display':'block'});
											prepareSlides(container,opt);

											// CREATE BULLETS
											if (opt.slideamount >1) createBullets(container,opt);
											if (opt.slideamount >1) createThumbs(container,opt);
											if (opt.slideamount >1) createArrows(container,opt);

											$('#unvisible_button').click(function() {

													opt.navigationArrows=$('.selectnavarrows').val();
													opt.navigationType=$('.selectnavtype').val();
													opt.navigationStyle = $('.selectnavstyle').val();
													opt.soloArrowStyle = "default";

													$('.tp-bullets').remove();
													$('.tparrows').remove();

													if (opt.slideamount >1) createBullets(container,opt);
													if (opt.slideamount >1) createThumbs(container,opt);
													if (opt.slideamount >1) createArrows(container,opt);

											});


											swipeAction(container,opt);

											if (opt.hideThumbs>0) hideThumbs(container,opt);


											container.waitForImages(function() {
												// START THE FIRST SLIDE

												container.find('.tp-loader').fadeOut(600);
												setTimeout(function() {

													swapSlide(container,opt);
													// START COUNTDOWN
													if (opt.slideamount >1) countDown(container,opt);
													container.trigger('revolution.slide.onloaded');
												},600);

											});


									});


									// IF RESIZED, NEED TO STOP ACTUAL TRANSITION AND RESIZE ACTUAL IMAGES
									$(window).resize(function() {
										if ($('body').find(container)!=0)
											if (container.outerWidth(true)!=opt.width) {
													containerResized(container,opt);
											}
									});
						}

					})
				},


		// METHODE PAUSE
		revpause: function(options) {

					return this.each(function() {
						var container=$(this);
						container.data('conthover',1);
						container.data('conthover-changed',1);
						container.trigger('revolution.slide.onpause');
						var bt = container.parent().find('.tp-bannertimer');
						bt.stop();

					})


				},

		// METHODE RESUME
		revresume: function(options) {
					return this.each(function() {
						var container=$(this);
						container.data('conthover',0);
						container.data('conthover-changed',1);
						container.trigger('revolution.slide.onresume');
						var bt = container.parent().find('.tp-bannertimer');
						var opt = bt.data('opt');

						bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
					})

				},

		// METHODE NEXT
		revnext: function(options) {
					return this.each(function() {
						// CATCH THE CONTAINER
						var container=$(this);
						container.parent().find('.tp-rightarrow').click();


					})

				},

		// METHODE RESUME
		revprev: function(options) {
					return this.each(function() {
						// CATCH THE CONTAINER
						var container=$(this);
						container.parent().find('.tp-leftarrow').click();
					})

				},

		// METHODE LENGTH
		revmaxslide: function(options) {
						// CATCH THE CONTAINER
						return $(this).find('>ul:first-child >li').length;
				},


		// METHODE CURRENT
		revcurrentslide: function(options) {
						// CATCH THE CONTAINER
						var container=$(this);
						var bt = container.parent().find('.tp-bannertimer');
						var opt = bt.data('opt');
						return opt.act;
				},

		// METHODE CURRENT
		revlastslide: function(options) {
						// CATCH THE CONTAINER
						var container=$(this);
						var bt = container.parent().find('.tp-bannertimer');
						var opt = bt.data('opt');
						return opt.lastslide;
				},


		// METHODE JUMP TO SLIDE
		revshowslide: function(slide) {
					return this.each(function() {
						// CATCH THE CONTAINER
						var container=$(this);
						container.data('showus',slide);
						container.parent().find('.tp-rightarrow').click();
					})

				}


})


		///////////////////////////
		// GET THE URL PARAMETER //
		///////////////////////////
		function getUrlVars(hashdivider)
			{
				var vars = [], hash;
				var hashes = window.location.href.slice(window.location.href.indexOf(hashdivider) + 1).split('_');
				for(var i = 0; i < hashes.length; i++)
				{
					hashes[i] = hashes[i].replace('%3D',"=");
					hash = hashes[i].split('=');
					vars.push(hash[0]);
					vars[hash[0]] = hash[1];
				}
				return vars;
			}

		//////////////////////////
		//	CONTAINER RESIZED	//
		/////////////////////////
		function containerResized(container,opt) {


			container.find('.defaultimg').each(function(i) {

						setSize($(this),opt);

						opt.height = Math.round(opt.startheight * (opt.width/opt.startwidth));
						container.height(opt.height);
						
						setSize($(this),opt);

						try{
							container.parent().find('.tp-bannershadow').css({'width':opt.width});
						} catch(e) {}

						var actsh = container.find('>ul >li:eq('+opt.act+') .slotholder');
						var nextsh = container.find('>ul >li:eq('+opt.next+') .slotholder');
						removeSlots(container,opt);
						nextsh.find('.defaultimg').css({'opacity':0});
						actsh.find('.defaultimg').css({'opacity':1});

						setCaptionPositions(container,opt);

						var nextli = container.find('>ul >li:eq('+opt.next+')');
						container.find('.tp-caption').each(function() { $(this).stop(true,true);});
						animateTheCaptions(nextli, opt);

						restartBannerTimer(opt,container);

				});
		}



		////////////////////////////////
		//	RESTART THE BANNER TIMER //
		//////////////////////////////
		function restartBannerTimer(opt,container) {
						opt.cd=0;
						if (opt.videoplaying !=true) {
							var bt=	container.find('.tp-bannertimer');
								if (bt.length>0) {
									bt.stop();
									bt.css({'width':'0%'});
									bt.animate({'width':"100%"},{duration:(opt.delay-100),queue:false, easing:"linear"});
								}
							clearTimeout(opt.thumbtimer);
							opt.thumbtimer = setTimeout(function() {
								moveSelectedThumb(container);
								setBulPos(container,opt);
							},200);
						}
		}

		function callingNewSlide(opt,container) {
						opt.cd=0;
						swapSlide(container,opt);

						// STOP TIMER AND RESCALE IT
							var bt=	container.find('.tp-bannertimer');
							if (bt.length>0) {
								bt.stop();
								bt.css({'width':'0%'});
								bt.animate({'width':"100%"},{duration:(opt.delay-100),queue:false, easing:"linear"});
							}
		}



		////////////////////////////////
		//	-	CREATE THE BULLETS -  //
		////////////////////////////////
		function createThumbs(container,opt) {

			var cap=container.parent();

			if (opt.navigationType=="thumb" || opt.navsecond=="both") {
						cap.append('<div class="tp-bullets tp-thumbs '+opt.navigationStyle+'"><div class="tp-mask"><div class="tp-thumbcontainer"></div></div></div>');
			}
			var bullets = cap.find('.tp-bullets.tp-thumbs .tp-mask .tp-thumbcontainer');
			var bup = bullets.parent();

			bup.width(opt.thumbWidth*opt.thumbAmount);
			bup.height(opt.thumbHeight);
			bup.parent().width(opt.thumbWidth*opt.thumbAmount);
			bup.parent().height(opt.thumbHeight);

			container.find('>ul:first >li').each(function(i) {
							var li= container.find(">ul:first >li:eq("+i+")");
							if (li.data('thumb') !=undefined)
								var src= li.data('thumb')
							else
								var src=li.find("img:first").attr('src');
							bullets.append('<div class="bullet thumb"><img src="'+src+'"></div>');
							var bullet= bullets.find('.bullet:first');
				});
			//bullets.append('<div style="clear:both"></div>');
			var minwidth=100;


			// ADD THE BULLET CLICK FUNCTION HERE
			bullets.find('.bullet').each(function(i) {
				var bul = $(this);

				if (i==opt.slideamount-1) bul.addClass('last');
				if (i==0) bul.addClass('first');
				bul.width(opt.thumbWidth);
				bul.height(opt.thumbHeight);
				if (minwidth>bul.outerWidth(true)) minwidth=bul.outerWidth(true);

				bul.click(function() {
					if (opt.transition==0 && bul.index() != opt.act) {
						opt.next = bul.index();
						callingNewSlide(opt,container);
					}
				});
			});


			var max=minwidth*container.find('>ul:first >li').length;

			var thumbconwidth=bullets.parent().width();
			opt.thumbWidth = minwidth;



				////////////////////////
				// SLIDE TO POSITION  //
				////////////////////////
				if (thumbconwidth<max) {
					$(document).mousemove(function(e) {
						$('body').data('mousex',e.pageX);
					});



					// ON MOUSE MOVE ON THE THUMBNAILS EVERYTHING SHOULD MOVE :)

					bullets.parent().mouseenter(function() {
							var $this=$(this);
							$this.addClass("over");
							var offset = $this.offset();
							var x = $('body').data('mousex')-offset.left;
							var thumbconwidth=$this.width();
							var minwidth=$this.find('.bullet:first').outerWidth(true);
							var max=minwidth*container.find('>ul:first >li').length;
							var diff=(max- thumbconwidth)+15;
							var steps = diff / thumbconwidth;
							x=x-30;
							//if (x<30) x=0;
							//if (x>thumbconwidth-30) x=thumbconwidth;

							//ANIMATE TO POSITION
							var pos=(0-((x)*steps));
							if (pos>0) pos =0;
							if (pos<0-max+thumbconwidth) pos=0-max+thumbconwidth;
							moveThumbSliderToPosition($this,pos,200);
					});

					bullets.parent().mousemove(function() {

									var $this=$(this);

									//if (!$this.hasClass("over")) {
											var offset = $this.offset();
											var x = $('body').data('mousex')-offset.left;
											var thumbconwidth=$this.width();
											var minwidth=$this.find('.bullet:first').outerWidth(true);
											var max=minwidth*container.find('>ul:first >li').length;
											var diff=(max- thumbconwidth)+15;
											var steps = diff / thumbconwidth;
											x=x-30;
											//if (x<30) x=0;
											//if (x>thumbconwidth-30) x=thumbconwidth;

											//ANIMATE TO POSITION
											var pos=(0-((x)*steps));
											if (pos>0) pos =0;
											if (pos<0-max+thumbconwidth) pos=0-max+thumbconwidth;
											moveThumbSliderToPosition($this,pos,0);
									//} else {
										//$this.removeClass("over");
									//}

					});

					bullets.parent().mouseleave(function() {
									var $this=$(this);
									$this.removeClass("over");
									moveSelectedThumb(container);
					});
				}


		}


		///////////////////////////////
		//	SelectedThumbInPosition //
		//////////////////////////////
		function moveSelectedThumb(container) {

									var bullets=container.parent().find('.tp-bullets.tp-thumbs .tp-mask .tp-thumbcontainer');
									var $this=bullets.parent();
									var offset = $this.offset();
									var minwidth=$this.find('.bullet:first').outerWidth(true);

									var x = $this.find('.bullet.selected').index() * minwidth;
									var thumbconwidth=$this.width();
									var minwidth=$this.find('.bullet:first').outerWidth(true);
									var max=minwidth*container.find('>ul:first >li').length;
									var diff=(max- thumbconwidth);
									var steps = diff / thumbconwidth;

									//ANIMATE TO POSITION
									var pos=0-x;

									if (pos>0) pos =0;
									if (pos<0-max+thumbconwidth) pos=0-max+thumbconwidth;
									if (!$this.hasClass("over")) {
										moveThumbSliderToPosition($this,pos,200);
									}
		}


		////////////////////////////////////
		//	MOVE THUMB SLIDER TO POSITION //
		///////////////////////////////////
		function moveThumbSliderToPosition($this,pos,speed) {
			$this.stop();
			$this.find('.tp-thumbcontainer').animate({'left':pos+'px'},{duration:speed,queue:false});
		}



		////////////////////////////////
		//	-	CREATE THE BULLETS -  //
		////////////////////////////////
		function createBullets(container,opt) {

			if (opt.navigationType=="bullet"  || opt.navigationType=="both") {
						container.parent().append('<div class="tp-bullets simplebullets '+opt.navigationStyle+'"></div>');
			}


			var bullets = container.parent().find('.tp-bullets');

			container.find('>ul:first >li').each(function(i) {
							var src=container.find(">ul:first >li:eq("+i+") img:first").attr('src');
							bullets.append('<div class="bullet"></div>');
							var bullet= bullets.find('.bullet:first');


				});

			// ADD THE BULLET CLICK FUNCTION HERE
			bullets.find('.bullet').each(function(i) {
				var bul = $(this);
				if (i==opt.slideamount-1) bul.addClass('last');
				if (i==0) bul.addClass('first');

				bul.click(function() {
					var sameslide = false;
					if (opt.navigationArrows=="withbullet" || opt.navigationArrows=="nexttobullets") {
						if (bul.index()-1 == opt.act) sameslide=true;
					} else {
						if (bul.index() == opt.act) sameslide=true;
					}

					if (opt.transition==0 && !sameslide) {

					if (opt.navigationArrows=="withbullet" || opt.navigationArrows=="nexttobullets") {
							opt.next = bul.index()-1;
					} else {
							opt.next = bul.index();
					}

						callingNewSlide(opt,container);
					}
				});

			});

			bullets.append('<div class="tpclear"></div>');



			setBulPos(container,opt);





		}

		//////////////////////
		//	CREATE ARROWS	//
		/////////////////////
		function createArrows(container,opt) {

						var bullets = container.find('.tp-bullets');

						var hidden="";
						var arst= opt.navigationStyle;
						if (opt.navigationArrows=="none") hidden="visibility:none";
						opt.soloArrowStyle = "default";

						if (opt.navigationArrows!="none" && opt.navigationArrows!="nexttobullets") arst = opt.soloArrowStyle;

						container.parent().append('<div style="'+hidden+'" class="tp-leftarrow tparrows '+arst+'"></div>');
						container.parent().append('<div style="'+hidden+'" class="tp-rightarrow tparrows '+arst+'"></div>');

						// 	THE LEFT / RIGHT BUTTON CLICK !	 //
						container.parent().find('.tp-rightarrow').click(function() {

							if (opt.transition==0) {
									if (container.data('showus') !=undefined && container.data('showus') != -1)
										opt.next = container.data('showus')-1;
									else
										opt.next = opt.next+1;
									container.data('showus',-1);
									if (opt.next >= opt.slideamount) opt.next=0;
									if (opt.next<0) opt.next=0;

									if (opt.act !=opt.next)
										callingNewSlide(opt,container);
							}
						});

						container.parent().find('.tp-leftarrow').click(function() {
							if (opt.transition==0) {
									opt.next = opt.next-1;
									opt.leftarrowpressed=1;
									if (opt.next < 0) opt.next=opt.slideamount-1;
									callingNewSlide(opt,container);
							}
						});

						setBulPos(container,opt);

		}

		////////////////////////////
		// SET THE SWIPE FUNCTION //
		////////////////////////////
		function swipeAction(container,opt) {
			// TOUCH ENABLED SCROLL

				if (opt.touchenabled=="on")
						container.swipe( {data:container,
										swipeRight:function()
												{

													if (opt.transition==0) {
															opt.next = opt.next-1;
															opt.leftarrowpressed=1;
															if (opt.next < 0) opt.next=opt.slideamount-1;
															callingNewSlide(opt,container);
													}
												},
										swipeLeft:function()
												{

													if (opt.transition==0) {
															opt.next = opt.next+1;
															if (opt.next == opt.slideamount) opt.next=0;
															callingNewSlide(opt,container);
													}
												},
									allowPageScroll:"auto"} );
		}




		////////////////////////////////////////////////////////////////
		// SHOW AND HIDE THE THUMBS IF MOUE GOES OUT OF THE BANNER  ///
		//////////////////////////////////////////////////////////////
		function hideThumbs(container,opt) {

			var bullets = container.parent().find('.tp-bullets');
			var ca = container.parent().find('.tparrows');

			if (bullets==null) {
				container.append('<div class=".tp-bullets"></div>');
				var bullets = container.parent().find('.tp-bullets');
			}

			if (ca==null) {
				container.append('<div class=".tparrows"></div>');
				var ca = container.parent().find('.tparrows');
			}


			//var bp = (thumbs.parent().outerHeight(true) - opt.height)/2;

			//	ADD THUMBNAIL IMAGES FOR THE BULLETS //
			container.data('hidethumbs',opt.hideThumbs);

			bullets.addClass("hidebullets");
			ca.addClass("hidearrows");

			bullets.hover(function() {
				bullets.addClass("hovered");
				clearTimeout(container.data('hidethumbs'));
				bullets.removeClass("hidebullets");
				ca.removeClass("hidearrows");
			},
			function() {

				bullets.removeClass("hovered");
				if (!container.hasClass("hovered") && !bullets.hasClass("hovered"))
					container.data('hidethumbs', setTimeout(function() {
					bullets.addClass("hidebullets");
					ca.addClass("hidearrows");
					},opt.hideThumbs));
			});


			ca.hover(function() {
				bullets.addClass("hovered");
				clearTimeout(container.data('hidethumbs'));
				bullets.removeClass("hidebullets");
				ca.removeClass("hidearrows");

			},
			function() {

				bullets.removeClass("hovered");
				/*if (!container.hasClass("hovered") && !bullets.hasClass("hovered"))
					container.data('hidethumbs', setTimeout(function() {
							bullets.addClass("hidebullets");
							ca.addClass("hidearrows");
					},opt.hideThumbs));*/
			});



			container.on('mouseenter', function() {
				container.addClass("hovered");
				clearTimeout(container.data('hidethumbs'));
				bullets.removeClass("hidebullets");
				ca.removeClass("hidearrows");
			});

			container.on('mouseleave', function() {
				container.removeClass("hovered");
				if (!container.hasClass("hovered") && !bullets.hasClass("hovered"))
					container.data('hidethumbs', setTimeout(function() {
							bullets.addClass("hidebullets");
							ca.addClass("hidearrows");
					},opt.hideThumbs));
			});

		}







		//////////////////////////////
		//	SET POSITION OF BULLETS	//
		//////////////////////////////
		function setBulPos(container,opt) {
			var topcont=container.parent();
			var bullets=topcont.find('.tp-bullets');
			var tl = topcont.find('.tp-leftarrow');
			var tr = topcont.find('.tp-rightarrow');

			if (opt.navigationType=="thumb" && opt.navigationArrows=="nexttobullets") opt.navigationArrows="solo";
			// IM CASE WE HAVE NAVIGATION BULLETS TOGETHER WITH ARROWS
			if (opt.navigationArrows=="nexttobullets") {
				tl.prependTo(bullets).css({'float':'left'});
				tr.insertBefore(bullets.find('.tpclear')).css({'float':'left'});
			}


			if (opt.navigationArrows!="none" && opt.navigationArrows!="nexttobullets") {

				tl.css({'position':'absolute'});
				tr.css({'position':'absolute'});

				if (opt.soloArrowLeftValign=="center")	tl.css({'top':'50%','marginTop':(opt.soloArrowLeftVOffset-Math.round(tl.innerHeight()/2))+"px"});
				if (opt.soloArrowLeftValign=="bottom")	tl.css({'bottom':(0+opt.soloArrowLeftVOffset)+"px"});
				if (opt.soloArrowLeftValign=="top")	 	tl.css({'top':(0+opt.soloArrowLeftVOffset)+"px"});
				if (opt.soloArrowLeftHalign=="center")	tl.css({'left':'50%','marginLeft':(opt.soloArrowLeftHOffset-Math.round(tl.innerWidth()/2))+"px"});
				if (opt.soloArrowLeftHalign=="left")	tl.css({'left':(0+opt.soloArrowLeftHOffset)+"px"});
				if (opt.soloArrowLeftHalign=="right")	tl.css({'right':(0+opt.soloArrowLeftHOffset)+"px"});

				if (opt.soloArrowRightValign=="center")	tr.css({'top':'50%','marginTop':(opt.soloArrowRightVOffset-Math.round(tr.innerHeight()/2))+"px"});
				if (opt.soloArrowRightValign=="bottom")	tr.css({'bottom':(0+opt.soloArrowRightVOffset)+"px"});
				if (opt.soloArrowRightValign=="top")	tr.css({'top':(0+opt.soloArrowRightVOffset)+"px"});
				if (opt.soloArrowRightHalign=="center")	tr.css({'left':'50%','marginLeft':(opt.soloArrowRightHOffset-Math.round(tr.innerWidth()/2))+"px"});
				if (opt.soloArrowRightHalign=="left")	tr.css({'left':(0+opt.soloArrowRightHOffset)+"px"});
				if (opt.soloArrowRightHalign=="right")	tr.css({'right':(0+opt.soloArrowRightHOffset)+"px"});


				if (tl.position()!=null)
					tl.css({'top':Math.round(parseInt(tl.position().top,0))+"px"});

				if (tr.position()!=null)
					tr.css({'top':Math.round(parseInt(tr.position().top,0))+"px"});
			}

			if (opt.navigationArrows=="none") {
				tl.css({'visibility':'hidden'});
				tr.css({'visibility':'hidden'});
			}

			// SET THE POSITIONS OF THE BULLETS // THUMBNAILS


			if (opt.navigationVAlign=="center")	 bullets.css({'top':'50%','marginTop':(opt.navigationVOffset-Math.round(bullets.innerHeight()/2))+"px"});
			if (opt.navigationVAlign=="bottom")	 bullets.css({'bottom':(0+opt.navigationVOffset)+"px"});
			if (opt.navigationVAlign=="top")	 bullets.css({'top':(0+opt.navigationVOffset)+"px"});


			if (opt.navigationHAlign=="center")	bullets.css({'left':'50%','marginLeft':(opt.navigationHOffset-Math.round(bullets.innerWidth()/2))+"px"});
			if (opt.navigationHAlign=="left")	bullets.css({'left':(0+opt.navigationHOffset)+"px"});
			if (opt.navigationHAlign=="right")	bullets.css({'right':(0+opt.navigationHOffset)+"px"});



		}



		//////////////////////////////////////////////////////////
		//	-	SET THE IMAGE SIZE TO FIT INTO THE CONTIANER -  //
		////////////////////////////////////////////////////////
		function setSize(img,opt) {


			opt.width=parseInt(opt.container.width(),0);
			opt.height=parseInt(opt.container.height(),0);

			opt.bw = opt.width / opt.startwidth;
			opt.bh = opt.height / opt.startheight;

			if (opt.bh>1) {
							opt.bw=1;
							opt.bh=1;
						}


			// IF IMG IS ALREADY PREPARED, WE RESET THE SIZE FIRST HERE
			if (img.data('orgw')!=undefined) {
				img.width(img.data('orgw'));
				img.height(img.data('orgh'));
			}


			var fw = opt.width / img.width();
			var fh = opt.height / img.height();


			opt.fw = fw;
			opt.fh = fh;

			if (img.data('orgw')==undefined) {
				img.data('orgw',img.width());
				img.data('orgh',img.height());
			}



			if (opt.fullWidth=="on") {

					var cow = opt.container.parent().width();
					var coh = opt.container.parent().height();
					var ffh = coh / img.data('orgh');
					var ffw = cow / img.data('orgw');


					img.width(img.width()*ffh);
					img.height(coh);

					if (img.width()<cow) {
						img.width(cow+50);
						var ffw = img.width() / img.data('orgw');
						img.height(img.data('orgh')*ffw);
						
					}

					if (img.width()>cow) {
						img.data("fxof",(cow/2 - img.width()/2));
						img.css({'position':'absolute','left':img.data('fxof')+"px"});
						
					}


					if (img.height()<=coh) {
						img.data('fyof',0);
						img.data("fxof",(cow/2 - img.width()/2));
						img.css({'position':'absolute','top':img.data('fyof')+"px",'left':img.data('fxof')+"px"});
						
					}


					if (img.height()>coh && img.data('fullwidthcentering')=="on") {
						img.data('fyof',(coh/2 - img.height()/2));
						img.data("fxof",(cow/2 - img.width()/2));
						img.css({'position':'absolute','top':img.data('fyof')+"px",'left':img.data('fxof')+"px"});
						
					 }


			} else {

					img.width(opt.width);
					img.height(img.height()*fw);

					if (img.height()<opt.height && img.height()!=0 && img.height()!=null) {

						img.height(opt.height);
						img.width(img.data('orgw')*fh);
					}
			}



			img.data('neww',img.width());
			img.data('newh',img.height());
			if (opt.fullWidth=="on") {
				opt.slotw=Math.ceil(img.width()/opt.slots);
			} else {
				opt.slotw=Math.ceil(opt.width/opt.slots);
			}
			opt.sloth=Math.ceil(opt.height/opt.slots);

		}




		/////////////////////////////////////////
		//	-	PREPARE THE SLIDES / SLOTS -  //
		///////////////////////////////////////
		function prepareSlides(container,opt) {

			container.find('.tp-caption').each(function() { $(this).addClass($(this).data('transition')); $(this).addClass('start') });

			container.find('>ul:first >li').each(function(j) {
				var li=$(this);
				if (li.data('link')!=undefined) {
					var link = li.data('link');
					var target="_self";
					var zindex=2;
					if (li.data('slideindex')=="back") zindex=0;

					var linktoslide=li.data('linktoslide');
					if (li.data('target')!=undefined) target=li.data('target');

					if (link=="slide") {
						li.append('<div class="tp-caption sft slidelink" style="z-index:'+zindex+';" data-x="0" data-y="0" data-linktoslide="'+linktoslide+'" data-start="0"><a><div></div></a></div>');
					} else {
						linktoslide="no";
						li.append('<div class="tp-caption sft slidelink" style="z-index:'+zindex+';" data-x="0" data-y="0" data-linktoslide="'+linktoslide+'" data-start="0"><a target="'+target+'" href="'+link+'"><div></div></a></div>');
					}

				}
			});

			container.find('>ul:first >li >img').each(function(j) {

				var img=$(this);
				img.addClass('defaultimg');
				setSize(img,opt);
				setSize(img,opt);
				img.wrap('<div class="slotholder"></div>');
				img.css({'opacity':0});
				img.data('li-id',j);

			});
		}


		///////////////////////
		// PREPARE THE SLIDE //
		//////////////////////
		function prepareOneSlide(slotholder,opt,visible) {

				var sh=slotholder;
				var img = sh.find('img')

				setSize(img,opt)
				var src = img.attr('src');
				var bgcolor=img.css('background-color');

				var w = img.data('neww');
				var h = img.data('newh');
				var fulloff = img.data("fxof");
				if (fulloff==undefined) fulloff=0;

				var fullyoff=img.data("fyof");
				if (img.data('fullwidthcentering')!="on" || fullyoff==undefined) fullyoff=0;

				var off=0;


				if (!visible)
					var off=0-opt.slotw;

				for (var i=0;i<opt.slots;i++)
					sh.append('<div class="slot" style="position:absolute;top:'+(0+fullyoff)+'px;left:'+(fulloff+i*opt.slotw)+'px;overflow:hidden;width:'+opt.slotw+'px;height:'+h+'px"><div class="slotslide" style="position:absolute;top:0px;left:'+off+'px;width:'+opt.slotw+'px;height:'+h+'px;overflow:hidden;"><img style="background-color:'+bgcolor+';position:absolute;top:0px;left:'+(0-(i*opt.slotw))+'px;width:'+w+'px;height:'+h+'px" src="'+src+'"></div></div>');

		}


		///////////////////////
		// PREPARE THE SLIDE //
		//////////////////////
		function prepareOneSlideV(slotholder,opt,visible) {

				var sh=slotholder;
				var img = sh.find('img')
				setSize(img,opt)
				var src = img.attr('src');
				var bgcolor=img.css('background-color');
				var w = img.data('neww');
				var h = img.data('newh');
				var fulloff = img.data("fxof");
				if (fulloff==undefined) fulloff=0;

				var fullyoff=img.data("fyof");
				if (img.data('fullwidthcentering')!="on" || fullyoff==undefined) fullyoff=0;

				var off=0;



				if (!visible)
					var off=0-opt.sloth;

				for (var i=0;i<opt.slots+2;i++)
					sh.append('<div class="slot" style="position:absolute;'+
												 'top:'+(fullyoff+(i*opt.sloth))+'px;'+
												 'left:'+(fulloff)+'px;'+
												 'overflow:hidden;'+
												 'width:'+w+'px;'+
												 'height:'+(opt.sloth)+'px"'+
												 '><div class="slotslide" style="position:absolute;'+
												 'top:'+(off)+'px;'+
												 'left:0px;width:'+w+'px;'+
												 'height:'+opt.sloth+'px;'+
												 'overflow:hidden;"><img style="position:absolute;'+
												 'background-color:'+bgcolor+';'+
												 'top:'+(0-(i*opt.sloth))+'px;'+
												 'left:0px;width:'+w+'px;'+
												 'height:'+h+'px" src="'+src+'"></div></div>');

		}


		///////////////////////
		// PREPARE THE SLIDE //
		//////////////////////
		function prepareOneSlideBox(slotholder,opt,visible) {

				var sh=slotholder;
				var img = sh.find('img')
				setSize(img,opt)
				var src = img.attr('src');
				var bgcolor=img.css('background-color');

				var w = img.data('neww');
				var h = img.data('newh');
				var fulloff = img.data("fxof");
				if (fulloff==undefined) fulloff=0;

				var fullyoff=img.data("fyof");
				if (img.data('fullwidthcentering')!="on" || fullyoff==undefined) fullyoff=0;



				var off=0;




				// SET THE MINIMAL SIZE OF A BOX
				var basicsize = 0;
				if (opt.sloth>opt.slotw)
					basicsize=opt.sloth
				else
					basicsize=opt.slotw;


				if (!visible) {
					var off=0-basicsize;
				}

				opt.slotw = basicsize;
				opt.sloth = basicsize;
				var x=0;
				var y=0;



				for (var j=0;j<opt.slots;j++) {

					y=0;
					for (var i=0;i<opt.slots;i++) 	{


						sh.append('<div class="slot" '+
								  'style="position:absolute;'+
											'top:'+(fullyoff+y)+'px;'+
											'left:'+(fulloff+x)+'px;'+
											'width:'+basicsize+'px;'+
											'height:'+basicsize+'px;'+
											'overflow:hidden;">'+

								  '<div class="slotslide" data-x="'+x+'" data-y="'+y+'" '+
								  'style="position:absolute;'+
											'top:'+(0)+'px;'+
											'left:'+(0)+'px;'+
											'width:'+basicsize+'px;'+
											'height:'+basicsize+'px;'+
											'overflow:hidden;">'+

								  '<img style="position:absolute;'+
											'top:'+(0-y)+'px;'+
											'left:'+(0-x)+'px;'+
											'width:'+w+'px;'+
											'height:'+h+'px'+
											'background-color:'+bgcolor+';"'+
								  'src="'+src+'"></div></div>');
						y=y+basicsize;
					}
					x=x+basicsize;
				}
		}





		///////////////////////
		//	REMOVE SLOTS	//
		/////////////////////
		function removeSlots(container,opt,time) {
			if (time==undefined)
				time==80

			setTimeout(function() {
				container.find('.slotholder .slot').each(function() {
					clearTimeout($(this).data('tout'));
					$(this).remove();
				});
				opt.transition = 0;
			},time);
		}


		////////////////////////
		//	CAPTION POSITION  //
		///////////////////////
		function setCaptionPositions(container,opt) {

			// FIND THE RIGHT CAPTIONS
			var actli = container.find('>li:eq('+opt.act+')');
			var nextli = container.find('>li:eq('+opt.next+')');

			// SET THE NEXT CAPTION AND REMOVE THE LAST CAPTION
			var nextcaption=nextli.find('.tp-caption');

			if (nextcaption.find('iframe')==0) {

				// MOVE THE CAPTIONS TO THE RIGHT POSITION
				if (nextcaption.hasClass('hcenter'))
					nextcaption.css({'height':opt.height+"px",'top':'0px','left':(opt.width/2 - nextcaption.outerWidth()/2)+'px'});
				else
					if (nextcaption.hasClass('vcenter'))
						nextcaption.css({'width':opt.width+"px",'left':'0px','top':(opt.height/2 - nextcaption.outerHeight()/2)+'px'});
			}
		}


		//////////////////////////////
		//                         //
		//	-	SWAP THE SLIDES -  //
		//                        //
		////////////////////////////
		function swapSlide(container,opt) {


			container.trigger('revolution.slide.onbeforeswap');


			opt.transition = 1;
			opt.videoplaying = false;

			try{
				var actli = container.find('>ul:first-child >li:eq('+opt.act+')');
			} catch(e) {
				var actli=container.find('>ul:first-child >li:eq(1)');
			}

			opt.lastslide=opt.act;

			var nextli = container.find('>ul:first-child >li:eq('+opt.next+')');

			var actsh = actli.find('.slotholder');
			var nextsh = nextli.find('.slotholder');
			actli.css({'visibility':'visible'});
			nextli.css({'visibility':'visible'});

			if (opt.ie) {
				if (nextli.data('transition')=="boxfade") nextli.data('transition',"boxslide");
				if (nextli.data('transition')=="slotfade-vertical") nextli.data('transition',"slotzoom-vertical");
				if (nextli.data('transition')=="slotfade-horizontal") nextli.data('transition',"slotzoom-horizontal");
			}


			// IF DELAY HAS BEEN SET VIA THE SLIDE, WE TAKE THE NEW VALUE, OTHER WAY THE OLD ONE...
			if (nextli.data('delay')!=undefined) {
						opt.cd=0;
						opt.delay=nextli.data('delay');
			} else {
				opt.delay=opt.origcd;
			}

			// RESET POSITION AND FADES OF LI'S
			actli.css({'left':'0px','top':'0px'});
			nextli.css({'left':'0px','top':'0px'});


			// IF THERE IS AN OTHER FIRST SLIDE START HAS BEED SELECTED
			if (nextli.data('differentissplayed') =='prepared') {
				nextli.data('differentissplayed','done');
				nextli.data('transition',nextli.data('savedtransition'));
				nextli.data('slotamount',nextli.data('savedslotamount'));
				nextli.data('masterspeed',nextli.data('savedmasterspeed'));
			}


			if (nextli.data('fstransition') != undefined && nextli.data('differentissplayed') !="done") {
				nextli.data('savedtransition',nextli.data('transition'));
				nextli.data('savedslotamount',nextli.data('slotamount'));
				nextli.data('savedmasterspeed',nextli.data('masterspeed'));

				nextli.data('transition',nextli.data('fstransition'));
				nextli.data('slotamount',nextli.data('fsslotamount'));
				nextli.data('masterspeed',nextli.data('fsmasterspeed'));

				nextli.data('differentissplayed','prepared');
			}

			///////////////////////////////////////
			// TRANSITION CHOOSE - RANDOM EFFECTS//
			///////////////////////////////////////
			var nexttrans = 0;




			if (nextli.data('transition')=="boxslide") nexttrans = 0
			else
				if (nextli.data('transition')=="boxfade") nexttrans = 1
			else
				if (nextli.data('transition')=="slotslide-horizontal") nexttrans = 2
			else
				if (nextli.data('transition')=="slotslide-vertical") nexttrans = 3
			else
				if (nextli.data('transition')=="curtain-1") nexttrans = 4
			else
				if (nextli.data('transition')=="curtain-2") nexttrans = 5
			else
				if (nextli.data('transition')=="curtain-3") nexttrans = 6
			else
				if (nextli.data('transition')=="slotzoom-horizontal") nexttrans = 7
			else
				if (nextli.data('transition')=="slotzoom-vertical")  nexttrans = 8
			else
				if (nextli.data('transition')=="slotfade-horizontal")  nexttrans = 9
			else
				if (nextli.data('transition')=="slotfade-vertical") nexttrans = 10
			else
				if (nextli.data('transition')=="fade") nexttrans = 11
			else
				if (nextli.data('transition')=="slideleft")  nexttrans = 12
			else
				if (nextli.data('transition')=="slideup") nexttrans = 13
			else
				if (nextli.data('transition')=="slidedown") nexttrans = 14
			else
				if (nextli.data('transition')=="slideright") nexttrans = 15;
			else
				if (nextli.data('transition')=="papercut") nexttrans = 16;
			else
				if (nextli.data('transition')=="3dcurtain-horizontal") nexttrans = 17;
			else
				if (nextli.data('transition')=="3dcurtain-vertical") nexttrans = 18;
			else
				if (nextli.data('transition')=="cubic" || nextli.data('transition')=="cube") nexttrans = 19;
			else
				if (nextli.data('transition')=="flyin") nexttrans = 20;
			else
				if (nextli.data('transition')=="turnoff") nexttrans = 21;
			else {
				nexttrans=Math.round(Math.random()*21);
				nextli.data('slotamount',Math.round(Math.random()*12+4));
			}

			if (nextli.data('transition')=="random-static")   {
						nexttrans=Math.round(Math.random()*16);
						if (nexttrans>15) nexttrans=15;
						if (nexttrans<0) nexttrans=0;
			}

			if (nextli.data('transition')=="random-premium")   {
						nexttrans=Math.round(Math.random()*6+16);
						if (nexttrans>21) nexttrans=21;
						if (nexttrans<16) nexttrans=16;
			}



		    var direction=-1;
			if (opt.leftarrowpressed==1 || opt.act>opt.next) direction=1;

			if (nextli.data('transition')=="slidehorizontal") {
						nexttrans = 12
					if (opt.leftarrowpressed==1)
						nexttrans = 15
				}

			if (nextli.data('transition')=="slidevertical") {
						nexttrans = 13
					if (opt.leftarrowpressed==1)
						nexttrans = 14
				}

			opt.leftarrowpressed=0;


		
			if (nexttrans>21) nexttrans = 21;
			if (nexttrans<0) nexttrans = 0;

			if ((opt.ie || opt.ie9) && nexttrans >18) {
					nexttrans=Math.round(Math.random()*16);
					nextli.data('slotamount',Math.round(Math.random()*12+4));
			};
			if (opt.ie && (nexttrans==17 || nexttrans==16 || nexttrans==2 || nexttrans==3 || nexttrans==9 || nexttrans==10 )) nexttrans=Math.round(Math.random()*3+12);


			if (opt.ie9 && (nexttrans==3)) nexttrans = 4;


			
			
			//$('body').find('.debug').html("Transition:"+nextli.data('transition')+"  id:"+nexttrans);

			// DEFINE THE MASTERSPEED FOR THE SLIDE //
			var masterspeed=300;
			if (nextli.data('masterspeed')!=undefined && nextli.data('masterspeed')>99 && nextli.data('masterspeed')<4001)
				masterspeed = nextli.data('masterspeed');



			/////////////////////////////////////////////
			// SET THE BULLETS SELECTED OR UNSELECTED  //
			/////////////////////////////////////////////


			container.parent().find(".bullet").each(function() {
				var bul = $(this);
				bul.removeClass("selected");


				if (opt.navigationArrows=="withbullet" || opt.navigationArrows=="nexttobullets") {
					if (bul.index()-1 == opt.next) bul.addClass('selected');

				} else {

					if (bul.index() == opt.next)  bul.addClass('selected');

				}
			});


			//////////////////////////////////////////////////////////////////
			// 		SET THE NEXT CAPTION AND REMOVE THE LAST CAPTION		//
			//////////////////////////////////////////////////////////////////

					container.find('>li').each(function() {
						var li = $(this);
						if (li.index!=opt.act && li.index!=opt.next) li.css({'z-index':16});
					});

					actli.css({'z-index':18});
					nextli.css({'z-index':20});
					nextli.css({'opacity':0});


			///////////////////////////
			//	ANIMATE THE CAPTIONS //
			///////////////////////////
			removeTheCaptions(actli,opt);
			animateTheCaptions(nextli, opt);




			/////////////////////////////////////////////
			//	SET THE ACTUAL AMOUNT OF SLIDES !!     //
			//  SET A RANDOM AMOUNT OF SLOTS          //
			///////////////////////////////////////////
						if (nextli.data('slotamount')==undefined || nextli.data('slotamount')<1) {
							opt.slots=Math.round(Math.random()*12+4);
							if (nextli.data('transition')=="boxslide")
								opt.slots=Math.round(Math.random()*6+3);
						 } else {
							opt.slots=nextli.data('slotamount');

						}

			/////////////////////////////////////////////
			//	SET THE ACTUAL AMOUNT OF SLIDES !!     //
			//  SET A RANDOM AMOUNT OF SLOTS          //
			///////////////////////////////////////////
						if (nextli.data('rotate')==undefined)
							opt.rotate = 0
						 else
							if (nextli.data('rotate')==999)
								opt.rotate=Math.round(Math.random()*360);
							 else
							    opt.rotate=nextli.data('rotate');
						if (!$.support.transition  || opt.ie || opt.ie9) opt.rotate=0;



			//////////////////////////////
			//	FIRST START 			//
			//////////////////////////////

			if (opt.firststart==1) {
					actli.css({'opacity':0});
					opt.firststart=0;
			}

			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==0) {								// BOXSLIDE

						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;

						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideBox(actsh,opt,true);
						prepareOneSlideBox(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							if (opt.ie9)
								ss.transition({top:(0-opt.sloth),left:(0-opt.slotw)},0);
							else
								ss.transition({top:(0-opt.sloth),left:(0-opt.slotw), rotate:opt.rotate},0);
							setTimeout(function() {
											ss.transition({top:0, left:0, scale:1, rotate:0},masterspeed*1.5,function() {

																	if (j==(opt.slots*opt.slots)-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																	moveSelectedThumb(container);

																	}
															});
							},j*15);
						});
			}



			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==1) {


						if (opt.slots>5) opt.slots=5;
						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						//prepareOneSlideBox(actsh,opt,true);
						prepareOneSlideBox(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT

						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.css({'opacity':0});
							ss.find('img').css({'opacity':0});
							if (opt.ie9)
								ss.find('img').transition({'top':(Math.random()*opt.slotw-opt.slotw)+"px",'left':(Math.random()*opt.slotw-opt.slotw)+"px"},0);
							else
								ss.find('img').transition({'top':(Math.random()*opt.slotw-opt.slotw)+"px",'left':(Math.random()*opt.slotw-opt.slotw)+"px", rotate:opt.rotate},0);

							var rand=Math.random()*1000+(masterspeed + 200);
							if (j==(opt.slots*opt.slots)-1) rand=1500;

									ss.find('img').transition({'opacity':1,'top':(0-ss.data('y'))+"px",'left':(0-ss.data('x'))+'px', rotate:0},rand);
									ss.transition({'opacity':1},rand,function() {
															if (j==(opt.slots*opt.slots)-1) {
																removeSlots(container,opt);
																nextsh.find('.defaultimg').css({'opacity':1});
																if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																opt.act=opt.next;

																moveSelectedThumb(container);
															}

									});


						});
			}


			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==2) {


						masterspeed = masterspeed + 200;

						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL OLD SLOTS SHOULD BE SLIDED TO THE RIGHT
						actsh.find('.slotslide').each(function() {
							var ss=$(this);


									//ss.animate({'left':opt.slotw+'px'},{duration:masterspeed,queue:false,complete:function() {
									ss.transit({'left':opt.slotw+'px',rotate:(0-opt.rotate)},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
															opt.act=opt.next;
															moveSelectedThumb(container);

									});

						});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function() {
							var ss=$(this);
							if (opt.ie9)
								ss.transit({'left':(0-opt.slotw)+"px"},0);
							else
								ss.transit({'left':(0-opt.slotw)+"px",rotate:opt.rotate},0);

									ss.transit({'left':'0px',rotate:0},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
															if (opt.ie) actsh.find('.defaultimg').css({'opacity':1});
															opt.act=opt.next;

																		moveSelectedThumb(container);

									});

						});
			}



			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==3) {


						masterspeed = masterspeed + 200;
						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideV(actsh,opt,true);
						prepareOneSlideV(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});

						// ALL OLD SLOTS SHOULD BE SLIDED TO THE RIGHT
						actsh.find('.slotslide').each(function() {
							var ss=$(this);

									ss.transit({'top':opt.sloth+'px',rotate:opt.rotate},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
															opt.act=opt.next;
															moveSelectedThumb(container);

									});

						});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function() {
							var ss=$(this);
								if (opt.ie9)
									ss.transit({'top':(0-opt.sloth)+"px"},0);
								else
									ss.transit({'top':(0-opt.sloth)+"px",rotate:opt.rotate},0);
								ss.transit({'top':'0px',rotate:0},masterspeed,function() {
													removeSlots(container,opt);
													nextsh.find('.defaultimg').css({'opacity':1});
													if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
													opt.act=opt.next;
													moveSelectedThumb(container);

								});

						});
			}



			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==4) {



						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,true);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						actsh.find('.slotslide').each(function(i) {
							var ss=$(this);

							ss.transit({'top':(0+(opt.height))+"px",'opacity':1,rotate:opt.rotate},masterspeed+(i*(70-opt.slots)));
						});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
								if (opt.ie9)
										ss.transition({'top':(0-(opt.height))+"px",'opacity':0},0);
									else
										ss.transition({'top':(0-(opt.height))+"px",'opacity':0,rotate:opt.rotate},0);

									ss.transition({'top':'0px','opacity':1,rotate:0},masterspeed+(i*(70-opt.slots)),function() {
															if (i==opt.slots-1) {
																removeSlots(container,opt);
																nextsh.find('.defaultimg').css({'opacity':1});
																if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																opt.act=opt.next;
																moveSelectedThumb(container);
															}

									});

						});
			}


			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==5) {



						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,true);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						actsh.find('.defaultimg').css({'opacity':0});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						actsh.find('.slotslide').each(function(i) {
							var ss=$(this);

									ss.transition({'top':(0+(opt.height))+"px",'opacity':1,rotate:opt.rotate},masterspeed+((opt.slots-i)*(70-opt.slots)));

						});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
									if (opt.ie9)
										ss.transition({'top':(0-(opt.height))+"px",'opacity':0},0);
									else
										ss.transition({'top':(0-(opt.height))+"px",'opacity':0,rotate:opt.rotate},0);

									ss.transition({'top':'0px','opacity':1,rotate:0},masterspeed+((opt.slots-i)*(70-opt.slots)),function() {
															if (i==0) {
																removeSlots(container,opt);
																nextsh.find('.defaultimg').css({'opacity':1});
																if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																opt.act=opt.next;
																moveSelectedThumb(container);
															}

									});

						});
			}


			/////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION I.  //
			////////////////////////////////////
			if (nexttrans==6) {



						nextli.css({'opacity':1});
						if (opt.slots<2) opt.slots=2;
						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,true);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						actsh.find('.defaultimg').css({'opacity':0});


						actsh.find('.slotslide').each(function(i) {
							var ss=$(this);

							if (i<opt.slots/2)
								var tempo = (i+2)*60;
							else
								var tempo = (2+opt.slots-i)*60;


									ss.transition({'top':(0+(opt.height))+"px",'opacity':1},masterspeed+tempo);

						});

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
							if (opt.ie9)
								ss.transition({'top':(0-(opt.height))+"px",'opacity':0},0);
							else
								ss.transition({'top':(0-(opt.height))+"px",'opacity':0,rotate:opt.rotate},0);
							if (i<opt.slots/2)
								var tempo = (i+2)*60;
							else
								var tempo = (2+opt.slots-i)*60;


									ss.transition({'top':'0px','opacity':1,rotate:0},masterspeed+tempo,function() {
															if (i==Math.round(opt.slots/2)) {
																removeSlots(container,opt);
																nextsh.find('.defaultimg').css({'opacity':1});
																if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																opt.act=opt.next;
																moveSelectedThumb(container);
															}

									});

						});
			}


			////////////////////////////////////
			// THE SLOTSZOOM - TRANSITION II. //
			////////////////////////////////////
			if (nexttrans==7) {

						masterspeed = masterspeed * 3;
						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,true);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});

						// ALL OLD SLOTS SHOULD BE SLIDED TO THE RIGHT
						actsh.find('.slotslide').each(function() {
							var ss=$(this).find('img');

									ss.transition({'left':(0-opt.slotw/2)+'px',
												   'top':(0-opt.height/2)+'px',
												   'width':(opt.slotw*2)+"px",
												   'height':(opt.height*2)+"px",
												   opacity:0,
												   rotate:opt.rotate
													},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
															opt.act = opt.next;
															moveSelectedThumb(container);
													});

						});

/						//////////////////////////////////////////////////////////////
						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT //
						///////////////////////////////////////////////////////////////
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this).find('img');

									if (opt.ie9)
										ss.transition({'left':(0)+'px','top':(0)+'px',opacity:0},0);
									else
										ss.transition({'left':(0)+'px','top':(0)+'px',opacity:0,rotate:opt.rotate},0);
									ss.transition({'left':(0-i*opt.slotw)+'px',
												   'top':(0)+'px',
												   'width':(nextsh.find('.defaultimg').data('neww'))+"px",
												   'height':(nextsh.find('.defaultimg').data('newh'))+"px",
												   opacity:1,rotate:0

													},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
															opt.act = opt.next;
															moveSelectedThumb(container);
													});


						});
			}




			////////////////////////////////////
			// THE SLOTSZOOM - TRANSITION II. //
			////////////////////////////////////
			if (nexttrans==8) {

						masterspeed = masterspeed * 3;
						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideV(actsh,opt,true);
						prepareOneSlideV(nextsh,opt,true);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});

						// ALL OLD SLOTS SHOULD BE SLIDED TO THE RIGHT
						actsh.find('.slotslide').each(function() {
							var ss=$(this).find('img');

									ss.transition({'left':(0-opt.width/2)+'px',
												   'top':(0-opt.sloth/2)+'px',
												   'width':(opt.width*2)+"px",
												   'height':(opt.sloth*2)+"px",
												   opacity:0,rotate:opt.rotate
													},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});

															opt.act = opt.next;
															moveSelectedThumb(container);
													});

						});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT //
						///////////////////////////////////////////////////////////////
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this).find('img');
									if (opt.ie9)
										ss.transition({'left':(0)+'px','top':(0)+'px',opacity:0},0);
									else
										ss.transition({'left':(0)+'px','top':(0)+'px',opacity:0,rotate:opt.rotate},0);
									ss.transition({'left':(0)+'px',
												   'top':(0-i*opt.sloth)+'px',
												   'width':(nextsh.find('.defaultimg').data('neww'))+"px",
												   'height':(nextsh.find('.defaultimg').data('newh'))+"px",
												   opacity:1,rotate:0
													},masterspeed,function() {
															removeSlots(container,opt);
															nextsh.find('.defaultimg').css({'opacity':1});
															if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});

															opt.act = opt.next;
															moveSelectedThumb(container);
													});

						});
			}


			////////////////////////////////////////
			// THE SLOTSFADE - TRANSITION III.   //
			//////////////////////////////////////
			if (nexttrans==9) {



						nextli.css({'opacity':1});

						opt.slots = opt.width/20;

						prepareOneSlide(nextsh,opt,true);


						//actsh.find('.defaultimg').css({'opacity':0});
						nextsh.find('.defaultimg').css({'opacity':0});

						var ssamount=0;
						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
							ssamount++;
							ss.transition({'opacity':0,x:0,y:0},0);
							ss.data('tout',setTimeout(function() {
											ss.transition({x:0,y:0,'opacity':1},masterspeed);

											},i*4)
									);

						});

						//nextsh.find('.defaultimg').transition({'opacity':1},(masterspeed+(ssamount*4)));

						setTimeout(function() {
									removeSlots(container,opt);
									nextsh.find('.defaultimg').css({'opacity':1});
									if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
									if (opt.ie) actsh.find('.defaultimg').css({'opacity':1});

									opt.act = opt.next;
									moveSelectedThumb(container);
							},(masterspeed+(ssamount*4)));
			}




			////////////////////////////////////////
			// THE SLOTSFADE - TRANSITION III.   //
			//////////////////////////////////////
			if (nexttrans==10) {



						nextli.css({'opacity':1});

						opt.slots = opt.height/20;

						prepareOneSlideV(nextsh,opt,true);


						//actsh.find('.defaultimg').css({'opacity':0});
						nextsh.find('.defaultimg').css({'opacity':0});

						var ssamount=0;
						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
							ssamount++;
							ss.transition({'opacity':0,x:0,y:0},0);
							ss.data('tout',setTimeout(function() {
											ss.transition({x:0,y:0,'opacity':1},masterspeed);

											},i*4)
									);

						});

						//nextsh.find('.defaultimg').transition({'opacity':1},(masterspeed+(ssamount*4)));

						setTimeout(function() {
									removeSlots(container,opt);
									nextsh.find('.defaultimg').css({'opacity':1});
									if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
									if (opt.ie) actsh.find('.defaultimg').css({'opacity':1});

									opt.act = opt.next;
									moveSelectedThumb(container);
							},(masterspeed+(ssamount*4)));
			}


			///////////////////////////
			// SIMPLE FADE ANIMATION //
			///////////////////////////

			if (nexttrans==11) {



						nextli.css({'opacity':1});

						opt.slots = 1;

						prepareOneSlide(nextsh,opt,true);


						//actsh.find('.defaultimg').css({'opacity':0});
						nextsh.find('.defaultimg').css({'opacity':0,'position':'relative'});

						var ssamount=0;
						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT

						nextsh.find('.slotslide').each(function(i) {
							var ss=$(this);
							ssamount++;

							if (opt.ie9 ||opt.ie) {
								if (opt.ie) nextli.css({'opacity':'0'});
								ss.css({'opacity':0});

							} else
								ss.transition({'opacity':0,rotate:opt.rotate},0);


							setTimeout(function() {
								if (opt.ie9 ||opt.ie) {
									if (opt.ie)
										nextli.animate({'opacity':1},{duration:masterspeed});
									 else
									 	ss.transition({'opacity':1},masterspeed);

								} else {
									ss.transition({'opacity':1,rotate:0},masterspeed);
								}
							},10);
						});

						setTimeout(function() {
									removeSlots(container,opt);
									nextsh.find('.defaultimg').css({'opacity':1});
									if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
									if (opt.ie) actsh.find('.defaultimg').css({'opacity':1});

									opt.act = opt.next;
									moveSelectedThumb(container);
							},masterspeed+15);
			}






			if (nexttrans==12 || nexttrans==13 || nexttrans==14 || nexttrans==15) {

						masterspeed = masterspeed * 3;
						nextli.css({'opacity':1});

						opt.slots = 1;

						prepareOneSlide(nextsh,opt,true);
						prepareOneSlide(actsh,opt,true);


						actsh.find('.defaultimg').css({'opacity':0});
						nextsh.find('.defaultimg').css({'opacity':0});

						var oow = opt.width;
						var ooh = opt.height;
						if (opt.fullWidth=="on") {
							oow=opt.container.parent().width();
							ooh=opt.container.parent().height();

						}

						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT
						var ssn=nextsh.find('.slotslide')

						if (nexttrans==12)
							if (opt.ie9) {
								ssn.transition({'left':oow+"px"},0);

							 } else {
								ssn.transition({'left':oow+"px",rotate:opt.rotate},0);

							}
						else
							if (nexttrans==15)
								if (opt.ie9)
									ssn.transition({'left':(0-opt.width)+"px"},0);
								else
									ssn.transition({'left':(0-opt.width)+"px",rotate:opt.rotate},0);
							else
								if (nexttrans==13)
									if (opt.ie9)
										ssn.transition({'top':(ooh)+"px"},0);
									else
										ssn.transition({'top':(ooh)+"px",rotate:opt.rotate},0);
								else
									if (nexttrans==14)
										if (opt.ie9)
											ssn.transition({'top':(0-opt.height)+"px"},0);
										else
											ssn.transition({'top':(0-opt.height)+"px",rotate:opt.rotate},0);


										ssn.transition({'left':'0px','top':'0px',opacity:1,rotate:0},masterspeed,function() {


														removeSlots(container,opt,0);
														if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
														nextsh.find('.defaultimg').css({'opacity':1});
														opt.act = opt.next;
														moveSelectedThumb(container);
												});



						var ssa=actsh.find('.slotslide');

								if (nexttrans==12)
									ssa.transition({'left':(0-oow)+'px',opacity:1,rotate:0},masterspeed);
								else
									if (nexttrans==15)
										ssa.transition({'left':(oow)+'px',opacity:1,rotate:0},masterspeed);
									else
										if (nexttrans==13)
											ssa.transition({'top':(0-ooh)+'px',opacity:1,rotate:0},masterspeed);
										else
											if (nexttrans==14)
												ssa.transition({'top':(ooh)+'px',opacity:1,rotate:0},masterspeed);



			}


			//////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XVI.  //
			//////////////////////////////////////
			if (nexttrans==16) {						// PAPERCUT

					actli.css({'position':'absolute','z-index':20});
					nextli.css({'position':'absolute','z-index':15});
					// PREPARE THE CUTS
					actli.wrapInner('<div class="tp-half-one"></div>');
					actli.find('.tp-half-one').clone(true).appendTo(actli).addClass("tp-half-two");
					actli.find('.tp-half-two').removeClass('tp-half-one');
					actli.find('.tp-half-two').wrapInner('<div class="tp-offset"></div>');

					// ANIMATE THE CUTS
					var img=actli.find('.defaultimg');
					if (img.length>0 && img.data("fullwidthcentering")=="on") {
						var imgh=img.height()/2;
						var to=img.position().top;
					} else {

						var imgh=opt.height/2;
						var to=0;
					}
					actli.find('.tp-half-one').css({'width':opt.width+"px",'height':(to+imgh)+"px",'overflow':'hidden','position':'absolute','top':'0px','left':'0px'});
					actli.find('.tp-half-two').css({'width':opt.width+"px",'height':(to+imgh)+"px",'overflow':'hidden','position':'absolute','top':(to+imgh)+'px','left':'0px'});
					actli.find('.tp-half-two .tp-offset').css({'position':'absolute','top':(0-imgh-to)+'px','left':'0px'});


					// Delegate .transition() calls to .animate()
					// if the browser can't do CSS transitions.
					if (!$.support.transition) {

						actli.find('.tp-half-one').animate({'opacity':0,'top':(0-opt.height/2)+"px"},{duration: 500,queue:false});
						actli.find('.tp-half-two').animate({'opacity':0,'top':(opt.height)+"px"},{duration: 500,queue:false});
					} else {
						var ro1=Math.round(Math.random()*40-20);
						var ro2=Math.round(Math.random()*40-20);
						var sc1=Math.random()*1+1;
						var sc2=Math.random()*1+1;
						actli.find('.tp-half-one').transition({opacity:1, scale:sc1, rotate:ro1,y:(0-opt.height/1.4)+"px"},800,'in');
						actli.find('.tp-half-two').transition({opacity:1, scale:sc2, rotate:ro2,y:(0+opt.height/1.4)+"px"},800,'in');

						if (actli.html()!=null) nextli.transition({scale:0.8,x:opt.width*0.1, y:opt.height*0.1, rotate:ro1},0).transition({rotate:0, scale:1,x:0,y:0},600,'snap');
					}
					nextsh.find('.defaultimg').css({'opacity':1});
					setTimeout(function() {


								// CLEAN UP BEFORE WE START
								actli.css({'position':'absolute','z-index':18});
								nextli.css({'position':'absolute','z-index':20});
								nextsh.find('.defaultimg').css({'opacity':1});
								actsh.find('.defaultimg').css({'opacity':0});
								if (actli.find('.tp-half-one').length>0)  {
									actli.find('.tp-half-one >img, .tp-half-one >div').unwrap();

								}
								actli.find('.tp-half-two').remove();
								opt.transition = 0;
								opt.act = opt.next;

					},800);
					nextli.css({'opacity':1});

			}

			////////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XVII.  //
			///////////////////////////////////////
			if (nexttrans==17) {								// 3D CURTAIN HORIZONTAL

						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;

						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideV(actsh,opt,true);
						prepareOneSlideV(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.transition({ opacity:0, rotateY:350 ,rotateX:40, perspective:'1400px'},0);
							setTimeout(function() {
											ss.transition({opacity:1, top:0, left:0, scale:1, perspective:'150px', rotate:0,rotateY:0, rotateX:0},masterspeed*2,function() {

																	if (j==opt.slots-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																		moveSelectedThumb(container);

																	}
															});
							},j*100);
						});
			}



			////////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XVIII.  //
			///////////////////////////////////////
			if (nexttrans==18) {								// 3D CURTAIN VERTICAL

						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;

						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.transition({  rotateX:10 ,rotateY:310, perspective:'1400px', rotate:0,opacity:0},0);
							setTimeout(function() {
											ss.transition({top:0, left:0, scale:1, perspective:'150px', rotate:0,rotateY:0, rotateX:0,opacity:1},masterspeed*2,function() {

																	if (j==opt.slots-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																		moveSelectedThumb(container);

																	}
															});
							},j*100);
						});
			}

			////////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XIX.  //
			///////////////////////////////////////
			if (nexttrans==19) {								// CUBIC VERTICAL
						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;
						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlide(actsh,opt,true);
						prepareOneSlide(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});
						var chix=nextli.css('z-index');
						var chix2=actli.css('z-index');

						//actli.css({'z-index':22});



						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							//ss.css({'overflow':'visible'});
							ss.parent().css({'overflow':'visible'});
							ss.css({'background':'#333'});
							if (direction==1)
								ss.transition({  opacity:0,left:0,top:opt.height/2,rotate3d:'1, 0, 0, -90deg '},0);
							else
								ss.transition({ opacity:0,left:0,top:0-opt.height/2,rotate3d:'1, 0, 0, 90deg '},0);

							setTimeout(function() {

											ss.transition({opacity:1,top:0,perspective:opt.height*2,rotate3d:' 1, 0, 0, 0deg '},masterspeed*2,function() {

																	if (j==opt.slots-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																		moveSelectedThumb(container);

																	}
															});
							},j*150);

						});

						actsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.parent().css({'overflow':'visible'});
							ss.css({'background':'#333'});
							ss.transition({ top:0,rotate3d: '1, 0, 0, 0deg'},0);
							actsh.find('.defaultimg').css({'opacity':0});
							setTimeout(function() {
											if (direction==1)
												ss.transition({opacity:0.6,left:0,perspective: opt.height*2,top:0-opt.height/2,rotate3d: '1, 0, 0, 90deg'},masterspeed*2,function() {});
											else
												ss.transition({opacity:0.6,left:0,perspective: opt.height*2,top:(0+opt.height/2),rotate3d: '1, 0, 0, -90deg'},masterspeed*2,function() {});
							},j*150);
						});
			}

			////////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XX.  //
			///////////////////////////////////////
			if (nexttrans==20) {								// FLYIN
						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;



						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideV(actsh,opt,true);
						prepareOneSlideV(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.parent().css({'overflow':'visible'});

							if (direction==1)
								ss.transition({ scale:0.8,top:0,left:0-opt.width,rotate3d: '2, 5, 0, 110deg'},0);
							else
								ss.transition({ scale:0.8,top:0,left:0+opt.width,rotate3d: '2, 5, 0, -110deg'},0);
							setTimeout(function() {
											ss.transition({ scale:0.8,left:0,perspective: opt.width,rotate3d: '1, 5, 0, 0deg'},masterspeed*2,'ease').transition({scale:1},200,'out',function() {

																	if (j==opt.slots-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																		moveSelectedThumb(container);

																	}
															});
							},j*100);
						});

						actsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.transition({ scale:0.5,left:0,rotate3d: '1, 5, 0, 5deg'},300,'in-out');
							actsh.find('.defaultimg').css({'opacity':0});
							setTimeout(function() {
											if (direction==1)
												ss.transition({top:0,left:opt.width/2,perspective: opt.width,rotate3d: '0, -3, 0, 70deg',opacity:0},masterspeed*2,'out',function() {});
											else
												ss.transition({top:0,left:0-opt.width/2,perspective: opt.width,rotate3d: '0, -3, 0, -70deg',opacity:0},masterspeed*2,'out',function() {});
							},j*100);
						});
			}


			////////////////////////////////////////
			// THE SLOTSLIDE - TRANSITION XX.  //
			///////////////////////////////////////
			if (nexttrans==21) {								// TURNOFF
						masterspeed = masterspeed + 100;
						if (opt.slots>10) opt.slots=10;

						nextli.css({'opacity':1});

						// PREPARE THE SLOTS HERE
						prepareOneSlideV(actsh,opt,true);
						prepareOneSlideV(nextsh,opt,false);

						//SET DEFAULT IMG UNVISIBLE
						nextsh.find('.defaultimg').css({'opacity':0});
						//actsh.find('.defaultimg').css({'opacity':0});


						// ALL NEW SLOTS SHOULD BE SLIDED FROM THE LEFT TO THE RIGHT


						nextsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							if (direction==1)
								ss.transition({ top:0,left:0-(opt.width),rotate3d: '0, 1, 0, 90deg'},0);
							else
								ss.transition({ top:0,left:0+(opt.width),rotate3d: '0, 1, 0, -90deg'},0);
							setTimeout(function() {
											ss.transition({left:0,perspective: opt.width*2,rotate3d: '0, 0, 0, 0deg'},masterspeed*2,function() {

																	if (j==opt.slots-1) {
																		removeSlots(container,opt);
																		nextsh.find('.defaultimg').css({'opacity':1});

																		if (nextli.index()!=actli.index()) actsh.find('.defaultimg').css({'opacity':0});
																		opt.act=opt.next;
																		moveSelectedThumb(container);

																	}
															});
							},j*100);
						});

						actsh.find('.slotslide').each(function(j) {
							var ss=$(this);
							ss.transition({ left:0,rotate3d: '0, 0, 0, 0deg'},0);
							actsh.find('.defaultimg').css({'opacity':0});
							setTimeout(function() {
										if (direction==1)
											ss.transition({top:0,left:(opt.width/2),perspective: opt.width,rotate3d: '0, 1, 0, -90deg'},masterspeed*1.5,function() {});
										else
											ss.transition({top:0,left:(0-opt.width/2),perspective: opt.width,rotate3d: '0, 1, 0, +90deg'},masterspeed*1.5,function() {});

							},j*100);
						});
			}


			var data={};
			data.slideIndex=opt.next+1;
			container.trigger('revolution.slide.onchange',data);
			setTimeout(function() { container.trigger('revolution.slide.onafterswap'); },masterspeed);
			container.trigger('revolution.slide.onvideostop');


		}




				function onYouTubePlayerAPIReady() {

							}


				//////////////////////////////////////////
				// CHANG THE YOUTUBE PLAYER STATE HERE //
				////////////////////////////////////////
				 function onPlayerStateChange(event) {
					if (event.data == YT.PlayerState.PLAYING) {

						var bt = $('body').find('.tp-bannertimer');
						var opt = bt.data('opt');
						bt.stop();
						opt.videoplaying=true;
						opt.videostartednow=1;

					} else {

						var bt = $('body').find('.tp-bannertimer');
						var opt = bt.data('opt');
						if (opt.conthover==0)
							bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
						opt.videoplaying=false;
						opt.videostoppednow=1;
					}
				  }

				  ///////////////////////////////
				  //	YOUTUBE VIDEO AUTOPLAY //
				  ///////////////////////////////
				   function onPlayerReady(event) {
						event.target.playVideo();
					}

				 ////////////////////////
				// VIMEO ADD EVENT /////
				////////////////////////
				function addEvent(element, eventName, callback) {

							if (element.addEventListener) {

								element.addEventListener(eventName, callback, false);
							}
							else {

								element.attachEvent(eventName, callback, false);
							}


						}

				//////////////////////////////////////////
				// CHANG THE YOUTUBE PLAYER STATE HERE //
				////////////////////////////////////////
				  function vimeoready(player_id) {

						var froogaloop = $f(player_id);

						//$('#debug').html($('#debug').html()+" <br>Frooga Func"+Math.round(Math.random()*100));

						froogaloop.addEvent('ready', function(data) {
								//$('#debug').html($('#debug').html()+" <br>Ready"+Math.round(Math.random()*100));
								froogaloop.addEvent('play', function(data) {
									//$('#debug').html($('#debug').html()+" <br>Play"+Math.round(Math.random()*100));

									var bt = $('body').find('.tp-bannertimer');
									var opt = bt.data('opt');
									bt.stop();
									opt.videoplaying=true;
								});

								froogaloop.addEvent('finish', function(data) {
										var bt = $('body').find('.tp-bannertimer');
										var opt = bt.data('opt');
										if (opt.conthover==0)
											bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
										opt.videoplaying=false;
										opt.videostartednow=1;
								});

								froogaloop.addEvent('pause', function(data) {
										var bt = $('body').find('.tp-bannertimer');
										var opt = bt.data('opt');
										if (opt.conthover==0)
											bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
										opt.videoplaying=false;
										opt.videostoppednow=1;
								});
						});




					}


					function vimeoready_auto(player_id) {

						var froogaloop = $f(player_id);


						froogaloop.addEvent('ready', function(data) {
							froogaloop.api('play');
						});

						froogaloop.addEvent('play', function(data) {
							var bt = $('body').find('.tp-bannertimer');
							var opt = bt.data('opt');
							bt.stop();
							opt.videoplaying=true;
						});

						froogaloop.addEvent('finish', function(data) {
								var bt = $('body').find('.tp-bannertimer');
								var opt = bt.data('opt');
								if (opt.conthover==0)
									bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
								opt.videoplaying=false;
								opt.videostartednow=1;
						});

						froogaloop.addEvent('pause', function(data) {
								var bt = $('body').find('.tp-bannertimer');
								var opt = bt.data('opt');
								if (opt.conthover==0)
									bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
								opt.videoplaying=false;
								opt.videostoppednow=1;
						});


					}




				////////////////////////
				// SHOW THE CAPTION  //
				///////////////////////
				function animateTheCaptions(nextli, opt,actli) {


						//if ($("body").find('#debug').length==0)
						//		$("body").append('<div id="debug" style="background:#000;z-index:1000;position:fixed;top:5px;left:5px;width:100px;height:500px;color:#fff;font-size:10px;font-family:Arial;"</div>');


						var offsetx=0;
						nextli.find('.tp-caption').each(function(i) {

								offsetx = opt.width/2 - opt.startwidth/2;
								
								
								if (opt.bh>1) {
									opt.bw=1;
									opt.bh=1;
								}

								if (opt.bw>1) {
									opt.bw=1;
									opt.bh=1;
								}

								var xbw = opt.bw;
								var xbh = opt.bh;

								
								var nextcaption=nextli.find('.tp-caption:eq('+i+')');

								var handlecaption=0;

								// HIDE CAPTION IF RESOLUTION IS TOO LOW
								if (opt.width<opt.hideCaptionAtLimit && nextcaption.data('captionhidden')=="on") {
									nextcaption.addClass("tp-hidden-caption")
									handlecaption=1;
								} else {
									if (opt.width<opt.hideAllCaptionAtLilmit)	{
										nextcaption.addClass("tp-hidden-caption")
										handlecaption=1;
									} else {
										nextcaption.removeClass("tp-hidden-caption")
									}
								}


								

								nextcaption.stop(true,true);
								if (handlecaption==0) {
											if (nextcaption.data('linktoslide')!=undefined) {
												nextcaption.css({'cursor':'pointer'});
												if (nextcaption.data('linktoslide')!="no") {
													nextcaption.click(function() {
														var nextcaption=$(this);
														var dir = nextcaption.data('linktoslide');
														if (dir!="next" && dir!="prev") {
															opt.container.data('showus',dir);
															opt.container.parent().find('.tp-rightarrow').click();
														} else
															if (dir=="next")
																opt.container.parent().find('.tp-rightarrow').click();
														else
															if (dir=="prev")
																opt.container.parent().find('.tp-leftarrow').click();
													});
												}
											}


											if (nextcaption.hasClass("coloredbg")) offsetx=0;
											if (offsetx<0) offsetx=0;

											var offsety = 0; //opt.height/2 - (opt.startheight*xbh)/2;

											clearTimeout(nextcaption.data('timer'));
											clearTimeout(nextcaption.data('timer-end'));



											// YOUTUBE AND VIMEO LISTENRES INITIALISATION

											var frameID = "iframe"+Math.round(Math.random()*1000+1);

											if (nextcaption.find('iframe').length>0) {

											  nextcaption.find('iframe').each(function() {
												var ifr=$(this);

												if (ifr.attr('src').toLowerCase().indexOf('youtube')>=0) {
														 if (!ifr.hasClass("HasListener")) {
															try {
																ifr.attr('id',frameID);

																var player;
																if (nextcaption.data('autoplay')==true)
																	player = new YT.Player(frameID, {
																		events: {
																			"onStateChange": onPlayerStateChange,
																			'onReady': onPlayerReady
																		}
																	});
																else
																	player = new YT.Player(frameID, {
																		events: {
																			"onStateChange": onPlayerStateChange
																		}
																	});
																ifr.addClass("HasListener");

																nextcaption.data('player',player);

															} catch(e) {}
													 } else {
														if (nextcaption.data('autoplay')==true) {
																var player=nextcaption.data('player');
																player.playVideo();
														}
													 }

												} else {
													if (ifr.attr('src').toLowerCase().indexOf('vimeo')>=0) {

														   if (!ifr.hasClass("HasListener")) {
																ifr.addClass("HasListener");
																ifr.attr('id',frameID);
																var isrc = ifr.attr('src');
																var queryParameters = {}, queryString = isrc,
																re = /([^&=]+)=([^&]*)/g, m;
																// Creates a map with the query string parameters
																while (m = re.exec(queryString)) {
																	queryParameters[decodeURIComponent(m[1])] = decodeURIComponent(m[2]);
																}


																if (queryParameters['player_id']!=undefined) {

																	isrc = isrc.replace(queryParameters['player_id'],frameID);
																} else {
																	isrc=isrc+"&player_id="+frameID;
																}

																try{
																		isrc = isrc.replace('api=0','api=1');
																	} catch(e) {}

																isrc=isrc+"&api=1";



																ifr.attr('src',isrc);
																var player = nextcaption.find('iframe')[0];
																if (nextcaption.data('autoplay')==true)
																	$f(player).addEvent('ready', vimeoready_auto);
																else
																	$f(player).addEvent('ready', vimeoready);


															   } else {
																	if (nextcaption.data('autoplay')==true) {
																		var ifr = nextcaption.find('iframe');
																		var id = ifr.attr('id');
																		var froogaloop = $f(id);
																		froogaloop.api("pause");
																	}
															   }

														}
													}
												});
											}



										if (nextcaption.hasClass("randomrotate") && (opt.ie || opt.ie9)) nextcaption.removeClass("randomrotate").addClass("sfb");
											nextcaption.removeClass('noFilterClass');



										   var imw =0;
										   var imh = 0;

													if (nextcaption.find('img').length>0) {
														var im = nextcaption.find('img');
														if (im.data('ww') == undefined) im.data('ww',im.width());
														if (im.data('hh') == undefined) im.data('hh',im.height());

														var ww = im.data('ww');
														var hh = im.data('hh');


														im.width(ww*opt.bw);
														im.height(hh*opt.bh);
														imw = im.width();
														imh = im.height();
													} else {

														if (nextcaption.find('iframe').length>0) {

															var im = nextcaption.find('iframe');
															if (nextcaption.data('ww') == undefined) {
																nextcaption.data('ww',im.width());
															}
															if (nextcaption.data('hh') == undefined) nextcaption.data('hh',im.height());

															var ww = nextcaption.data('ww');
															var hh = nextcaption.data('hh');

															var nc =nextcaption;
																if (nc.data('fsize') == undefined) nc.data('fsize',parseInt(nc.css('font-size'),0) || 0);
																if (nc.data('pt') == undefined) nc.data('pt',parseInt(nc.css('paddingTop'),0) || 0);
																if (nc.data('pb') == undefined) nc.data('pb',parseInt(nc.css('paddingBottom'),0) || 0);
																if (nc.data('pl') == undefined) nc.data('pl',parseInt(nc.css('paddingLeft'),0) || 0);
																if (nc.data('pr') == undefined) nc.data('pr',parseInt(nc.css('paddingRight'),0) || 0);

																if (nc.data('mt') == undefined) nc.data('mt',parseInt(nc.css('marginTop'),0) || 0);
																if (nc.data('mb') == undefined) nc.data('mb',parseInt(nc.css('marginBottom'),0) || 0);
																if (nc.data('ml') == undefined) nc.data('ml',parseInt(nc.css('marginLeft'),0) || 0);
																if (nc.data('mr') == undefined) nc.data('mr',parseInt(nc.css('marginRight'),0) || 0);

																if (nc.data('bt') == undefined) nc.data('bt',parseInt(nc.css('borderTop'),0) || 0);
																if (nc.data('bb') == undefined) nc.data('bb',parseInt(nc.css('borderBottom'),0) || 0);
																if (nc.data('bl') == undefined) nc.data('bl',parseInt(nc.css('borderLeft'),0) || 0);
																if (nc.data('br') == undefined) nc.data('br',parseInt(nc.css('borderRight'),0) || 0);

																if (nc.data('lh') == undefined) nc.data('lh',parseInt(nc.css('lineHeight'),0) || 0);

																var fvwidth=opt.width;
																var fvheight=opt.height;
																if (fvwidth>opt.startwidth) fvwidth=opt.startwidth;
																if (fvheight>opt.startheight) fvheight=opt.startheight;

																if (!nextcaption.hasClass('fullscreenvideo'))
																			nextcaption.css({
																				 'font-size': (nc.data('fsize') * opt.bw)+"px",

																				 'padding-top': (nc.data('pt') * opt.bh) + "px",
																				 'padding-bottom': (nc.data('pb') * opt.bh) + "px",
																				 'padding-left': (nc.data('pl') * opt.bw) + "px",
																				 'padding-right': (nc.data('pr') * opt.bw) + "px",

																				 'margin-top': (nc.data('mt') * opt.bh) + "px",
																				 'margin-bottom': (nc.data('mb') * opt.bh) + "px",
																				 'margin-left': (nc.data('ml') * opt.bw) + "px",
																				 'margin-right': (nc.data('mr') * opt.bw) + "px",

																				 'border-top': (nc.data('bt') * opt.bh) + "px",
																				 'border-bottom': (nc.data('bb') * opt.bh) + "px",
																				 'border-left': (nc.data('bl') * opt.bw) + "px",
																				 'border-right': (nc.data('br') * opt.bw) + "px",

																				 'line-height': (nc.data('lh') * opt.bh) + "px",
																				 'height':(hh*opt.bh)+'px',
																				 'white-space':"nowrap"
																				});
																	else
																			nextcaption.css({
																				'width':opt.startwidth*opt.bw,
																				'height':opt.startheight*opt.bh
																			});


															im.width(ww*opt.bw);
															im.height(hh*opt.bh);
															imw = im.width();
															imh = im.height();
														} else {

																var nc =nextcaption;
																if (nc.data('fsize') == undefined) nc.data('fsize',parseInt(nc.css('font-size'),0) || 0);
																if (nc.data('pt') == undefined) nc.data('pt',parseInt(nc.css('paddingTop'),0) || 0);
																if (nc.data('pb') == undefined) nc.data('pb',parseInt(nc.css('paddingBottom'),0) || 0);
																if (nc.data('pl') == undefined) nc.data('pl',parseInt(nc.css('paddingLeft'),0) || 0);
																if (nc.data('pr') == undefined) nc.data('pr',parseInt(nc.css('paddingRight'),0) || 0);

																if (nc.data('mt') == undefined) nc.data('mt',parseInt(nc.css('marginTop'),0) || 0);
																if (nc.data('mb') == undefined) nc.data('mb',parseInt(nc.css('marginBottom'),0) || 0);
																if (nc.data('ml') == undefined) nc.data('ml',parseInt(nc.css('marginLeft'),0) || 0);
																if (nc.data('mr') == undefined) nc.data('mr',parseInt(nc.css('marginRight'),0) || 0);

																if (nc.data('bt') == undefined) nc.data('bt',parseInt(nc.css('borderTop'),0) || 0);
																if (nc.data('bb') == undefined) nc.data('bb',parseInt(nc.css('borderBottom'),0) || 0);
																if (nc.data('bl') == undefined) nc.data('bl',parseInt(nc.css('borderLeft'),0) || 0);
																if (nc.data('br') == undefined) nc.data('br',parseInt(nc.css('borderRight'),0) || 0);

																if (nc.data('lh') == undefined) nc.data('lh',parseInt(nc.css('lineHeight'),0) || 0);


																nextcaption.css({
																				 'font-size': (nc.data('fsize') * opt.bw)+"px",

																				 'padding-top': (nc.data('pt') * opt.bh) + "px",
																				 'padding-bottom': (nc.data('pb') * opt.bh) + "px",
																				 'padding-left': (nc.data('pl') * opt.bw) + "px",
																				 'padding-right': (nc.data('pr') * opt.bw) + "px",

																				 'margin-top': (nc.data('mt') * opt.bh) + "px",
																				 'margin-bottom': (nc.data('mb') * opt.bh) + "px",
																				 'margin-left': (nc.data('ml') * opt.bw) + "px",
																				 'margin-right': (nc.data('mr') * opt.bw) + "px",

																				 'border-top': (nc.data('bt') * opt.bh) + "px",
																				 'border-bottom': (nc.data('bb') * opt.bh) + "px",
																				 'border-left': (nc.data('bl') * opt.bw) + "px",
																				 'border-right': (nc.data('br') * opt.bw) + "px",

																				 'line-height': (nc.data('lh') * opt.bh) + "px",
																				 'white-space':"nowrap"


																});
																imh=nextcaption.outerHeight(true);
																imw=nextcaption.outerWidth(true);
															}
													}

											// CENTER THE CAPTION HORIZONTALLY
											if (nextcaption.data('x')=="center" || nextcaption.data('xcenter')=='center') {
												nextcaption.data('xcenter','center');
												nextcaption.data('x',(opt.width/2 - nextcaption.outerWidth(true)/2)/xbw-offsetx);
												
											}
											
											
											// CENTER THE CAPTION VERTICALLY
											if (nextcaption.data('y')=="center" || nextcaption.data('ycenter')=='center') {
												nextcaption.data('ycenter','center');
												nextcaption.data('y',(opt.height/2 - nextcaption.outerHeight(true)/2)/opt.bh);
												
											}
										

											if (nextcaption.hasClass('fade')) {

												nextcaption.css({'opacity':0,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':(opt.bh*nextcaption.data('y'))+"px"});
											}

											if (nextcaption.hasClass("randomrotate")) {

														nextcaption.css({'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':((xbh*nextcaption.data('y'))+offsety)+"px" });
														var sc=Math.random()*2+1;
														var ro=Math.round(Math.random()*200-100);
														var xx=Math.round(Math.random()*200-100);
														var yy=Math.round(Math.random()*200-100);
														nextcaption.data('repx',xx);
														nextcaption.data('repy',yy);
														nextcaption.data('repo',nextcaption.css('opacity'));
														nextcaption.data('rotate',ro);
														nextcaption.data('scale',sc);

														nextcaption.transition({opacity:0, scale:sc, rotate:ro, x:xx, y: yy,duration: '0ms'});
											} else {
												if (opt.ie || opt.ie9 )
													{}
												else {
												if (nextcaption.find('iframe').length==0)
													nextcaption.transition({ scale:1, rotate:0});
												}
											}

											if (nextcaption.hasClass('lfr')) {

												nextcaption.css({'opacity':1,'left':(15+opt.width)+'px','top':(opt.bh*nextcaption.data('y'))+"px"});

											}

											if (nextcaption.hasClass('lfl')) {

												nextcaption.css({'opacity':1,'left':(-15-imw)+'px','top':(opt.bh*nextcaption.data('y'))+"px"});

											}

											if (nextcaption.hasClass('sfl')) {

												nextcaption.css({'opacity':0,'left':((xbw*nextcaption.data('x'))-50+offsetx)+'px','top':(opt.bh*nextcaption.data('y'))+"px"});
											}

											if (nextcaption.hasClass('sfr')) {
												nextcaption.css({'opacity':0,'left':((xbw*nextcaption.data('x'))+50+offsetx)+'px','top':(opt.bh*nextcaption.data('y'))+"px"});
											}




											if (nextcaption.hasClass('lft')) {

												nextcaption.css({'opacity':1,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':(-25 - imh)+"px"});

											}

											if (nextcaption.hasClass('lfb')) {
												nextcaption.css({'opacity':1,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':(25+opt.height)+"px"});

											}

											if (nextcaption.hasClass('sft')) {
												nextcaption.css({'opacity':0,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':((opt.bh*nextcaption.data('y'))-50)+"px"});
											}

											if (nextcaption.hasClass('sfb')) {
												nextcaption.css({'opacity':0,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':((opt.bh*nextcaption.data('y'))+50)+"px"});
											}




											nextcaption.data('timer',setTimeout(function() {
													nextcaption.css({'visibility':'visible'});
													if (nextcaption.hasClass('fade')) {
														nextcaption.data('repo',nextcaption.css('opacity'));
														nextcaption.animate({'opacity':1},{duration:nextcaption.data('speed'),complete:function() { if (opt.ie) $(this).addClass('noFilterClass');}});
														//if (opt.ie) nextcaption.addClass('noFilterClass');
													}

													if (nextcaption.hasClass("randomrotate")) {

														nextcaption.transition({opacity:1, scale:1, 'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':(xbh*(nextcaption.data('y'))+offsety)+"px", rotate:0, x:0, y:0,duration: nextcaption.data('speed')});
														if (opt.ie) nextcaption.addClass('noFilterClass');
													}

													if (nextcaption.hasClass('lfr') ||
														nextcaption.hasClass('lfl') ||
														nextcaption.hasClass('sfr') ||
														nextcaption.hasClass('sfl') ||
														nextcaption.hasClass('lft') ||
														nextcaption.hasClass('lfb') ||
														nextcaption.hasClass('sft') ||
														nextcaption.hasClass('sfb')
														)
													{
														var easetype=nextcaption.data('easing');
														if (easetype==undefined) easetype="linear";
														nextcaption.data('repx',nextcaption.position().left);
														nextcaption.data('repy',nextcaption.position().top);

														nextcaption.data('repo',nextcaption.css('opacity'));
														
														
														nextcaption.animate({'opacity':1,'left':(xbw*nextcaption.data('x')+offsetx)+'px','top':opt.bh*(nextcaption.data('y'))+"px"},{duration:nextcaption.data('speed'), easing:easetype,complete:function() { if (opt.ie) $(this).addClass('noFilterClass');}});
														//if (opt.ie) nextcaption.addClass('noFilterClass');
													}
											},nextcaption.data('start')));


											// IF THERE IS ANY EXIT ANIM DEFINED
											if (nextcaption.data('end')!=undefined)

												nextcaption.data('timer-end',setTimeout(function() {

														if ((opt.ie || opt.ie9) && (nextcaption.hasClass("randomrotate") || nextcaption.hasClass("randomrotateout"))) {
															nextcaption.removeClass("randomrotate").removeClass("randomrotateout").addClass('fadeout');
														}

														endMoveCaption(nextcaption,opt);

												},nextcaption.data('end')));
									}
						})

						var bt=jQuery('body').find('#'+opt.container.attr('id')).find('.tp-bannertimer');												
						bt.data('opt',opt);
				}



				//////////////////////////
				//	REMOVE THE CAPTIONS //
				/////////////////////////
				function removeTheCaptions(actli,opt) {

						actli.find('.tp-caption').each(function(i) {
							var nextcaption=actli.find('.tp-caption:eq('+i+')');
							nextcaption.stop(true,true);
							clearTimeout(nextcaption.data('timer'));
							clearTimeout(nextcaption.data('timer-end'));

							var easetype=nextcaption.data('easing');
							easetype="easeInOutSine";
							var ll = nextcaption.data('repx');
							var tt = nextcaption.data('repy');
							var oo = nextcaption.data('repo');
							var rot = nextcaption.data('rotate');
							var sca = nextcaption.data('scale');


							if (nextcaption.find('iframe').length>0) {
															// VIMEO VIDEO PAUSE
															try {
																var ifr = nextcaption.find('iframe');
																var id = ifr.attr('id');
																var froogaloop = $f(id);
																froogaloop.api("pause");
															} catch(e) {}
															//YOU TUBE PAUSE
															try {
																var player=nextcaption.data('player');
																player.stopVideo();
															} catch(e) {}

														}
							try {
									/*if (rot!=undefined || sca!=undefined)
										{
											if (rot==undefined) rot=0;
											if (sca==undefined) sca=1;
												nextcaption.transition({'rotate':rot, 'scale':sca, 'opacity':0,'left':ll+'px','top':tt+"px"},(nextcaption.data('speed')+10), function() { nextcaption.removeClass('noFilterClass');nextcaption.css({'visibility':'hidden'})});
										} else {

											nextcaption.animate({'opacity':0,'left':ll+'px','top':tt+"px"},{duration:(nextcaption.data('speed')+10), easing:easetype, complete:function() { nextcaption.removeClass('noFilterClass');nextcaption.css({'visibility':'hidden'})}});
										}*/
									endMoveCaption(nextcaption,opt);
								} catch(e) {}



						});
				}

				//////////////////////////
				//	MOVE OUT THE CAPTIONS //
				/////////////////////////
				function endMoveCaption(nextcaption,opt) {


														if (nextcaption.hasClass("randomrotate") && (opt.ie || opt.ie9)) nextcaption.removeClass("randomrotate").addClass("sfb");
														if (nextcaption.hasClass("randomrotateout") && (opt.ie || opt.ie9)) nextcaption.removeClass("randomrotateout").addClass("stb");

														var endspeed=nextcaption.data('endspeed');
														if (endspeed==undefined) endspeed=nextcaption.data('speed');

														var xx=nextcaption.data('repx');
														var yy=nextcaption.data('repy');
														var oo=nextcaption.data('repo');

														if (opt.ie) {
															nextcaption.css({'opacity':'inherit','filter':'inherit'});
														}

														if (nextcaption.hasClass('ltr') ||
															nextcaption.hasClass('ltl') ||
															nextcaption.hasClass('str') ||
															nextcaption.hasClass('stl') ||
															nextcaption.hasClass('ltt') ||
															nextcaption.hasClass('ltb') ||
															nextcaption.hasClass('stt') ||
															nextcaption.hasClass('stb')
															)
														{

															xx=nextcaption.position().left;
															yy=nextcaption.position().top;

															if (nextcaption.hasClass('ltr'))
																xx=opt.width+60;
															else if (nextcaption.hasClass('ltl'))
																xx=0-nextcaption.width()-60;
															else if (nextcaption.hasClass('ltt'))
																yy=0-nextcaption.height()-60;
															else if (nextcaption.hasClass('ltb'))
																yy=opt.height+60;
															else if (nextcaption.hasClass('str')) {
																xx=xx+50;oo=0;
															} else if (nextcaption.hasClass('stl')) {
																xx=xx-50;oo=0;
															} else if (nextcaption.hasClass('stt')) {
																yy=yy-50;oo=0;
															} else if (nextcaption.hasClass('stb')) {
																yy=yy+50;oo=0;
															}

															var easetype=nextcaption.data('endeasing');
															if (easetype==undefined) easetype="linear";

															nextcaption.animate({'opacity':oo,'left':xx+'px','top':yy+"px"},{duration:nextcaption.data('endspeed'), easing:easetype,complete:function() { $(this).css({visibility:'hidden'})}});
															if (opt.ie) nextcaption.removeClass('noFilterClass');

														}

														else

														if ( nextcaption.hasClass("randomrotateout")) {

															nextcaption.transition({opacity:0, scale:Math.random()*2+0.3, 'left':Math.random()*opt.width+'px','top':Math.random()*opt.height+"px", rotate:Math.random()*40, duration: endspeed,complete:function() { $(this).css({visibility:'hidden'})}});
															if (opt.ie) nextcaption.removeClass('noFilterClass');

														}

														else

														if (nextcaption.hasClass('fadeout')) {
															if (opt.ie) nextcaption.removeClass('noFilterClass');
															nextcaption.animate({'opacity':0},{duration:200,complete:function() { $(this).css({visibility:'hidden'})}});

														}

														else

														if (nextcaption.hasClass('lfr') ||
															nextcaption.hasClass('lfl') ||
															nextcaption.hasClass('sfr') ||
															nextcaption.hasClass('sfl') ||
															nextcaption.hasClass('lft') ||
															nextcaption.hasClass('lfb') ||
															nextcaption.hasClass('sft') ||
															nextcaption.hasClass('sfb')
															)
														{

															if (nextcaption.hasClass('lfr'))
																xx=opt.width+60;
															else  if (nextcaption.hasClass('lfl'))
																xx=0-nextcaption.width()-60;
															else if (nextcaption.hasClass('lft'))
																yy=0-nextcaption.height()-60;
															else if (nextcaption.hasClass('lfb'))
																yy=opt.height+60;


															var easetype=nextcaption.data('endeasing');
															if (easetype==undefined) easetype="linear";

															nextcaption.animate({'opacity':oo,'left':xx+'px','top':yy+"px"},{duration:nextcaption.data('endspeed'), easing:easetype, complete:function() { $(this).css({visibility:'hidden'})}});
															if (opt.ie) nextcaption.removeClass('noFilterClass');

														}

														else

														if (nextcaption.hasClass('fade')) {

															nextcaption.animate({'opacity':0},{duration:endspeed,complete:function() { $(this).css({visibility:'hidden'})} });
															if (opt.ie) nextcaption.removeClass('noFilterClass');

														}

														else

														if (nextcaption.hasClass("randomrotate")) {

															nextcaption.transition({opacity:0, scale:Math.random()*2+0.3, 'left':Math.random()*opt.width+'px','top':Math.random()*opt.height+"px", rotate:Math.random()*40, duration: endspeed });
															if (opt.ie) nextcaption.removeClass('noFilterClass');

														}
				}

		///////////////////////////
		//	REMOVE THE LISTENERS //
		///////////////////////////
		function removeAllListeners(container,opt) {
			container.children().each(function() {
			  try{ $(this).die('click'); } catch(e) {}
			  try{ $(this).die('mouseenter');} catch(e) {}
			  try{ $(this).die('mouseleave');} catch(e) {}
			  try{ $(this).unbind('hover');} catch(e) {}
			})
			try{ $container.die('click','mouseenter','mouseleave');} catch(e) {}
			clearInterval(opt.cdint);
			container=null;



		}

		///////////////////////////
		//	-	COUNTDOWN	-	//
		/////////////////////////
		function countDown(container,opt) {
			opt.cd=0;
			opt.loop=0;
			if (opt.stopAfterLoops!=undefined && opt.stopAfterLoops>-1)
					opt.looptogo=opt.stopAfterLoops;
			else
				opt.looptogo=9999999;

			if (opt.stopAtSlide!=undefined && opt.stopAtSlide>-1)
					opt.lastslidetoshow=opt.stopAtSlide;
			else
					opt.lastslidetoshow=999;

			opt.stopLoop="off";

			if (opt.looptogo==0) opt.stopLoop="on";



			if (opt.slideamount >1 && !(opt.stopAfterLoops==0 && opt.stopAtSlide==1) ) {
					var bt=container.find('.tp-bannertimer');
					if (bt.length>0) {
						bt.css({'width':'0%'});
						bt.animate({'width':"100%"},{duration:(opt.delay-100),queue:false, easing:"linear"});
					}

					bt.data('opt',opt);


					opt.cdint=setInterval(function() {

						if ($('body').find(container).length==0) removeAllListeners(container,opt);
						if (container.data('conthover-changed') == 1) {
							opt.conthover=	container.data('conthover');
							container.data('conthover-changed',0);
						}

						if (opt.conthover!=1 && opt.videoplaying!=true && opt.width>opt.hideSliderAtLimit) opt.cd=opt.cd+100;

						if (opt.fullWidth!="on")
							if (opt.width>opt.hideSliderAtLimit)
								container.parent().removeClass("tp-hide-revslider")
							else
								container.parent().addClass("tp-hide-revslider")
						// EVENT TRIGGERING IN CASE VIDEO HAS BEEN STARTED
						if (opt.videostartednow==1) {
							container.trigger('revolution.slide.onvideoplay');
							opt.videostartednow=0;
						}

						// EVENT TRIGGERING IN CASE VIDEO HAS BEEN STOPPED
						if (opt.videostoppednow==1) {
							container.trigger('revolution.slide.onvideostop');
							opt.videostoppednow=0;
						}


						if (opt.cd>=opt.delay) {
							opt.cd=0;
							// SWAP TO NEXT BANNER
							opt.act=opt.next;
							opt.next=opt.next+1;
							if (opt.next>container.find('>ul >li').length-1) {
									opt.next=0;
									opt.looptogo=opt.looptogo-1;

									if (opt.looptogo<=0) {
											opt.stopLoop="on";

									}
								}

							// STOP TIMER IF NO LOOP NO MORE NEEDED.

							if (opt.stopLoop=="on" && opt.next==opt.lastslidetoshow-1) {
									clearInterval(opt.cdint);
									container.find('.tp-bannertimer').css({'visibility':'hidden'});
									container.trigger('revolution.slide.onstop');
							}

							// SWAP THE SLIDES
							swapSlide(container,opt);


							// Clear the Timer
							if (bt.length>0) {
								bt.css({'width':'0%'});
								bt.animate({'width':"100%"},{duration:(opt.delay-100),queue:false, easing:"linear"});
							}
						}
					},100);


					container.hover(
						function() {

							if (opt.onHoverStop=="on") {
									opt.conthover=1;
								bt.stop();
								container.trigger('revolution.slide.onpause');
							}
						},
						function() {
							if (container.data('conthover')!=1) {
								container.trigger('revolution.slide.onresume');
								opt.conthover=0;
								if (opt.onHoverStop=="on" && opt.videoplaying!=true) {
									bt.animate({'width':"100%"},{duration:((opt.delay-opt.cd)-100),queue:false, easing:"linear"});
								}
							}
						});
			}
		}



})(jQuery);




