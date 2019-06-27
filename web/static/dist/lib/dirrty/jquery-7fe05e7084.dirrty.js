/*
 * Dirrty v0.2.0
 * lightweight jquery plugin to detect when the fields of a form are modified
 * (c)2014 Rub�n Torres - rubentdlh@gmail.com
 * Released under the MIT license
 */

//Save dirrty instances
var singleDs = [];

(function($) {

 	function getSingleton(id){
 		var result;
 		$(singleDs).each(function(){
			if($(this)[0].id == id){
				result = $(this)[0];
			}
 		});
 		return result;
 	}

 	function Dirrty(form, options){
 		this.form=form;
 		this.isDirty=false;
 		this.options=options;
 		this.history = ["clean", "clean"]; //Keep track of last statuses
 		this.id=$(form).attr("id");
 		singleDs.push(this);
 	}

 	Dirrty.prototype = {

 		init: function(){
 			this.saveInitialValues();
 			this.setEvents();
 		},

 		saveInitialValues: function(){
 			this.form.find("input, select, textarea").each( function(){
 				$(this).attr("data-dirrty-initial-value", $(this).val());
 			});

 			this.form.find("input[type=checkbox], input[type=radio]").each( function(){
 				if($(this).is(":checked")){
 					$(this).attr("data-dirrty-initial-value", "checked");
 				}else{
 					$(this).attr("data-dirrty-initial-value", "unchecked");
 				}
 			});
 		},

 		setEvents: function(){
 			var d = this;

 			$(document).ready( function(){

 				d.form.on('submit', function(){
 					d.submitting = true;
 				});

 				if(d.options.preventLeaving){
					$(window).on('beforeunload', function(){
						if(d.isDirty && !d.submitting){
							return d.options.leavingMessage;
						}
					});
				}

				d.form.find("input, select").change(function(){
					d.checkValues();
				});

				d.form.find("input, textarea").on('keyup keydown blur', function(){
					d.checkValues();
				});

				//fronteend's icheck support
				d.form.find("input[type=radio], input[type=checkbox]").on('ifChecked', function(event){
					d.checkValues();
				});

			});
 		},

 		checkValues: function(){
 			var d = this;
 			this.form.find("input, select, textarea").each( function(){
 				var initialValue = $(this).attr("data-dirrty-initial-value");
 				if($(this).val() != initialValue){
 					$(this).attr("data-is-dirrty", "true");
 				}else{
 					$(this).attr("data-is-dirrty", "false");
 				}
 			});
 			this.form.find("input[type=checkbox], input[type=radio]").each( function(){
 				var initialValue = $(this).attr("data-dirrty-initial-value");
 				if($(this).is(":checked") && initialValue != "checked"
 					|| !$(this).is(":checked") && initialValue == "checked"){
 					$(this).attr("data-is-dirrty", "true");
				}else{
					$(this).attr("data-is-dirrty", "false");
				}
 			});
 			var isDirty = false;
 			this.form.find("input, select, textarea").each( function(){
 				if( $(this).attr("data-is-dirrty") == "true" ){
 					isDirty = true;
 				}
 			});
 			if(isDirty){
 				d.setDirty();
 			}else{
				d.setClean();
 			}

 			d.fireEvents();
 		},

 		fireEvents: function(){

 			if(this.isDirty && this.wasJustClean()){
 				this.form.trigger("dirty");
 			}

 			if(!this.isDirty && this.wasJustDirty()){
 				this.form.trigger("clean");
 			}
 		},

 		setDirty: function(){
 			this.isDirty = true;
 			this.history[0] = this.history[1];
 			this.history[1] = "dirty";
 		},

 		setClean: function(){
 			this.isDirty = false;
 			this.history[0] = this.history[1];
 			this.history[1] = "clean";
 		},

 		//Lets me know if the previous status of the form was dirty
 		wasJustDirty: function(){
 			return (this.history[0] == "dirty");
 		},

 		//Lets me know if the previous status of the form was clean
 		wasJustClean: function(){
 			return (this.history[0] == "clean");
 		}
 	}

 	$.fn.dirrty = function(options) {

		if (/^(isDirty)$/i.test(options) || /^(setClean)$/i.test(options)) {
			//Check if we have an instance of dirrty for this form
			var d = getSingleton($(this).attr("id"));

			if(!d){
				var d = new Dirrty($(this), options);
				d.init();
			}
			switch(options){
				case 'isDirty':
					return d.isDirty;
				case 'setClean':
					d.setClean();
					return true;
			}

		}else if (typeof options == 'object' || !options) {

			return this.each(function(){
				options = $.extend({}, $.fn.dirrty.defaults, options);
 				var dirrty = new Dirrty($(this), options);
 				dirrty.init();
			});

		}

 	}

 	$.fn.dirrty.defaults = {
 		preventLeaving: true,
 		leavingMessage: "You have unsaved changes",
 		onDirty: function(){},  //This function is fired when the form gets dirty
 		onClean: function(){}   //This funciton is fired when the form gets clean again
 	};

 })(jQuery);
