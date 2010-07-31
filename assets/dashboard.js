jQuery(document).ready(function() {
	
	/* 
		* rewrite as a clean object rather than standalone functions
		* refine selectors to prevent re-selection
		* allow toggling of Edit controls
	*/
	
	jQuery('a.create').bind('click', function(e) {
		e.preventDefault();		
		var type = jQuery('h2 select').val();
		showEditForm(null, type);
	});
	
	jQuery('.panel a.panel-edit').live('click', function(e) {
		e.preventDefault();		
		var id = jQuery(this).parent().attr('id').replace(/id-/,'');
		var type = jQuery(this).parent().attr('class').replace(/panel /,'');
		showEditForm(id, type);
	});
	
	jQuery('#save-panel button[name="action[delete]"]').live('click', function(e) {
		e.preventDefault();
		var data = jQuery('form').serialize() + '&action[delete]=true';
		savePanel(data, 'delete');
	});
	
	jQuery('#save-panel input[name="action[cancel]"]').live('click', function(e) {
		e.preventDefault();
		hideEditForm(null, true);
	});
	
	jQuery('#save-panel input[name="action[submit]"]').live('click', function(e) {
		e.preventDefault();
		var data = jQuery('form').serialize() + '&action[submit]=true';
		savePanel(data, 'save');
	});
	
	jQuery('form').bind('submit', function(e) {
		e.preventDefault();
		var data = jQuery('form').serialize() + '&action[submit]=true';
		savePanel(data);
	});
	
	function hideEditForm(callback, enable_dashboard) {
		if (enable_dashboard === true) {
			jQuery('#dashboard').fadeTo('fast', 1);
		}
		jQuery('#save-panel').slideUp(function() {
			jQuery(this).remove();
			if (typeof callback == 'function') callback();
		});
	}
	
	function showEditForm(id, type) {
		jQuery.ajax({
			type: 'GET',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/panel_config/?type=' + type + ((id != null) ? ('&id=' + id) : ''),
			success: function(data) {
				var form = jQuery('#save-panel');
				// if form exists in the DOM it needs to be removed first before the new form is revealed
				if (form.length) {
					hideEditForm(function() { revealEditForm(data); }, false);
				} else {
					revealEditForm(data, false);
				}
			}
		});
	}
	
	function revealEditForm(html) {
		// fade down dashboard panels to give edit form more priority
		jQuery('#dashboard').fadeTo('fast', 0.5);
		// append form to page (hidden with CSS)
		jQuery('h2').after(html);
		jQuery('#save-panel').slideDown();
	}
	
	function savePanel(post_data, action) {
		jQuery.ajax({
			type: 'POST',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/panel_config/',
			data: post_data,
			success: function(data) {
				switch (action) {
					case 'delete':
						var id = jQuery('response', data).attr('id');
						hideEditForm(function() {
							jQuery('#id-' + id).fadeOut('slow', function() {
								jQuery(this).remove();
							});
						}, true);
					break;
					case 'save':
						var id = jQuery('response', data).attr('id');
						var placement = jQuery('response', data).attr('placement');
						// insert new panel
						if (id == '') {
							hideEditForm(function() {
								var html = jQuery('response', data).text();
								jQuery('.' + placement).append(html);
							}, true);
						}
						// update
						else {
							hideEditForm(function() {
								var html = jQuery('response', data).text();
								var panel = jQuery('#id-' + id);
								var column = panel.parent();
								var column_name = ((panel.parent().hasClass('primary')) ? 'primary' : 'secondary');
								jQuery('#id-' + id).fadeOut('fast', function() {
									if (placement == column_name) {
										jQuery(this).after(html).remove();
									} else {
										jQuery(this).remove();
										jQuery('.' + placement).append(html);
									}
									jQuery('#id-' + id).hide().fadeIn('fast');
								});
							}, true);
						}
					break;
				}
			}
		});
	}
		
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
				url: Symphony.WEBSITE + '/symphony/extension/dashboard/save_order/',
				data: post,
			});
		}
	});
	
	jQuery('.primary, .secondary').droppable({
		activeClass: 'hover',
		hoverClass: 'active'	
	});
	
});