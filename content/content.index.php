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
		
		$this->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard.css', 'screen', 948131);
		$this->addScriptToHead(URL . '/extensions/dashboard/assets/dashboard.js', 948131);
		
		$heading = new XMLElement('h2', __('Dashboard'));
		$create_new = new XMLElement('a', __('Create New Panel'), array(
			'class'	=> 'create button',
			'href'	=> '#'
		));

		$heading->appendChild($create_new);
		$this->Form->appendChild($heading);
		
		$container = new XMLElement('div', NULL, array('id' => 'dashboard'));
		
		$primary = new XMLElement('div', NULL, array('class' => 'primary'));
		$secondary = new XMLElement('div', NULL, array('class' => 'secondary'));
		
		$panels = Extension_Dashboard::getPanels();
		
		foreach($panels as $p) {
			
			$panel = new XMLElement('div', NULL, array('class' => 'panel', 'id' => 'id-' . $p['id']));
			
			Administration::instance()->ExtensionManager->notifyMembers('RenderDashboardPanel', '/backend/', array(
				'type'		=> $p['type'],
				'config'	=> unserialize($p['config']),
				'panel'		=> &$panel
			));
			
			switch($p['placement']) {
				case 'primary': $primary->appendChild($panel); break;
				case 'secondary': $secondary->appendChild($panel); break;
			}
			
		}
		
		$container->appendChild($primary);
		$container->appendChild($secondary);		
		$this->Form->appendChild($container);
		
	}
	
}