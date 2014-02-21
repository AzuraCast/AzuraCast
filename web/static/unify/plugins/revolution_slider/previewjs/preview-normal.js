jQuery(document).ready(function() {


				var ie = $.browser.msie;

				if (ie) {
					jQuery('.rotbutcont').css({'opacity':'0.4'});
					jQuery('.premium').each(function() {
						jQuery(this).html(jQuery(this).html()+" ( **not in IE )");
					});
				}

		// ROTATE THE RADIO BUTTON HERE
				if (!ie) {
					jQuery('.rotator').bind(
						'mousedown',
						function() {
							jQuery(document).data('rotator-dragged',1);
							jQuery('.rotator .rotator-line').transit({rotate:jQuery('.rotator').data('angle')},5);
							jQuery('.rotatefield.inputfield').html(jQuery('.rotator').data('angle')+" degree");
						}
					)

					jQuery('.rotator').bind(
						'mousemove',
						function(e) {
								var x = (e.pageX - this.offsetLeft);
								var y = (e.pageY - this.offsetTop);





									var angle=Math.atan((x - 37)/(y-37)) * 180/Math.PI;

									var newangle=(0-angle);

									if (x>=37 && y>=37)
										 newangle = (180-angle);
								    else
								    if (x<=37 && y>=37)
								    	 newangle=(0-angle)+180;
								    else
								    if (x<37 && y<37)
								    	newangle=360-angle;

								   jQuery('.rotator').data('angle',Math.round(newangle));

								if (jQuery(document).data('rotator-dragged')==1) {
									jQuery('.rotator .rotator-line').transit({rotate:newangle},5);
									jQuery('.rotatefield.inputfield').html(jQuery('.rotator').data('angle')+" degree");
								}




						}
					)

					jQuery(document).bind(
						'mouseup',
						function() {
						  if (jQuery(document).data('rotator-dragged')==1) {
							  // THE ROTATOR WOULD BE ROTATED, NEW POSITION NEED TO BE CALCULATED
							  changeValues(jQuery('.rotator').data('angle'),"rotate",true);
						  }
						  jQuery(document).data('rotator-dragged',0);
						}
					)
				 }


			// Set the Amount of the SLOT AMOUNTS
					jQuery('.slot.plus').click(function() {

						var inf = jQuery('.slotamount.inputfield');
						var slot=inf.data('slot');
						if (slot==undefined) slot=0;
						slot=slot+1;
						inf.data('slot',slot);
						inf.html(slot+" Slots");
						changeValues(slot,"slotamount",false);
					})

					jQuery('.slot.minus').click(function() {

						var inf = jQuery('.slotamount.inputfield');
						var slot=inf.data('slot');
						if (slot==undefined) slot=0;
						slot=slot-1;
						if (slot<0) slot=0;
						inf.data('slot',slot);
						changeValues(slot,"slotamount",false);
						if (slot==0) slot="Random";
						inf.html(slot+" Slots");

					})


			// Select Transition
				jQuery('.selecttransition').change(function() {
					jQuery(this).parent().find('.dropcontent').html(jQuery(this).find('option:selected').html());
					changeValues(jQuery(this).val(),"transition",true);
				})



				jQuery('.dselect').each(function() {

					jQuery(this).change(function() {
						jQuery(this).parent().find('.dropcontent').html(jQuery(this).find('option:selected').html());
						var bul = jQuery('.tp-bullets.simplebullets');
						var arrws = jQuery('.tparrows');

						arrws.removeClass('navbar').removeClass('round').removeClass('square').removeClass('round-old').removeClass('navbar-old').removeClass('square-old');
						bul.removeClass('navbar').removeClass('round').removeClass('square').removeClass('round-old').removeClass('navbar-old').removeClass('square-old');


						var navstyle=jQuery('.selectnavstyle');
						var navtype =jQuery('.selectnavtype');
						var navarrow =jQuery('.selectnavarrows');


						jQuery('#unvisible_button').click();


					});
				})




			// Change The Valus and jump to next Slide (if needed)
			function changeValues(neu,old,startapi) {


					jQuery('.banner ul li').each(function() {
						var li=jQuery(this);
						if (li.data('old'+old)==undefined) li.data(old,li.data(old));
						if (neu=="Demo") {

						  li.data(old,li.data(old));
						} else {
						  li.data(old,neu);
						 }
					});

					// call next slide
					if (startapi) api.revnext();
			}
	});
