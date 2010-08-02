# Dashboard
 
Version: 1.1  
Author: Nick Dunn  
Build Date: 2010-08-02  
Requirements: Symphony 2.1.0

## Purpose
To provide a Dashboard summary screen for users. Dashboard "panels" can contain any information. This extension provides the framework for building a Dashboard, and provides four basic panel types. Other extensions can provide their own panel types.

## Installation
 
1. Upload the 'dashboard' folder in this archive to your Symphony 'extensions' folder
2. Enable it by selecting "Dashboard" in the list, choose Enable from the with-selected menu, then click Apply
3. Navigate to the Dashboard from the "Dashboard" link in the primary navigation


## Core panel types

There are five core panel types:

* **Datasource to Table** takes a Symphony Data Source and attempts to render it as an HTML table. This works best with basic fields such as Text Input, Checkboxes and Dates. The first column will link to the entry itself.
* **HTML Block** allows you to specify the URL of a page that outputs a chunk of HTML (a `<div />` perhaps) to include in the panel
* **Markdown Text Block** allows you to add Markdown-formatted text to include in the panel
* **RSS Feed Reader** parses an RSS feed and renders the summary. Useful for latest news or updates.
* **Symphony Overview** renders basic statistics about your installation such as version number and total number of entries.

## Creating your own panel types

To provide panels your extension needs to implement (subscribe to) three delegates:

	public function getSubscribedDelegates() {
		return array(
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelRender',
				'callback'	=> 'render_panel'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelOptions',
				'callback'	=> 'dashboard_panel_options'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'DashboardPanelTypes',
				'callback'	=> 'dashboard_panel_types'
			),
		);
	}

### DashboardPanelTypes

The callback function should return the handle and name of your panel(s):

	public function dashboard_panel_types($context) {
		$context['types']['my_dashboard_panel'] = 'My Amazing Dashboard Panel';
	}

This will create a panel of type `my_dashboard_panel`. Make this name as unique as possible so it doesn't conflict with others.

### DashboardPanelOptions

Each panel has a configuration screen. There are default options for all panels (Label and Position), but you can add additional elements to the configuration form using the `DashboardPanelOptions` delegate:

	public function dashboard_panel_options($context) {
		if ($context['type'] != 'my_dashboard_panel') return;
		
		$config = $context['existing_config'];
	
		$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
		$fieldset->appendChild(new XMLElement('legend', 'My Panel Options'));
	
		$label = Widget::Label('Option 1', Widget::Input('config[option-1]', $config['option-1']));
		$fieldset->appendChild($label);

		$context['form'] = $fieldset;
	
	}

The above code creates a fieldset which will be appended to the panel configuration form. The fielset contains a single textfield "Option 1". The `$config` array contains existing saved config, so you can pre-populate your form fields when editing an existing panel.

Upon saving any form fields prefixed with `config` will be saved with this panel instance, and provided to the panel when it renders.

### DashboardPanelRender

Subscribe to the `DashboardPanelRender` delegate to actually render your panel.

	public function render_panel($context) {
		if ($context['type'] != 'my_dashboard_panel') return;
		
		$config = $context['config'];
		$context['panel']->appendChild(new XMLElement('div', 'The value of Option 1 is: ' . $config['option-1']));
	}

First check that you should output your own panel. `$context['panel']` contains an `XMLElement` that is a panel container to which you can append children. The saved configuration for the panel is presented in the `$context['config']` array.

* * *

## Known issues
* when selecting an item from the Create New menu, the menu does not disappear until it loses focus
* adding more than one Markdown Text Panel produces a PHP error