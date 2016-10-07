/*----------------------------------------------------------
    Detect Mobile Browser
-----------------------------------------------------------*/
if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
   $('html').addClass('ismobile');
}

$(window).load(function () {
    /*-----------------------------------------------------------
        Page Loader
     -----------------------------------------------------------*/
    if($('.page-loader')[0]) {
        setTimeout (function () {
            $('.page-loader').fadeOut();
        }, 500);

    }
})

$(document).ready(function(){

    /*----------------------------------------------------------
        Scrollbar
    -----------------------------------------------------------*/
    function scrollBar(selector, theme, mousewheelaxis) {
        $(selector).mCustomScrollbar({
            theme: theme,
            scrollInertia: 100,
            axis:'mousewheelaxis',
            mouseWheel: {
                enable: true,
                axis: mousewheelaxis,
                preventDefault: true
            }
        });
    }

    if (!$('html').hasClass('ismobile')) {
        //On Custom Class
        if ($('.c-overflow')[0]) {
            scrollBar('.c-overflow', 'minimal-dark', 'y');
        }
    }

    /*----------------------------------------------------------
        Dropdown Menu
    -----------------------------------------------------------*/
    if($('.dropdown')[0]) {
  	   //Propagate
    	$('body').on('click', '.dropdown.open .dropdown-menu', function(e){
    	    e.stopPropagation();
    	});

    	$('.dropdown').on('shown.bs.dropdown', function (e) {
    	    if($(this).attr('data-animation')) {
        		$animArray = [];
        		$animation = $(this).data('animation');
        		$animArray = $animation.split(',');
        		$animationIn = 'animated '+$animArray[0];
        		$animationOut = 'animated '+ $animArray[1];
        		$animationDuration = ''
        		if(!$animArray[2]) {
        		    $animationDuration = 500; //if duration is not defined, default is set to 500ms
        		}
        		else {
        		    $animationDuration = $animArray[2];
        		}

        		$(this).find('.dropdown-menu').removeClass($animationOut)
        		$(this).find('.dropdown-menu').addClass($animationIn);
    	    }
    	});

	     $('.dropdown').on('hide.bs.dropdown', function (e) {
    	    if($(this).attr('data-animation')) {
        		e.preventDefault();
        		$this = $(this);
        		$dropdownMenu = $this.find('.dropdown-menu');

        		$dropdownMenu.addClass($animationOut);
        		setTimeout(function(){
        		    $this.removeClass('open')

        		}, $animationDuration);
        	}
        });
      }


    /*----------------------------------------------------------
        Calendar Widget
    -----------------------------------------------------------*/
    if($('#calendar-widget')[0]) {
        (function(){
            $('#calendar-widget #cw-body').fullCalendar({
		        contentHeight: 'auto',
		        theme: true,
                header: {
                    right: '',
                    center: 'prev, title, next',
                    left: ''
                },
                defaultDate: '2014-06-12',
                editable: true,
                events: [
                    {
                        title: 'All Day',
                        start: '2014-06-01',
                    },
                    {
                        title: 'Long Event',
                        start: '2014-06-07',
                        end: '2014-06-10',
                    },
                    {
                        id: 999,
                        title: 'Repeat',
                        start: '2014-06-09',
                    },
                    {
                        id: 999,
                        title: 'Repeat',
                        start: '2014-06-16',
                    },
                    {
                        title: 'Meet',
                        start: '2014-06-12',
                        end: '2014-06-12',
                    },
                    {
                        title: 'Lunch',
                        start: '2014-06-12',
                    },
                    {
                        title: 'Birthday',
                        start: '2014-06-13',
                    },
                    {
                        title: 'Google',
                        url: 'http://google.com/',
                        start: '2014-06-28',
                    }
                ]
            });

            //Display Current Date as Calendar widget header
            var mYear = moment().format('YYYY');
            var mDay = moment().format('dddd, MMM D');
            $('#calendar-widget .cwh-year').html(mYear);
            $('#calendar-widget .cwh-day').html(mDay);
        })();
    }


    /*----------------------------------------------------------
        Weather Widget
    -----------------------------------------------------------*/
    if ($('#weather-widget')[0]) {
        $.simpleWeather({
            location: 'Austin, TX',
            woeid: '',
            unit: 'f',
            success: function(weather) {
                html = '<div class="weather-status">'+weather.temp+'&deg;'+weather.units.temp+'</div>';
                html += '<ul class="weather-info"><li>'+weather.city+', '+weather.region+'</li>';
                html += '<li class="currently">'+weather.currently+'</li></ul>';
                html += '<div class="weather-icon wi-'+weather.code+'"></div>';
                html += '<div class="dw-footer"><div class="weather-list tomorrow">';
                html += '<span class="weather-list-icon wi-'+weather.forecast[2].code+'"></span><span>'+weather.forecast[1].high+'/'+weather.forecast[1].low+'</span><span>'+weather.forecast[1].text+'</span>';
                html += '</div>';
                html += '<div class="weather-list after-tomorrow">';
                html += '<span class="weather-list-icon wi-'+weather.forecast[2].code+'"></span><span>'+weather.forecast[2].high+'/'+weather.forecast[2].low+'</span><span>'+weather.forecast[2].text+'</span>';
                html += '</div></div>';
                $("#weather-widget").html(html);
            },
            error: function(error) {
                $("#weather-widget").html('<p>'+error+'</p>');
            }
        });
    }


    /*----------------------------------------------------------
         Auto Size Textare
    -----------------------------------------------------------*/
    if ($('.auto-size')[0]) {
	     autosize($('.auto-size'));
    }


    /*----------------------------------------------------------
        Text Field
    -----------------------------------------------------------*/
    //Add blue animated border and remove with condition when focus and blur
    if($('.fg-line')[0]) {
        $('body').on('focus', '.fg-line .form-control', function(){
            $(this).closest('.fg-line').addClass('fg-toggled');
        })

        $('body').on('blur', '.form-control', function(){
            var p = $(this).closest('.form-group, .input-group');
            var i = p.find('.form-control').val();

            if (p.hasClass('fg-float')) {
                if (i.length == 0) {
                    $(this).closest('.fg-line').removeClass('fg-toggled');
                }
            }
            else {
                $(this).closest('.fg-line').removeClass('fg-toggled');
            }
        });
    }

    //Add blue border for pre-valued fg-flot text feilds
    if($('.fg-float')[0]) {
        $('.fg-float .form-control').each(function(){
            var i = $(this).val();

            if (!i.length == 0) {
                $(this).closest('.fg-line').addClass('fg-toggled');
            }

        });
    }


    /*----------------------------------------------------------
        Audio and Video Player
    -----------------------------------------------------------*/
    if($('audio, video')[0]) {
        $('video,audio').mediaelementplayer();
    }


    /*----------------------------------------------------------
        Chosen
    -----------------------------------------------------------*/
    if($('.chosen')[0]) {
        $('.chosen').chosen({
            width: '100%',
            allow_single_deselect: true
        });
    }


    /*----------------------------------------------------------
        NoUiSlider (Input Slider)
    -----------------------------------------------------------*/
    //Basic
    if ($('#input-slider')[0]) {
        var slider = document.getElementById ('input-slider');

        noUiSlider.create (slider, {
            start: [20],
            connect: 'lower',
            range: {
                'min': 0,
                'max': 100
            }
        });
    }

    //Range
    if ($('#input-slider-range')[0]) {
        var sliderRange = document.getElementById ('input-slider-range');

        noUiSlider.create (sliderRange, {
            start: [40, 70],
            connect: true,
            range: {
                'min': 0,
                'max': 100
            }
        });
    }

    //Range with value
    if($('#input-slider-value')[0]) {
        var sliderRangeValue = document.getElementById('input-slider-value');

        noUiSlider.create(sliderRangeValue, {
            start: [10, 50],
            connect: true,
            range: {
                'min': 0,
                'max': 100
            }
        });

        sliderRangeValue.noUiSlider.on('update', function( values, handle ) {
            document.getElementById('input-slider-value-output').innerHTML = values[handle];
        });
    }


    /*----------------------------------------------------------
        Input Mask
    -----------------------------------------------------------*/
    if ($('input-mask')[0]) {
        $('.input-mask').mask();
    }


    /*----------------------------------------------------------
        Farbtastic Color Picker
    -----------------------------------------------------------*/
    if ($('.color-picker')[0]) {
	    $('.color-picker').each(function(){
            var colorOutput = $(this).closest('.cp-container').find('.cp-value');
            $(this).farbtastic(colorOutput);
        });
    }


    /*-----------------------------------------------------------
        Summernote HTML Editor
    -----------------------------------------------------------*/
    if ($('.html-editor')[0]) {
	   $('.html-editor').summernote({
            height: 150
        });
    }

    if($('.html-editor-click')[0]) {
        //Edit
        $('body').on('click', '.hec-button', function(){
            $('.html-editor-click').summernote({
                focus: true
            });
            $('.hec-save').show();
        })

        //Save
        $('body').on('click', '.hec-save', function(){
            $('.html-editor-click').code();
            $('.html-editor-click').destroy();
            $('.hec-save').hide();
        });
    }

    //Air Mode
    if($('.html-editor-airmod')[0]) {
        $('.html-editor-airmod').summernote({
            airMode: true
        });
    }


    /*-----------------------------------------------------------
        Date Time Picker
    -----------------------------------------------------------*/
    //Date Time Picker
    if ($('.date-time-picker')[0]) {
	   $('.date-time-picker').datetimepicker();
    }

    //Time
    if ($('.time-picker')[0]) {
    	$('.time-picker').datetimepicker({
    	    format: 'LT'
    	});
    }

    //Date
    if ($('.date-picker')[0]) {
    	$('.date-picker').datetimepicker({
    	    format: 'DD/MM/YYYY'
    	});
    }

    $('.date-picker').on('dp.hide', function(){
        $(this).closest('.dtp-container').removeClass('fg-toggled');
        $(this).blur();
    })


    /*-----------------------------------------------------------
        Form Wizard
    -----------------------------------------------------------*/
    if ($('.form-wizard-basic')[0]) {
    	$('.form-wizard-basic').bootstrapWizard({
    	    tabClass: 'fw-nav',
            'nextSelector': '.next',
            'previousSelector': '.previous'
    	});
    }


    /*-----------------------------------------------------------
        Waves
    -----------------------------------------------------------
    (function(){
         Waves.attach('.btn:not(.btn-icon):not(.btn-float)');
         Waves.attach('.btn-icon, .btn-float', ['waves-circle', 'waves-float']);
        Waves.init();
    })();
    */


    /*----------------------------------------------------------
        Lightbox
    -----------------------------------------------------------*/
    if ($('.lightbox')[0]) {
        $('.lightbox').lightGallery({
            enableTouch: true
        });
    }


    /*-----------------------------------------------------------
        Link prevent
    -----------------------------------------------------------*/
    $('body').on('click', '.a-prevent', function(e){
        e.preventDefault();
    });


    /*----------------------------------------------------------
        Bootstrap Accordion Fix
    -----------------------------------------------------------*/
    if ($('.collapse')[0]) {

        //Add active class for opened items
        $('.collapse').on('show.bs.collapse', function (e) {
            $(this).closest('.panel').find('.panel-heading').addClass('active');
        });

        $('.collapse').on('hide.bs.collapse', function (e) {
            $(this).closest('.panel').find('.panel-heading').removeClass('active');
        });

        //Add active class for pre opened items
        $('.collapse.in').each(function(){
            $(this).closest('.panel').find('.panel-heading').addClass('active');
        });
    }


    /*-----------------------------------------------------------
        Tooltips
    -----------------------------------------------------------*/
    if ($('[data-toggle="tooltip"]')[0]) {
        $('[data-toggle="tooltip"]').tooltip();
    }


    /*-----------------------------------------------------------
        Popover
    -----------------------------------------------------------*/
    if ($('[data-toggle="popover"]')[0]) {
        $('[data-toggle="popover"]').popover();
    }


    /*-----------------------------------------------------------
        IE 9 Placeholder
    -----------------------------------------------------------*/
    if($('html').hasClass('ie9')) {
        $('input, textarea').placeholder({
            customClass: 'ie9-placeholder'
        });
    }


    /*-----------------------------------------------------------
        Typeahead Auto Complete
    -----------------------------------------------------------*/
     if($('.typeahead')[0]) {

          var statesArray = ['Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California',
            'Colorado', 'Connecticut', 'Delaware', 'Florida', 'Georgia', 'Hawaii',
            'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky', 'Louisiana',
            'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
            'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
            'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota',
            'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania', 'Rhode Island',
            'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
            'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'
          ];
        var states = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.whitespace,
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            local: statesArray
        });

        $('.typeahead').typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        },
        {
          name: 'states',
          source: states
        });
    }
});
