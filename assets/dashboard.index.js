/*-----------------------------------------------------------------------------
Language strings
-----------------------------------------------------------------------------*/	 

Symphony.Language.add({
	'Untitled Panel': false,
}); 


/*-----------------------------------------------------------------------------
Dashboard
-----------------------------------------------------------------------------*/
var Dashboard = {
	
	dashboard: null,
	edit_mode: false,
	
	init: function() {
		
		var self = this;

		this.dashboard = jQuery('#dashboard');
		this.drawer = jQuery('#drawer-dashboard');
		
		// Edit Mode button
		jQuery('#context').on('click', 'a.edit-mode', function(e) {
			e.preventDefault();		
			self.edit_mode = !self.edit_mode;
			
			var text = jQuery(this).text();
			var title = jQuery(this).attr('title');
			
			jQuery(this).text(title).attr('title', text);
			jQuery(this).toggleClass('selected');
			
			if (self.edit_mode === true) {
				self.dashboard.addClass('edit');
				jQuery('.primary, .secondary').sortable('enable');
			} else {
				self.dashboard.removeClass('edit');
				jQuery('.primary, .secondary').sortable('disable');
			}
			
		});
		
		// Create New button
		jQuery('#context').on('change', 'select[name="panel-type"]', function(e) {
			e.preventDefault();
			var type = jQuery(this).val();
			if(type === '') return;
			self.showEditForm(type);
		});

		// Delete panel button
		jQuery('#context').on('click', '#drawer-dashboard button[name="action[delete]"]', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('#context form').serialize(), 'delete');
		});
		
		// Cancel form button
		jQuery('#context').on('click', '#drawer-dashboard input[name="action[cancel]"]', function(e) {
			e.preventDefault();
			self.resetPanelType();
			self.hideEditForm(null, true);
		});
		
		// Save panel button
		jQuery('#context').on('click', '#drawer-dashboard input[name="action[submit]"]', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('#context form').serialize(), 'submit');
		});
		
		// Save panel button (form submit default)
		jQuery('#context').on('submit', '#drawer-dashboard form', function(e) {
			e.preventDefault();
			self.savePanel(jQuery('#context #drawer-dashboard form').serialize(), 'submit');
		});
		
		// Edit panel button
		jQuery('#dashboard').on('click', '.panel a.panel-edit', function(e) {
			e.preventDefault();		
			var id = jQuery(this).parent().attr('id').replace(/id-/,'');
			var panel_type = jQuery(this).parent().attr('class').replace(/panel /,'');
			self.showEditForm(panel_type, id);
		});
		
		jQuery('.primary, .secondary').sortable({
			items: '.panel',
			connectWith: '.sortable-container',
			placeholder: 'panel-placeholder',
			handle: '> h3',
			revert: 200,
			disabled: true,
			start: function(event, ui) {
				jQuery('.panel-placeholder').height(ui.helper.height() + parseInt(ui.helper.css('padding-top')) + parseInt(ui.helper.css('padding-bottom')) + parseInt(ui.helper.css('border-top-width')) + parseInt(ui.helper.css('border-bottom-width')));
			},
			stop: function() {
				self.saveReordering();
			}
		});

		jQuery('.primary, .secondary').droppable({
			activeClass: 'hover',
			hoverClass: 'active'	
		});
		
		// Panel name
		jQuery('#context').on('keyup change', '#drawer-dashboard input[name="label"]', function(e) {
			var name = jQuery(e.target).val();
			var title = jQuery('#drawer-dashboard h3 span');
			if(name) {
				title.text(name);
			}
			else {
				title.text(Symphony.Language.get('Untitled Panel'));
			}
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
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/save_order/',
			data: post_data,
		});
		
	},
	
	resetPanelType: function() {
		jQuery('#context select[name="panel-type"]').val('');
	},
	
	hideEditForm: function(callback, enable_dashboard) {
		var self = this;
		if (enable_dashboard === true) this.dashboard.fadeTo('fast', 1);
		this.drawer
			.unbind('collapsestop.drawer')
			.bind('collapsestop.drawer', function() {
				if (typeof callback == 'function') {
					callback();
				}
			});
		this.drawer.trigger('collapse.drawer');
	},
	
	showEditForm: function(panel_type, id) {
		var self = this;
		jQuery.ajax({
			type: 'GET',
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/panel_config/?type=' + panel_type + ((id != null) ? ('&id=' + id) : ''),
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
				
				// Update title
				//jQuery('#save-panel input[name="panel[label]"]').change();
			}
		});
	},
	
	revealEditForm: function(html) {
		// fade down dashboard panels to give edit form more priority
		this.dashboard.fadeTo('fast', 0.25);
		this.drawer.find('.contents').empty().html(html);
		this.drawer.trigger('expand.drawer');
	},
	
	savePanel: function(post_data, action) {
		var self = this;
		
		post_data += '&action[' + action + ']=true';
		
		jQuery.ajax({
			type: 'POST',
			url: Symphony.Context.get('root') + '/symphony/extension/dashboard/panel_config/',
			data: post_data,
			success: function(data) {
				// Must be an error, show the form again:
				if (jQuery('response', data).length == 0) {
					if (jQuery('#save-panel').length) {
						self.hideEditForm(function() {
							self.revealEditForm(data);
						}, false);
					}
					else {
						self.revealEditForm(data);
					}
					return;
				}

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
						self.resetPanelType();
						// insert new panel
						if (panel.length == 0) {
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
									jQuery('#id-' + id).hide().removeClass('new-panel').fadeIn('fast');
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