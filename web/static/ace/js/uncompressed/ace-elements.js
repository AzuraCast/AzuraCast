(function($ , undefined) {
	var multiplible = 'multiple' in document.createElement('INPUT');
    $.fn.ace_file_input = function(options) {
        var settings = $.extend( {
			style:false,
			no_file:'No File ...',
			no_icon:'icon-upload-alt',
			btn_choose:'Choose',
			btn_change:'Change',
			icon_remove:'icon-remove',
			droppable:false,
			thumbnail:false,//large, fit, small
			
			before_change:null,
			before_remove:null
        }, options);

		
		var hasFileList = !!window.FileList;//file list enabled in modern browsers
		this.each(function(){
		
			var that = this;
			var $this = $(this);
			var remove = !!settings.icon_remove;
			
			var multi = $this.attr('multiple') && multiplible;
			var well_style = settings.style == 'well' ? true : false;

			
			$this.wrap("<div class='ace-file-input"+(well_style ? " ace-file-multiple":"")+"' />");
			$this.after('<label data-title="'+settings.btn_choose+'" for="'+$(this).attr('id')+'"><span data-title="'+settings.no_file+'">'+(settings.no_icon ? '<i class="'+settings.no_icon+'"></i>' : '')+'</span></label>'+(remove ? '<a class="remove" href="#"><i class="'+settings.icon_remove+'"></i></a>' : ''));
			var $label = $this.next();
			
			
			if($.browser.mozilla) {//let label click open the file dialog in ff
				$label.on('click', function(){
					if(!that.disabled && !$this.attr('readonly')) $this.click();
				})
			}
			
			if(remove) $label.next('a').on('click', function(){
				var ret = true;
				if(settings.before_remove) ret = settings.before_remove.call(that);
				if(!ret) return false;
				return reset_input();
			});
			

			if(settings.droppable && hasFileList) {
				var dropbox = this.parentNode;
				$(dropbox).on('dragenter', function(e){
					e.preventDefault();
					e.stopPropagation();
				}).on('dragover', function(e){
					e.preventDefault();
					e.stopPropagation();
				}).on('drop', function(e){
					e.preventDefault();
					e.stopPropagation();
 
					var dt = e.originalEvent.dataTransfer;
					var files = dt.files;
					if(!multi && files.length > 1) {//single file upload, but dragged multiple files
						var tmpfiles = [];
						tmpfiles.push(files[0]);
						files = tmpfiles;//keep only first file
					}
					
					var ret = true;
					if(settings.before_change) ret = settings.before_change.call(that, files, true);//true means files have been dropped
					if(!ret || ret.length == 0) {
						return false;
					}
					
					//user can return a modified File Array as result
					if(ret instanceof Array || (hasFileList && ret instanceof FileList)) files = ret;
					
					
					$this.data('ace_input_files', files);//save files data to be used later by user
					$this.data('ace_input_method', 'drop');

					var filenames = [];
					for(var i = 0; i < files.length; i++) filenames.push(files[i].name);
					show_file_list(filenames);
					
					$this.triggerHandler('change' , [true]);//true means inner_call
					return true;
				});
			}
			
			
			
			$this.on('change.inner_call', function(e , inner_call){
				if(inner_call === true) return;//this change event is called from above drop event
				
				var ret = true;
				if(settings.before_change) ret = settings.before_change.call(that, this.files || this.value, false);//false means files have been selected, not dropped
				if(!ret || ret.length == 0) {
					if(!$this.data('ace_input_files')) reset_input_field();//if nothing selected before, reset the newly unacceptable (ret=false) selection
					return false;
				}

				//user can return a modified File Array as result
				var files = (ret instanceof Array || (hasFileList && ret instanceof FileList)) ? ret : this.files;
				$this.data('ace_input_method', 'select');
				
				var filenames = [];
				if(files) {//html5
					$this.data('ace_input_files', files);

					for (var i = 0; i < files.length; i++) {
						var name = $.trim( files[i].name );
						if(!name) continue;
						filenames.push(name);
					}
				}
				else {
					var name = $.trim( this.value );
					if(name) filenames.push(name);
				}

				if(filenames.length == 0) return false;
				show_file_list(filenames);
				
				return true;
			});
			
			
			
			
			var show_file_list = function(filenames) {
				var files = $this.data('ace_input_files');
				//////////////////////////////////////////////////////////////////


				if(well_style) {
					$label.find('span').remove();
					if(!settings.btn_change) $label.addClass('hide-placeholder');
				}
				$label.attr('data-title', settings.btn_change).addClass('selected');
				
				for (var i = 0; i < filenames.length; i++) {
					var filename = filenames[i];
					var index = filename.lastIndexOf("\\") + 1;
					if(index == 0)index = filename.lastIndexOf("/") + 1;
					filename = filename.substr(index);
					
					var fileType = 'icon-file';
					if((/\.(jpe?g|png|gif|svg|bmp|tiff?)$/i).test(filename)) {
						fileType = 'icon-picture';
					}
					else if((/\.(mpe?g|flv|mov|avi|swf|mp4|mkv|webm|wmv|3gp)$/i).test(filename)) fileType = 'icon-film';
					else if((/\.(mp3|ogg|wav|wma|amr|aac)$/i).test(filename)) fileType = 'icon-music';

					if(!well_style) $label.find('span').attr({'data-title':filename}).find('[class*="icon-"]').attr('class', fileType);
					else {
						$label.append('<span data-title="'+filename+'"><i class="'+fileType+'"></i></span>');
						var preview = settings.thumbnail && files && files[i].type.match('image') && !!window.FileReader;
						if(preview) {
							preview_image(files[i], $this);
						}
					}

				}
				
				return true;
			}
			
			
			var preview_image = function(file, input) {
				var $span = $label.find('span:last');
				
				var size = 50;
				if(settings.thumbnail == 'large') size = 150;
				else if(settings.thumbnail == 'fit') size = $span.width();
				
				$span.addClass(size > 50 ? 'large' : '').prepend("<img align='absmiddle' style='display:none;' />");
				var img = $span.find('img:last').get(0);
				
	
				var reader = new FileReader();
				reader.onload = (function(img) {
					return function(e) {
						$(img).one('load', function() {
							
							var thumb = get_thumbnail(img, size, file.type);
							var w = thumb.w, h = thumb.h;
							if(settings.thumbnail == 'small') {w=h=size;};
							$(img).css({'background-image':'url('+thumb.src+')' , width:w, height:h})
									.attr({src:'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQImWNgYGBgAAAABQABh6FO1AAAAABJRU5ErkJggg=='})
									.show();
						});
						img.src = e.target.result;
					};
				})(img);
				reader.readAsDataURL(file);
				reader = null;
			}

			var reset_input = function() {
			  $label.attr({'data-title':settings.btn_choose, 'class':''})
					.find('span:first').attr({'data-title':settings.no_file , 'class':''})
					.find('[class*="icon-"]').attr('class', settings.no_icon)
					.prev('img').remove();
					if(!settings.no_icon) $label.find('[class*="icon-"]').remove();
				
				$label.find('span').not(':first').remove();
				
				if($this.data('ace_input_files')) {
					$this.removeData('ace_input_files');
					$this.removeData('ace_input_method');
				}
				
				reset_input_field();
				
				return false;
			}

			var reset_input_field = function() {
				//http://stackoverflow.com/questions/1043957/clearing-input-type-file-using-jquery/13351234#13351234
				$this.wrap('<form>').closest('form').get(0).reset();
				$this.unwrap();
			}

			var get_thumbnail = function(img, size, type) {
				var canvas = document.createElement('canvas');
				var w = img.width, h = img.height;
				if(w > size || h > size) {
				  if(w > h) {
					h = parseInt(size/w * h);
					w = size;
				  } else {
					w = parseInt(size/h * w);
					h = size;
				  }
				}


				canvas.width = w; canvas.height = h;
				var context = canvas.getContext('2d');
				context.drawImage(img, 0, 0, img.width, img.height, 0, 0, w, h);
				return {src:canvas.toDataURL(type == 'image/jpeg' ? type : 'image/png', 10) , w:w, h:h};
			}

		});
		
		

        return this;
	}

})(jQuery);








(function($ , undefined) {
	$.fn.ace_spinner = function(options) {
		
		//when min is negative, the input maxlength does not account for the extra minus sign
		this.each(function() {
			var icon_up = options.icon_up || 'icon-chevron-up';
			var icon_down = options.icon_down || 'icon-chevron-down';
			
			var btn_up_class = options.btn_up_class || '';
			var btn_down_class = options.btn_down_class || '';
		
			var max = options.max || 999;
			max = (''+max).length;
			var $parent_div = 
				$(this).addClass('spinner-input').css('width' , (max*10)+'px').wrap('<div class="ace-spinner">')
				.after('<div class="spinner-buttons btn-group btn-group-vertical">\
						<span class="btn spinner-up btn-mini '+btn_up_class+'">\
						<i class="'+icon_up+'"></i>\
						</span>\
						<span class="btn spinner-down btn-mini '+btn_down_class+'">\
						<i class="'+icon_down+'"></i>\
						</span>\
						</div>')
				.closest('.ace-spinner').spinner(options);
			

			$(this).on('mousewheel DOMMouseScroll', function(event){
				var delta = event.originalEvent.detail < 0 || event.originalEvent.wheelDelta > 0 ? 1 : -1;
				$parent_div.spinner('step', delta > 0);//accepts true or false as second param
				$parent_div.spinner('triggerChangedEvent');
				return false;
			});
			var that = $(this);
			$parent_div.on('changed', function(){
				that.trigger('change');//trigger the input's change event
			});
			
		});
		
		return this;
	}


})(jQuery);






(function($ , undefined) {
	$.fn.ace_wizard = function(options) {
		
		this.each(function() {
			var $this = $(this);
			var steps = $this.find('li');
			var numSteps = steps.length;
			var width = parseFloat((100 / numSteps).toFixed(1))+'%';
			steps.css({'min-width':width , 'max-width':width});
			
			$this.removeClass('hidden').wizard();

			var buttons = $this.nextAll('.wizard-actions').eq(0);
			var $wizard = $this.data('wizard');
			$wizard.$prevBtn = buttons.find('.btn-prev').eq(0).on('click',  function(){
				$this.wizard('previous');
			});
			$wizard.$nextBtn = buttons.find('.btn-next').eq(0).on('click',  function(){
				$this.wizard('next');
			});
			$wizard.nextText = $wizard.$nextBtn.text();
		});
		
		return this;
	}


})(jQuery);





(function($ , undefined) {
	$.fn.ace_colorpicker = function(options) {
		
		var settings = $.extend( {
			pull_right:false
        }, options);
		
		this.each(function() {
		
			var $that = $(this);
			var colors = '';
			var color = '';
			$(this).hide().find('option').each(function() {
				var $class = 'colorpick-btn';
				if(this.selected) {
					$class += ' selected';
					color = this.value;
				}
				colors += '<li><a class="'+$class+'" href="#" style="background-color:'+this.value+';" data-color="'+this.value+'"></a></li>';
			}).end().on('change.inner_call', function(){
					$(this).next().find('.btn-colorpicker').css('background-color', this.value);
			})
			.after('<div class="dropdown dropdown-colorpicker"><a data-toggle="dropdown" class="dropdown-toggle" href="#"><span class="btn-colorpicker" style="background-color:'+color+'"></span></a><ul class="dropdown-menu dropdown-caret'+(settings.pull_right ? ' pull-right' : '')+'">'+colors+'</ul></div>')
			.next().find('.dropdown-menu').on('click', function(e) {
				var a = $(e.target);
				if(!a.is('.colorpick-btn')) return false;
				a.closest('ul').find('.selected').removeClass('selected');
				a.addClass('selected');
				var color = a.data('color');
				
				$that.val(color).change();
				
				e.preventDefault();
				return true;//if false, dropdown won't hide!
			});
			
			
		});
		return this;
		
	}	
	
	
})(jQuery);
