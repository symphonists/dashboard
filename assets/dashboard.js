var Dashboard = {
	
	init: function() {
		var self = this;
		
		// Create New button
		jQuery('a.create').bind('click', function(e) {
			e.preventDefault();		
			var panel_type = jQuery('h2 select').val();
			self.showEditForm(panel_type);
		});

		// Edit panel button
		jQuery('.panel a.panel-edit').live('click', function(e) {
			e.preventDefault();		
			var id = jQuery(this).parent().attr('id').replace(/id-/,'');
			var panel_type = jQuery(this).parent().attr('class').replace(/panel /,'');
			self.showEditForm(panel_type, id);
		});

		// Delete panel button
		jQuery('#save-panel button[name="action[delete]"]').live('click', function(e) {
			e.preventDefault();
			var data = jQuery('form').serialize() + '&action[delete]=true';
			self.savePanel(data, 'delete');
		});
		
		// Cancel form button
		jQuery('#save-panel input[name="action[cancel]"]').live('click', function(e) {
			e.preventDefault();
			self.hideEditForm(null, true);
		});
		
		// Save panel button
		jQuery('#save-panel input[name="action[submit]"]').live('click', function(e) {
			e.preventDefault();
			var data = jQuery('form').serialize() + '&action[submit]=true';
			self.savePanel(data, 'save');
		});
		
		// Save panel button (form submit default)
		jQuery('form').bind('submit', function(e) {
			e.preventDefault();
			var data = jQuery('form').serialize() + '&action[submit]=true';
			self.savePanel(data);
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
					url: Symphony.WEBSITE + '/symphony/extension/dashboard/save_order/',
					data: post,
				});
			}
		});

		jQuery('.primary, .secondary').droppable({
			activeClass: 'hover',
			hoverClass: 'active'	
		});
		
	},
	
	hideEditForm: function(callback, enable_dashboard) {
		if (enable_dashboard === true) {
			jQuery('#dashboard').fadeTo('fast', 1);
		}
		jQuery('#save-panel').slideUp(function() {
			jQuery(this).remove();
			if (typeof callback == 'function') callback();
		});
	},
	
	showEditForm: function(panel_type, id) {
		var self = this;
		jQuery.ajax({
			type: 'GET',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/panel_config/?type=' + panel_type + ((id != null) ? ('&id=' + id) : ''),
			success: function(data) {
				var form = jQuery('#save-panel');
				// if form exists in the DOM it needs to be removed first before the new form is revealed
				if (form.length) {
					self.hideEditForm(function() { self.revealEditForm(data); }, false);
				} else {
					self.revealEditForm(data, false);
				}
			}
		});
	},
	
	revealEditForm: function(html) {
		// fade down dashboard panels to give edit form more priority
		jQuery('#dashboard').fadeTo('fast', 0.5);
		// append form to page (hidden with CSS)
		jQuery('h2').after(html);
		jQuery('#save-panel').slideDown();
	},
	
	savePanel: function(post_data, action) {
		var self = this;
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/panel_config/',
			data: post_data,
			success: function(data) {
				switch (action) {
					case 'delete':
						var id = jQuery('response', data).attr('id');
						self.hideEditForm(function() {
							jQuery('#id-' + id).slideUp('slow', function() {
								jQuery(this).remove();
							});
						}, true);
					break;
					case 'save':
						var id = jQuery('response', data).attr('id');
						var placement = jQuery('response', data).attr('placement');
						// insert new panel
						if (id == '') {
							self.hideEditForm(function() {
								var html = jQuery('response', data).text();
								jQuery('.' + placement).append(html);
								jQuery('.new-panel').slideDown('fast', function() {
									jQuery(this).removeClass('new-panel');
								})
							}, true);
						}
						// update
						else {
							self.hideEditForm(function() {
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
	
};

jQuery(document).ready(function() {
	Dashboard.init();
});