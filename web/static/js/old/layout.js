/**
 * DF Core Layout jQuery Functions
 */

var is_compact;
var is_narrow;

$(function() {
	/* Auto-header navigation. */
	$('div.navbar a.here').closest('li').addClass('active');
	
	$('div.navbar div.nav-collapse > ul').addClass('nav');
	$('ul.nav li > ul').each(function() {
		$(this).closest('li').addClass('dropdown');
		$(this).prev('a').attr('href', '#').attr('data-toggle', 'dropdown').addClass('dropdown-toggle').append(' <b class="caret"></b>');
		$(this).addClass('dropdown-menu');
	});

	$('div.navbar.navbar-unloaded').removeClass('navbar-unloaded');

	/* Register global AJAX handler. */
	$.ajaxSetup({ global: true });

	$(document.body).ajaxComplete(function(e) {
		initPage(e.target);
	});

	/* Adding classes for resizing */
	handleResize();
	$(window).resize(handleResize);

	initPage($(document.body));
});

function handleResize()
{
	var width = $(window).width();
	is_compact = (width < 768);
	is_narrow = (width < 980);
}

/* Page initialization function. */
function initPage(page) {

	/* Clean up display of forms. */
	$(page).find('form.df-form').not('.df-form-loaded').each(function() {
		$(this).find('input[type="submit"],input[type="reset"],button')
			.not('.mid-form')
			.addClass('btn')
			.wrapAll('<div class="form-actions" />');
		
		$(this).find('button[type="submit"],input[type="submit"]')
			.addClass('btn-primary');

		var input_lists = $(this).find('input[type="checkbox"],input[type="radio"]');
		if (input_lists.length > 0)
		{
			input_lists.closest('label').contents().filter(function() {
	  			return this.nodeType == 3;
	  		}).before(' ').wrap('<span />');
	  	}
	  	
	  	$(this).find('fieldset legend + span.help-block').each(function() {
			$(this).prev('legend').append('<br><small>'+$(this).html()+'</small>');
			$(this).remove();
	  	});

		$(this).find('span.error').closest('div.clearfix').addClass('error');
		
		// Repair smart quotes and other error-causing Unicode elements.
		$(this).submit(function(e) {
			$(this).find('input[type="text"],textarea').each(function() {
				var s = $(this).val();
				
				s = s.replace( /\u2018|\u2019|\u201A|\uFFFD/g, "'" );
				s = s.replace( /\u201c|\u201d|\u201e/g, '"' );
				s = s.replace( /\u02C6/g, '^' );
				s = s.replace( /\u2039/g, '<' );
				s = s.replace( /\u203A/g, '>' );
				s = s.replace( /\u2013/g, '-' );
				s = s.replace( /\u2014/g, '--' );
				s = s.replace( /\u2026/g, '...' );
				s = s.replace( /\u00A9/g, '(c)' );
				s = s.replace( /\u00AE/g, '(r)' );
				s = s.replace( /\u2122/g, 'TM' );
				s = s.replace( /\u00BC/g, '1/4' );
				s = s.replace( /\u00BD/g, '1/2' );
				s = s.replace( /\u00BE/g, '3/4' );
				s = s.replace(/[\u02DC|\u00A0]/g, " ");
				
				$(this).val(s);
			});
		});

		$(this).addClass('df-form-loaded');
	});
	
	/* Bootstrap 2.0 forward compatibility */
	$(page).find('.datatable').addClass('table table-bordered');
	$(page).find('form .actions').addClass('form-actions');

	$(page).find('.btn.primary,.btn.blue').addClass('btn-primary');
	$(page).find('.btn.warning,.btn.yellow').addClass('btn-warning');
	$(page).find('.btn.danger,.btn.red').addClass('btn-danger');
	$(page).find('.btn.success,btn.green').addClass('btn-success');
	$(page).find('.btn.small').addClass('btn-small');
	$(page).find('.btn.large').addClass('btn-large');

	$(page).find('.alert-message').addClass('alert');
	$(page).find('.block-message').addClass('alert-block');
	$(page).find('.alert.info,.alert.blue').addClass('alert-info');
	$(page).find('.alert.danger,.alert.red').addClass('alert-danger');
	$(page).find('.alert.success,alert.green').addClass('alert-success');

	/* Form validation. */
	if (jQuery.fn.validate)
	{
		$(page).find('form.validate').validate();
	}

	/* Tooltips */
	if (jQuery.fn.tooltip)
	{
		$(page).find('a[rel=tooltip]').tooltip({placement: 'right', html: true});
	}

	/* Pagination */
	$(page).find('div.pagination li.disabled, div.pagination li.active').click(function(e) {
		e.preventDefault();
		return false;
	});
	
	/* Automatically add zebra-stripes to tables and lists.
	 * Note that 0-based indexing requires that the "even" and "odd" accessors be switched. */
	$(page).find('table.datatable.zebra tbody tr:nth-child(odd)').removeClass('odd even').addClass('odd');
	$(page).find('table.datatable.zebra tbody tr:nth-child(even)').removeClass('odd even').addClass('even');
	
	$(page).find('dl.zebra:odd,fieldset.zebra:odd').addClass('odd');
	$(page).find('dl.zebra:even,fieldset.zebra:even').addClass('even');
	
	/* Wrappers for confirmation functions. */
	$(page).find('.confirm-action,.confirm-delete,.btn.warning').click(function(e) {
        var thistitle = $(this).attr('title');

        if (thistitle)
            var message = 'Are you sure you want to '+thistitle+'?';
        else
            var message = 'Are you sure you want to complete this action?';
        
        if (!confirm(message))
        {
        	e.preventDefault();
        	return false;
        }
	});
	
	/* Disable submit button to prevent double submissions */
	$(page).find('form').submit(function(){
		if (!$(this).hasClass('no-disable'))
			$(this).find('input[type=submit],button[type=submit]').attr('disabled', 'disabled').addClass('disabled').val('Working...');
	});

	/* Suppress the backspace key. */
	$(page).find('select').keypress(function(event) { return cancelBackspace(event) });
	$(page).find('select').keydown(function(event) { return cancelBackspace(event) });

	/* Apply "equal heights" rules */
	$(page).find('div.equal-heights').each(function() {
		var elements = $(this).find('div[class^=span]');
		elements.equalHeight();
		$(window).resize(function() {
			elements.equalHeight();
		});
	});

	/* Form validation. */
	if (jQuery.fn.fancybox)
	{
		var height = screen.height/2;

		$(page).find('.fancybox').fancybox({
			maxWidth	: 1280,
			maxHeight	: 720,
			autoSize	: true,
			fitToView	: true,
			width		: height * 16/9,
			height		: height,
			aspectRatio : true,
			arrows		: false,
			closeClick	: false,
			closeBtn	: true,
			openEffect	: 'none',
			closeEffect	: 'none',
            helpers     : { media : {} }
		});
	}
}

/* Prevents the backspace key from navigating backwards on dropdown forms. */
function cancelBackspace(event) {
	if (event.keyCode == 8) {
		return false;
	}
}

(function($) {

	/* Make all elements in a group have the same height. */
	$.fn.extend({
	    equalHeight: function() {
	    	if (is_compact)
	    	{
	    		$(this).height('auto');
	    	}
	    	else
	    	{
		        var tallest = 0;
		        $(this).each(function() {
		        	$(this).height('auto');

		            var thisHeight = $(this).height();
		            if(thisHeight > tallest) {
		                tallest = thisHeight;
		            }
		        });

		        $(this).height(tallest);
		    }
	    }
	});

})(jQuery);