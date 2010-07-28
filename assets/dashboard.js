jQuery(document).ready(function() {
	
	if (jQuery('.panel-config').length) {
		jQuery('form').css('margin-top', '1em');
		jQuery('html, body').css('height', 'auto');
		jQuery('h1').remove();
		jQuery('#nav').remove();
		jQuery('#usr').remove();
		jQuery('input:first').focus();
	}
	
	jQuery('a.create').bind('click', function() {
		jQuery(this).attr('href', '/symphony/extension/dashboard/panel_config/?type=' + jQuery('h2 select').val());
	})
	
	jQuery("a.panel-edit, a.create").fancybox({
		'width' : 700,
		'height' : 400,
		'autoScale' : false,
		'transitionIn' : 'none',
		'transitionOut' : 'none',
		'type' : 'iframe'
	});
	
	jQuery('.primary, .secondary').sortable({
		items: '.panel',
		connectWith: '.sortable-container',
		placeholder: 'panel-placeholder',
		handle: '> h3',
		revert: 200,
		stop: function(event, ui) {
			var post = '';
			var i = 0;
			jQuery('.primary, .secondary').each(function(j) {
				var sort_order = 1;
				jQuery('.panel', this).each(function() {
					post += 'panel[' + i + '][id]=' + jQuery(this).attr('id').replace(/^id-/,'') + '&';
					post += 'panel[' + i + '][placement]=' + ((j == 0) ? 'primary' : 'secondary') + '&';
					post += 'panel[' + i + '][sort_order]=' + sort_order++ + '&';
					i++;
				});
			});
			jQuery.ajax({
				type: 'POST',
				url: '/symphony/extension/dashboard/save_order/',
				data: post,
			});
		}
	});
	
	jQuery('.primary, .secondary').droppable({
		activeClass: 'hover',
		hoverClass: 'active'	
	});
	
});