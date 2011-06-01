<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardIndex extends AdministrationPage {
	
	public function __construct(&$parent) {
		parent::__construct($parent);
	}
	
	public function __viewIndex() {
		
		$this->setPageType('form');
		$this->setTitle(__('Symphony') . ' &ndash; ' . __('Dashboard'));
		
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/jquery-ui-1.8.2.custom.min.js', 29421);
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard.index.css', 'screen', 29422);
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/dashboard.index.js', 29423);
		
		// Add welcome message
		$hour = date('H');
		$welcome = __('Nice to meet you');
		if(Administration::instance()->Author->get('last_seen') != NULL) {
			if($hour < 10) $welcome = __('Good morning');
			elseif($hour < 17) $welcome = __('Welcome back');
			else $welcome = __('Good evening');
		}
		$heading = new XMLElement('h2', $welcome . ', ' . Administration::instance()->Author->get('first_name'));
		
		// Create new button
		$create_new = new XMLElement('a', __('Create New'), array(
			'class'	=> 'create button',
			'href'	=> '#',
			'id'	=> 'select-panel-type'
		));
	
		$panel_types = array();

		/**
		* Ask panel extensions to list their panel types.
		*
		* @delegate DashboardPanelTypes
		* @param string $context
		* '/backend/'
		* @param array $types
		*/
		Administration::instance()->ExtensionManager->notifyMembers('DashboardPanelTypes', '/backend/', array(
			'types' => &$panel_types
		));
		
		$panel_types_options = array();
		ksort($panel_types);
		foreach($panel_types as $handle => $name) {
			$panel_types_options[] = array($handle, false, $name);
		}
	
		$heading->appendChild($create_new);
		$heading->appendChild(Widget::Select('panel-type', $panel_types_options));
		
		if(Administration::instance()->Author->isDeveloper()) {
			$heading->appendChild(
				new XMLElement('a', __('Enable Editing'), array(
					'class'	=> 'edit-mode button',
					'href'	=> '#',
					'title' => __('Disable Editing')
				))
			);
		}
		
		$this->Form->appendChild($heading);
		
		$container = new XMLElement('div', NULL, array('id' => 'dashboard'));
		
		$primary = new XMLElement('div', NULL, array('class' => 'primary sortable-container'));
		$secondary = new XMLElement('div', NULL, array('class' => 'secondary sortable-container'));
		
		$panels = Extension_Dashboard::getPanels();
		
		foreach($panels as $p) {
			
			$html = Extension_Dashboard::buildPanelHTML($p);
			
			switch($p['placement']) {
				case 'primary': $primary->appendChild($html); break;
				case 'secondary': $secondary->appendChild($html); break;
			}
			
		}
		
		$container->appendChild($primary);
		$container->appendChild($secondary);		
		$this->Form->appendChild($container);
		
	}
	
}