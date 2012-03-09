<?php

require_once(TOOLKIT . '/class.administrationpage.php');
require_once(EXTENSIONS . '/dashboard/extension.driver.php');

class contentExtensionDashboardPanel_Config extends AjaxPage {
	protected $panelErrors = array();
	protected $panelConfig = array();
	protected $panelLabel = null;
	protected $panelPlacement = null;
	protected $panelType = null;
	protected $response = null;
	
	public function __construct() {
		parent::__construct();

		// AjaxPage uses 'result' instead of 'response':
		$this->_Result = new XMLElement('response');
		$this->_Result->setIncludeHeader(true);

		$this->panelId = (
			isset($_REQUEST['id'])
				? $_REQUEST['id']
				: null
		);
		$this->panelConfig = (
			isset($_REQUEST['config'])
				? $_REQUEST['config']
				: null
		);
		$this->panelLabel = (
			isset($_REQUEST['label'])
				? $_REQUEST['label']
				: null
		);
		$this->panelPlacement = (
			isset($_REQUEST['placement'])
				? $_REQUEST['placement']
				: null
		);
		$this->panelType = (
			isset($_REQUEST['type'])
				? $_REQUEST['type']
				: null
		);
	}

	public function view() {
		if (isset($_POST['action']['submit'])) {
			$this->panelErrors = Extension_Dashboard::validatePanelOptions(
				$this->panelType, $this->panelId
			);

			if (empty($this->panelErrors)) {
				$this->panelId = Extension_Dashboard::savePanel(
					array(
						'id'		=> $this->panelId,
						'label'		=> $this->panelLabel,
						'placement'	=> $this->panelPlacement,
						'type'		=> $this->panelType
					),
					$this->panelConfig
				);
			}
		}

		else if (isset($_POST['action']['delete'])) {
			Extension_Dashboard::deletePanel($this->panelId);

			$this->_Result->setAttribute(
				'id', $this->panelId
			);
			$this->_Result->setAttribute(
				'placement', $this->panelPlacement
			);

			return;
		}

		if (isset($this->panelId) && !empty($this->panelId)) {
			$this->panelConfig = Extension_Dashboard::getPanel($this->panelId);
			$this->panelLabel = $this->panelConfig['label'];
			$this->panelPlacement = $this->panelConfig['placement'];
		}

		if (isset($_POST['action']['submit']) && empty($this->panelErrors)) {
			$html = Extension_Dashboard::buildPanelHTML($this->panelConfig);
			$class = $html->getAttribute('class');
			$html->setAttribute('class', $class . ' new-panel');

			$this->_Result->setAttribute(
				'id', $this->panelId
			);
			$this->_Result->setAttribute(
				'placement', $this->panelPlacement
			);
			$this->_Result->setValue(
				sprintf('<![CDATA[%s]]>', $html->generate())
			);
		}

		else {
			$this->addHeaderToPage('Content-Type', 'text/html');

			$container = new XMLElement('form');
			$container->setAttribute('id', 'save-panel');
			$container->appendChild(new XMLElement('div', NULL, array('class' => 'top')));

			$heading = new XMLElement('h3', __('Configuration') . ' <span>' . __('Untitled Panel') . '<span>');
			$container->appendChild($heading);

			$config_options = Extension_Dashboard::buildPanelOptions(
				$this->panelType, $this->panelId, $this->panelErrors
			);

			$primary = new XMLElement('div', NULL, array('class' => 'panel-config'));

			$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
			$legend = new XMLElement('legend', __('General'));
			$fieldset->appendChild($legend);
			
			$group = new XMLElement('div', NULL, array('class' => 'group'));
			
			$group->appendChild(Widget::Label(__('Name'),
				Widget::Input('label', $this->panelLabel)
			));
			$group->appendChild(Widget::Label(__('Placement'), 
				Widget::Select('placement', array(
					array(
						'primary',
						($this->panelPlacement == 'primary'),
						__('Main content')
					),
					array(
						'secondary',
						($this->panelPlacement == 'secondary'),
						__('Sidebar')
					)
				))
			));
			$fieldset->appendChild($group);
			$primary->appendChild($fieldset);

			if ($config_options) $primary->appendChild($config_options);

			$actions = new XMLElement('div', NULL, array('class' => 'actions'));
			$actions->appendChild(Widget::Input('action[submit]', __('Save Panel'), 'submit', array('class' => 'button create')));
			$actions->appendChild(Widget::Input('action[cancel]', __('Cancel'), 'submit'));

			if ($this->panelId) {
				$actions->appendChild(new XMLElement('button', __('Delete Panel'), array(
					'class' => 'delete',
					'name' => 'action[delete]'
				)));
			}

			$primary->appendChild($actions);

			$primary->appendChild(Widget::Input('id', $this->panelId, 'hidden'));
			$primary->appendChild(Widget::Input('type', $this->panelType, 'hidden'));

			$container->appendChild($primary);

			$this->_Result = $container;
		}
	}
}