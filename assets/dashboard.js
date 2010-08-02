var Dashboard = {
	
	$dashboard: null,
	$h2: null,
	
	init: function() {
		var self = this;
		
		this.$dashboard = jQuery('#dashboard');
		this.$h2 = jQuery('h2:first');
		
		// Create New button
		jQuery('a.create').bind('click', function(e) {
			e.preventDefault();		
			var panel_type = self.$h2.find('select').val();
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
			self.savePanel(jQuery('form').serialize(), 'delete');
		});
		
		// Cancel form button
		jQuery('#save-panel input[name="action[cancel]"]').live('click', function(e) {
			e.preventDefault();
			self.hideEditForm(null, true);
		});
		
		// Save panel button
		jQuery('#save-panel input[name="action[submit]"]').live('click', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('form').serialize(), 'submit');
		});
		
		// Save panel button (form submit default)
		jQuery('form').bind('submit', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('form').serialize(), 'submit');
		});
		
		jQuery('.primary, .secondary').sortable({
			items: '.panel',
			connectWith: '.sortable-container',
			placeholder: 'panel-placeholder',
			handle: '> h3',
			revert: 200,
			stop: function() {
				self.saveReordering();
			}
		});

		jQuery('.primary, .secondary').droppable({
			activeClass: 'hover',
			hoverClass: 'active'	
		});
		
	},
	
	saveReordering: function() {
		
		var post_data = '';
		var i = 0;
		
		jQuery('.primary, .secondary').each(function(j) {
			var sort_order = 1;
			jQuery('.panel', this).each(function() {
				post_data += 'panel[' + i + '][id]=' + jQuery(this).attr('id').replace(/^id-/,'') + '&';
				post_data += 'panel[' + i + '][placement]=' + ((j == 0) ? 'primary' : 'secondary') + '&';
				post_data += 'panel[' + i + '][sort_order]=' + sort_order++ + '&';
				i++;
			});
		});
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/save_order/',
			data: post_data,
		});
		
	},
	
	hideEditForm: function(callback, enable_dashboard) {
		if (enable_dashboard === true) this.$dashboard.fadeTo('fast', 1);
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
				// if form exists in the DOM it needs to be removed first
				// before the new form is revealed
				if (form.length) {
					self.hideEditForm(function() {
						self.revealEditForm(data);
					}, false);
				} else {
					self.revealEditForm(data);
				}
			}
		});
	},
	
	revealEditForm: function(html) {
		// fade down dashboard panels to give edit form more priority
		this.$dashboard.fadeTo('fast', 0.5);
		// append form to page (hidden with CSS)
		this.$h2.after(html);
		jQuery('#save-panel').slideDown();
	},
	
	savePanel: function(post_data, action) {
		var self = this;
		
		post_data += '&action[' + action + ']=true';
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.WEBSITE + '/symphony/extension/dashboard/panel_config/',
			data: post_data,
			success: function(data) {
				
				var id = jQuery('response', data).attr('id');
				var placement = jQuery('response', data).attr('placement');
				
				var html = jQuery('response', data).text();
				var panel = jQuery('#id-' + id);
				
				switch (action) {
					
					case 'delete':
						self.hideEditForm(function() {
							panel.slideUp('slow', function() {
								jQuery(this).remove();
							});
						}, true);
					break;
					
					case 'submit':
						// insert new panel
						if (id == '') {
							self.hideEditForm(function() {
								jQuery('.' + placement).append(html);
								jQuery('.new-panel').slideDown('fast', function() {
									jQuery(this).removeClass('new-panel');
								})
							}, true);
						}
						// update
						else {
							self.hideEditForm(function() {
								var column = panel.parent();
								var column_name = ((panel.parent().hasClass('primary')) ? 'primary' : 'secondary');
								panel.fadeOut('fast', function() {
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