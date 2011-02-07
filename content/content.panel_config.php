<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

Class contentExtensionDashboardPanel_Config extends AjaxPage {
	
	private $_result = NULL;
	
	public function __construct(&$parent) {
		parent::__construct($parent);
	}
	
	public function generate(){
		echo $this->_result;
		exit;	
	}
	
	public function view() {
		
		if ($_POST) {
			
			$panel = $_POST['panel'];
			$config = $_POST['config'];
			
			$response = new XMLElement('response', NULL, array(
				'id' => $panel['id'],
				'placement' => $panel['placement']
			));
			
			if(isset($_POST['action']['submit'])) {
				
				$panel_id = Extension_Dashboard::savePanel($panel, $config);
				$p = Extension_Dashboard::getPanel($panel_id);
				
				$html = Extension_Dashboard::buildPanelHTML($p);
				$class = $html->getAttribute('class');
				$html->setAttribute('class', $class . ' new-panel');
				
				$response->setValue(
					sprintf('<![CDATA[%s]]>', $html->generate())
				);
			}
			elseif(isset($_POST['action']['delete'])) {
				Extension_Dashboard::deletePanel($panel);
			}
			
			header('Content-Type: text/xml');
			$this->_result = $response->generate();
			
		} else {
			
			$id = $_GET['id'];
			$type = $_GET['type'];

			$container = new XMLElement('div', NULL, array('id' => 'save-panel'));

			$container->appendChild(new XMLElement('div', NULL, array('class' => 'top')));

			$heading = new XMLElement('h3', __('Configuration') . ' <span>' . __('Untitled Panel') . '<span>');
			$container->appendChild($heading);

			$panel_config = Extension_Dashboard::getPanel($id);
			$config_options = Extension_Dashboard::buildPanelOptions($type, $id);

			$primary = new XMLElement('div', NULL, array('class' => 'panel-config'));

			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
			$legend = new XMLElement('legend', __('General'));
			$fieldset->appendChild($legend);
			
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			
			$group->appendChild(Widget::Label(__('Name'),
				Widget::Input('panel[label]', $panel_config['label'])
			));
			$group->appendChild(Widget::Label(__('Placement'), 
				Widget::Select('panel[placement]', array(
					array('primary', ($panel_config['placement'] == 'primary'), __('Main content')),
					array('secondary', ($panel_config['placement'] == 'secondary'), __('Sidebar'))
				))
			));
			$fieldset->appendChild($group);
			$primary->appendChild($fieldset);

			if ($config_options) $primary->appendChild($config_options);

			$actions = new XMLElement('div', NULL, array('class' => 'actions'));
			$actions->appendChild(Widget::Input('action[submit]', __('Save Panel'), 'submit'));
			$actions->appendChild(Widget::Input('action[cancel]', __('Cancel'), 'submit'));
			if ($id) {
				$actions->appendChild(new XMLElement('button', __('Delete Panel'), array(
					'class' => 'delete',
					'name' => 'action[delete]'
				)));
			}
			$primary->appendChild($actions);

			$primary->appendChild(Widget::Input('panel[id]', $id, 'hidden'));
			$primary->appendChild(Widget::Input('panel[type]', $type, 'hidden'));

			$container->appendChild($primary);
			$this->_result = $container->generate();
			
		}
		
	}
	
}