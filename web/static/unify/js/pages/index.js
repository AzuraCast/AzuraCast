var Index = function () {

    return {
        
        //Parallax Slider
        initParallaxSlider: function () {
			$(function() {
				$('#da-slider').cslider();
			});
        },

        //Revolution Slider
        initRevolutionSlider: function () {
            var api;
            jQuery(document).ready(function() {
                 api =  jQuery('.fullwidthabnner').revolution(
	                {
	                    delay:9000,
	                    startheight:500,
	                    startwidth:960,

	                    hideThumbs:10,

	                    thumbWidth:100,                         // Thumb With and Height and Amount (only if navigation Tyope set to thumb !)
	                    thumbHeight:50,
	                    thumbAmount:5,

	                    navigationType:"bullet",                // bullet, thumb, none
	                    navigationArrows:"solo",                // nexttobullets, solo (old name verticalcentered), none

	                    navigationStyle:"round",                // round,square,navbar,round-old,square-old,navbar-old, or any from the list in the docu (choose between 50+ different item), custom


	                    navigationHAlign:"center",              // Vertical Align top,center,bottom
	                    navigationVAlign:"bottom",              // Horizontal Align left,center,right
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

	                    touchenabled:"on",                      // Enable Swipe Function : on/off
	                    onHoverStop:"on",                       // Stop Banner Timet at Hover on Slide on/off

	                    stopAtSlide:-1,
	                    stopAfterLoops:-1,

	                    shadow:1,                               //0 = no Shadow, 1,2,3 = 3 Different Art of Shadows  (No Shadow in Fullwidth Version !)
	                    fullWidth:"on"                          // Turns On or Off the Fullwidth Image Centering in FullWidth Modus
	                });
            });
        }

    };
}();