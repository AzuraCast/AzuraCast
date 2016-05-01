/*
*  Project: Bootstrap Growl - v2.0.1
*  Description: Turns standard Bootstrap alerts into "Growl-like" notifications.
*  Author: Mouse0270 aka Robert McIntosh
*  License: MIT License
*  Website: https://github.com/mouse0270/bootstrap-growl
*/
;(function ( $, window, document, undefined ) {
	// Create the defaults once
	var pluginName = "growl",
		dataKey = "plugin_" + pluginName,
		defaults = {
			element: 'body',
			type: "info",
			allow_dismiss: true,
			placement: {
				from: "top",
				align: "right"
			},
			offset: 20,
			spacing: 10,
			z_index: 1031,
			delay: 5000,
			timer: 1000,
			url_target: '_blank',
			mouse_over: false,
			animate: {
				enter: 'animated fadeInDown',
				exit: 'animated fadeOutUp'
			},
			onShow: null,
			onShown: null,
			onHide: null,
			onHidden: null,
			icon_type: 'class',
			template: '<div data-growl="container" class="alert" role="alert"><button type="button" aria-hidden="true" class="close" data-growl="dismiss">&times;</button><span data-growl="icon"></span><span data-growl="title"></span><span data-growl="message"></span><a href="#" data-growl="url"></a></div>'
		};

	// The actual plugin constructor
	var setDefaults = function(element, options) {
		defaults = $.extend(true, {}, defaults, options);
	},
	closeAll = function(options) {
		if (!options) {
			$('[data-growl="container"]').find('[data-growl="dismiss"]').trigger('click');
		}else{
			$('[data-growl="container"][data-growl-position="'+options+'"]').find('[data-growl="dismiss"]').trigger('click');
		}
	},
	Plugin = function (element, content, options) {
		var content = {
			content: {
				message: typeof content == 'object' ? content.message : content,
				title: content.title ? content.title : null,
				icon: content.icon ? content.icon : null,
				url: content.url ? content.url : null
			}
		};

		options = $.extend(true, {}, content, options);
		this.settings = $.extend(true, {}, defaults, options);
		plugin = this;
		init(options, this.settings, plugin);
		this.$template = $template;
	},
	init = function (options, settings, plugin) {

		var base = {
				settings: settings,
				element: settings.element,
				template: settings.template
			};

		if (typeof settings.offset == 'number') {
		    settings.offset = {
		    	x: settings.offset,
		    	y: settings.offset
		    };
		}

		$template = buildGrowl(base);
		addContent($template, base.settings);
		placement($template, base.settings);
		bindControls($template, base.settings,plugin);
	},
	buildGrowl = function(base) {

		var $template = $(base.settings.template);

		$template.addClass('alert-' + base.settings.type);
		$template.attr('data-growl-position', base.settings.placement.from + '-' + base.settings.placement.align);

		$template.find('[data-growl="dismiss"]').css('display', 'none');
		$template.removeClass('alert-dismissable');
		if (base.settings.allow_dismiss) {
  		$template.addClass('alert-dismissable');
			$template.find('[data-growl="dismiss"]').css('display', 'block');
		}

		return $template;
	},
	addContent = function($template, settings) {

		$template.find('[data-growl="dismiss"]').css({
			'z-index': ((settings.z_index-1) >= 1 ? (settings.z_index-1) : 1)
		});

		if (settings.content.icon) {
			if (settings.icon_type.toLowerCase() == 'class') {
				$template.find('[data-growl="icon"]').addClass(settings.content.icon);
			}else{
				if ($template.find('[data-growl="icon"]').is('img')) {
					$template.find('[data-growl="icon"]').attr('src', settings.content.icon);
				}else{
					$template.find('[data-growl="icon"]').append('<img src="'+settings.content.icon+'" />');
				}
			}
		}

		if (settings.content.title) {
			$template.find('[data-growl="title"]').html(settings.content.title);
		}

		if (settings.content.message) {
			$template.find('[data-growl="message"]').html(settings.content.message);
		}

		if (settings.content.url) {
			$template.find('[data-growl="url"]').attr('href', settings.content.url).attr('target', settings.url_target);
			$template.find('[data-growl="url"]').css({
				'position': 'absolute',
				'top': 0,
				'left': 0,
				'width': '100%',
				'height': '100%',
				'z-index': ((settings.z_index-2) >= 1 ? (settings.z_index-2) : 1)
			});
		}
	},
	placement = function($template, settings) {
		var offsetAmt = settings.offset.y,
			gCSS = {
				'position': (settings.element === 'body' ? 'fixed' : 'absolute'),
				'margin': 0,
				'z-index': settings.z_index,
				'display': 'inline-block'
			},
			hasAnimation = false;

		$('[data-growl-position="' + settings.placement.from + '-' + settings.placement.align + '"]').each(function() {
			return offsetAmt = Math.max(offsetAmt, parseInt($(this).css(settings.placement.from)) + $(this).outerHeight() + settings.spacing);
		});

		gCSS[settings.placement.from] = offsetAmt + "px";
		$template.css(gCSS);

		if (settings.onShow) {
			settings.onShow(event);
		}

		$(settings.element).append($template);

		switch (settings.placement.align) {
			case 'center':
				$template.css({
					'left': '50%',
					'marginLeft': -($template.outerWidth() / 2) + 'px'
				});
				break;
			case 'left':
				$template.css('left', settings.offset.x + 'px');
				break;
			case 'right':
				$template.css('right', settings.offset.x + 'px');
				break;
		}
		$template.addClass('growl-animated');

		$template.one('webkitAnimationStart oanimationstart MSAnimationStart animationstart', function(event) {
			hasAnimation = true;
		});

		$template.one('webkitAnimationEnd oanimationend MSAnimationEnd animationend', function(event) {
			if (settings.onShown) {
				settings.onShown(event);
			}
		});

		setTimeout(function() {
			if (!hasAnimation) {
				if (settings.onShown) {
					settings.onShown(event);
				}
			}
		}, 600);
	},
	bindControls = function($template, settings, plugin) {
		$template.addClass(settings.animate.enter);

		$template.find('[data-growl="dismiss"]').on('click', function() {
			plugin.close();
		});

		$template.on('mouseover', function(e) {
			$template.addClass('hovering');
		}).on('mouseout', function() {
			$template.removeClass('hovering');
		});

		if (settings.delay >= 1) {
			$template.data('growl-delay', settings.delay);
			var timer = setInterval(function() {

				var delay = parseInt($template.data('growl-delay')) - settings.timer;
				if ((!$template.hasClass('hovering') && settings.mouse_over == 'pause') || settings.mouse_over != 'pause') {
					$template.data('growl-delay', delay);
				}

				if (delay <= 0) {
					clearInterval(timer);
					plugin.close();
				}
			}, settings.timer);
		}
	};

	// Avoid Plugin.prototype conflicts
	Plugin.prototype = {
		update: function(command, update) {
			switch (command) {
				case 'icon':
					if (this.settings.icon_type.toLowerCase() == 'class') {
						this.$template.find('[data-growl="icon"]').removeClass(this.settings.content.icon);
						this.$template.find('[data-growl="icon"]').addClass(update);
					}else{
						if (this.$template.find('[data-growl="icon"]').is('img')) {
							this.$template.find('[data-growl="icon"]')
						}else{
							this.$template.find('[data-growl="icon"]').find('img').attr().attr('src', update);
						}
					}
					break;
				case 'url':
					this.$template.find('[data-growl="url"]').attr('href', update);
					break;
				case 'type':
					this.$template.removeClass(function (index, css) {
						return (css.match (/(^|\s)alert-\S+/g) || []).join(' ');
					});
					this.$template.addClass('alert-' + update);
					break;
				default:
					this.$template.find('[data-growl="' + command +'"]').html(update);
			}

			return this;
		},
		close: function() {
			var base = this.$template,
				settings = this.settings,
				posX = base.css(settings.placement.from),
				hasAnimation = false;

			if (settings.onHide) {
				settings.onHide(event);
			}

			base.addClass(this.settings.animate.exit);

			base.nextAll('[data-growl-position="' + this.settings.placement.from + '-' + this.settings.placement.align + '"]').each(function() {
				$(this).css(settings.placement.from, posX);
				posX = (parseInt(posX)+(settings.spacing)) + $(this).outerHeight();
			});

			base.one('webkitAnimationStart oanimationstart MSAnimationStart animationstart', function(event) {
				hasAnimation = true;
			});

			base.one('webkitAnimationEnd oanimationend MSAnimationEnd animationend', function(event) {
				$(this).remove();
				if (settings.onHidden) {
					settings.onHidden(event);
				}
			});

			setTimeout(function() {
				if (!hasAnimation) {
					base.remove();
					if (settings.onHidden) {
						settings.onHidden(event);
					}
				}
			}, 100);

 			return this;
		}
	};

	// A really lightweight plugin wrapper around the constructor,
	// preventing against multiple instantiations
	$.growl = function ( content, options ) {
		if (content == false && options.command == "closeAll") {
			closeAll(options.position);
			return false;
		}else if (content == false) {
			setDefaults(this, options);
			return false;
		}
		var plugin = new Plugin( this, content, options );
		return plugin;
	};

})( jQuery, window, document );
