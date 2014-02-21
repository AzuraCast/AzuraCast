$(document).ready(function(){
    $status = $(".status");
    var options = {
        autoPlayDelay: 4000,
        pauseOnHover: false,
        hidePreloaderDelay: 500,
        nextButton: true,
        prevButton: true,
        pauseButton: true,
        preloader: true,
        hidePreloaderUsingCSS: false,                   
        animateStartingFrameIn: true,    
        navigationSkipThreshold: 750,
        customKeyEvents: {
            80: "pause"
        }
    };

    var sequence = $("#sequence").sequence(options).data("sequence");

    sequence.afterNextFrameAnimatesIn = function() {
        if(sequence.settings.autoPlay && !sequence.hardPaused && !sequence.isPaused) {
            $status.addClass("active").css("opacity", 1);
        }
        $(".prev, .next").css("cursor", "pointer").animate({"opacity": 1}, 500);
    };
    sequence.beforeCurrentFrameAnimatesOut = function() {
        if(sequence.settings.autoPlay && !sequence.hardPaused) {
            $status.css({"opacity": 0}, 500).removeClass("active");
        }
        $(".prev, .next").css("cursor", "auto").animate({"opacity": .7}, 500);
    };
    sequence.paused = function() {
        $status.css({"opacity": 0}).removeClass("active").addClass("paused");
    };
    sequence.unpaused = function() {
        if(!sequence.hardPaused) {
            $status.removeClass("paused").addClass("active").css("opacity", 1)
        }               
    };
});