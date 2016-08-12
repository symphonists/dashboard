<?php

require_once(TOOLKIT . '/class.datasourcemanager.php');
require_once(TOOLKIT . '/class.entrymanager.php');
require_once(TOOLKIT . '/class.eventmanager.php');
require_once(TOOLKIT . '/class.pagemanager.php');

Class Extension_Dashboard extends Extension{

	public function install() {
		return Symphony::Database()->query("CREATE TABLE `tbl_dashboard_panels` (
		  `id` int(11) NOT NULL auto_increment,
		  `label` varchar(255) default NULL,
		  `type` varchar(255) default NULL,
		  `config` text,
		  `placement` varchar(255) default NULL,
		  `sort_order` int(11) default '0',
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM");
	}

	public function uninstall() {
		return Symphony::Database()->query("DROP TABLE `tbl_dashboard_panels`");
	}

	
	public function getSubscribedDelegates() {
		return array(
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'InitaliseAdminPageHead',
				'callback'	=> 'append_assets'
			),
			array(
				'page'		=> '/backend/',
				'delegate'	=> 'AdminPagePreGenerate',
				'callback'	=> 'page_pre_generate'
			),
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
			array(
				'page'		=> '/system/authors/',
				'delegate'	=> 'AddDefaultAuthorAreas',
				'callback'	=> 'author_default_section'
			)
		);
	}
	
	public function fetchNavigation() {
		return array(
			array(
				'name'		=> __('Dashboard'),
				'type'		=> 'content',
				'children'	=> array(
					array(
						'link'		=> '/index/',
						'name'		=> __('Dashboard'),
						'visible'	=> 'yes'
					),
				)
			)
		);
	}
	
	public function append_assets($context) {
		$page = Administration::instance()->Page;
		$page->addStylesheetToHead(URL . '/extensions/dashboard/assets/dashboard.backend.css', 'screen', 666);
		$page->addScriptToHead(URL . '/extensions/dashboard/assets/dashboard.backend.js', 667);
	}
	
	public function author_default_section($context) {
		$context['options'][] = array(
			'/extension/dashboard/', //value
			($context['default_area'] == '/extension/dashboard/'), //selected
			__('Dashboard') // label
		);
	}
	
	public static function getPanels() {
		return Symphony::Database()->fetch('SELECT * FROM tbl_dashboard_panels ORDER BY sort_order ASC');
	}
	
	public static function getPanel($panel_id) {
		return Symphony::Database()->fetchRow(0, "SELECT * FROM tbl_dashboard_panels WHERE id='{$panel_id}'");
	}
	
	public static function deletePanel($panel_id) {
		return Symphony::Database()->query("DELETE FROM tbl_dashboard_panels WHERE id='{$panel_id}'");
	}
	
	public static function updatePanelOrder($id, $placement, $sort_order) {
		$sql = sprintf(
			"UPDATE tbl_dashboard_panels SET
			placement = '%s',
			sort_order = '%d'
			WHERE id = '%d'",
			Symphony::Database()->cleanValue($placement),
			Symphony::Database()->cleanValue($sort_order),
			(int)$id
		);
		return Symphony::Database()->query($sql);
	}
	
	public static function savePanel($panel, $config) {
		if (!isset($panel['id']) || empty($panel['id'])) {
			$max_sort_order = (int)reset(Symphony::Database()->fetchCol('max_sort_order', 'SELECT MAX(sort_order) AS `max_sort_order` FROM tbl_dashboard_panels'));
			
			Symphony::Database()->query(sprintf(
				"INSERT INTO tbl_dashboard_panels 
				(label, type, config, placement, sort_order)
				VALUES('%s','%s','%s','%s','%d')",
				Symphony::Database()->cleanValue($panel['label']),
				Symphony::Database()->cleanValue($panel['type']),
				Symphony::Database()->cleanValue(serialize($config)),
				Symphony::Database()->cleanValue($panel['placement']),
				$max_sort_order + 1
			));
			
			return Symphony::Database()->getInsertID();
		}

		else {
			Symphony::Database()->query(sprintf(
				"UPDATE tbl_dashboard_panels SET
				label = '%s',
				config = '%s',
				placement = '%s'
				WHERE id = '%d'",
				Symphony::Database()->cleanValue($panel['label']),
				Symphony::Database()->cleanValue(serialize($config)),
				Symphony::Database()->cleanValue($panel['placement']),
				(int)$panel['id']
			));
			
			return (int)$panel['id'];
			
		}

	}
	
	public static function buildPanelHTML($p) {
		
		$panel = new XMLElement('div', NULL, array('class' => 'panel', 'id' => 'id-' . $p['id']));
		$panel->appendChild(new XMLElement('a', __('Edit'), array('class' => 'panel-edit', 'href' => URL . '/symphony/extension/dashboard/panel_config/?id=' . $p['id'] . '&type=' . $p['type'])));
		$panel->appendChild(new XMLElement('h3', (($p['label'] == '') ? __('Untitled Panel') : $p['label']) . ('<span>'.__('drag to re-order').'</span>')));
		
		$panel_inner = new XMLElement('div', NULL, array('class' => 'panel-inner'));
		
		/**
		* Ask panel extensions to render their panel HTML.
		*
		* @delegate DashboardPanelRender
		* @param string $context
		* '/backend/'
		* @param string $type
		* @param array $config
		* @param XMLElement $panel
		*/
		Symphony::ExtensionManager()->notifyMembers('DashboardPanelRender', '/backend/', array(
			'type'		=> $p['type'],
			'config'	=> unserialize($p['config']),
			'label'		=> $p['label'],
			'id'		=> $p['id'],
			'panel'		=> &$panel_inner
		));
		
		$panel->setAttribute('class', 'panel ' . $p['type']);
		$panel->appendChild($panel_inner);
		
		return $panel;
	}
	
	public static function buildPanelOptions($type, $panel_id, $errors) {
		
		$panel_config = self::getPanel($panel_id);
		$form = null;

		/**
		* Ask panel extensions to render their options HTML.
		*
		* @delegate DashboardPanelOptions
		* @param string $context
		* '/backend/'
		* @param string $type
		* @param XMLElement $form
		* @param array $existing_config
		* @param array $errors
		*/
		Symphony::ExtensionManager()->notifyMembers('DashboardPanelOptions', '/backend/', array(
			'type'				=> $type,
			'form'				=> &$form,
			'existing_config'	=> unserialize($panel_config['config']),
			'label'				=> $panel_config['label'],
			'id'				=> $panel_config['id'],
			'errors'			=> $errors
		));

		return $form;
		
	}
	
	public static function validatePanelOptions($type, $panel_id) {
		
		$panel_config = self::getPanel($panel_id);
		$errors = array();

		/**
		* Ask panel extensions to validate their options.
		*
		* @delegate DashboardPanelValidate
		* @param string $context
		* '/backend/'
		* @param string $type
		* @param array $errors
		* @param array $existing_config
		*/
		Symphony::ExtensionManager()->notifyMembers('DashboardPanelValidate', '/backend/', array(
			'type'				=> $type,
			'errors'			=> &$errors,
			'existing_config'	=> unserialize($panel_config['config']),
			'label'				=> $panel_config['label'],
			'id'				=> $panel_config['id']
		));

		return $errors;
		
	}
	
	public function dashboard_panel_types($context) {
		$context['types']['datasource_to_table'] = __('Data Source to Table');
		$context['types']['rss_reader'] = __('RSS Reader');
		$context['types']['html_block'] = __('HTML Block');
		$context['types']['markdown_text'] = __('Markdown Text');
		$context['types']['symphony_overview'] = __('Symphony Overview');
	}

	public function dashboard_panel_options($context) {
		
		$config = $context['existing_config'];
		
		switch($context['type']) {
			
			case 'datasource_to_table':
				
				$datasources = array();
				foreach(DatasourceManager::listAll() as $ds) {
					$datasources[] = array(
						$ds['handle'],
						($config['datasource'] == $ds['handle']),
						$ds['name']
					);
				}

				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', __('Data Source to Table')));
				
				$label = Widget::Label(__('Data Source'), Widget::Select('config[datasource]', $datasources));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;
				
			break;
			
			case 'rss_reader':
			
				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', __('RSS Reader')));
				
				$label = Widget::Label(__('Feed URL'), Widget::Input('config[url]', $config['url']));
				$fieldset->appendChild($label);
				
				$label = Widget::Label(__('Items to display'), Widget::Select('config[show]',
					array(
						array(
							'label' => __('Full view'),
							'options' => array(
								array('full-all', ($config['show'] == 'full-all'), __('All items')),
								array('full-3', ($config['show'] == 'full-3'), '3 ' . __('items')),
								array('full-5', ($config['show'] == 'full-5'), '5 ' . __('items')),
								array('full-10', ($config['show'] == 'full-10'), '10 ' . __('items'))
							)
						),
						array(
							'label' => __('List view'),
							'options' => array(
								array('list-all', ($config['show'] == 'list-all'), __('All items')),
								array('list-3', ($config['show'] == 'list-3'), '3 ' . __('items')),
								array('list-5', ($config['show'] == 'list-5'), '5 ' . __('items')),
								array('list-10', ($config['show'] == 'list-10'), '10 ' . __('items'))
							)
						),
					)				
				));
				$fieldset->appendChild($label);
				
				$label = Widget::Label(__('Cache (minutes)'), Widget::Input('config[cache]', (string)(int)$config['cache']));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;

			break;
			
			case 'html_block':
			
				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', __('HTML Block')));
				
				$label = Widget::Label(__('Page URL'), Widget::Input('config[url]', $config['url']));
				$fieldset->appendChild($label);
								
				$label = Widget::Label(__('Cache (minutes)'), Widget::Input('config[cache]', (string)(int)$config['cache']));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;

			break;
			
			case 'markdown_text':
			
				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', __('Markdown Text Block')));
				
				$formatters = array();
				foreach(TextformatterManager::listAll() as $tf) {
					$formatters[] = array(
						$tf['handle'],
						($config['formatter'] == $tf['handle']),
						$tf['name']
					);
				}

				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', __('Markdown Text')));
				
				$label = Widget::Label(__('Text Formatter'), Widget::Select('config[formatter]', $formatters));
				$fieldset->appendChild($label);
				
				$label = Widget::Label(__('Text'), Widget::Textarea('config[text]', 6, 25, $config['text']));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;

			break;
		}

	}
		
	public function render_panel($context) {
		
		$config = $context['config'];
		
		switch($context['type']) {
			
			case 'datasource_to_table':

				$ds = DatasourceManager::create($config['datasource'], NULL, false);
				if (!$ds) {
					$context['panel']->appendChild(new XMLElement('div', __(
						'The Data Source with the name <code>%s</code> could not be found.',
						array($config['datasource'])
					)));
					return;
				}
				
				$param_pool = array();
				$xml = $ds->grab($param_pool);
				
				if(!$xml) return;
				$xml = $xml->generate();

				require_once(TOOLKIT . '/class.xsltprocess.php');
				$proc = new XsltProcess();
				$data = $proc->process(
					$xml,
					file_get_contents(EXTENSIONS . '/dashboard/lib/datasource-to-table.xsl')
				);

				$context['panel']->appendChild(new XMLElement('div', $data));
			
			break;
			
			case 'rss_reader':
				
				require_once(TOOLKIT . '/class.gateway.php');
				require_once(CORE . '/class.cacheable.php');
				
				$cache_id = md5('rss_reader_cache' . $config['url']);
				$cache = new Cacheable(Administration::instance()->Database());
				$data = $cache->check($cache_id);

				if(!$data) {
					
						$ch = new Gateway;
						$ch->init();
						$ch->setopt('URL', $config['url']);
						$ch->setopt('TIMEOUT', 6);
						$new_data = $ch->exec();
						$writeToCache = true;
						
						if ((int)$config['cache'] > 0) {
							$cache->write($cache_id, $new_data, $config['cache']);
						}
						
						$xml = $new_data;
						if (empty($xml) && $data) $xml = $data['data'];
					
				} else {
					$xml = $data['data'];
				}
				
				if(!$xml) $xml = '<error>' . __('Error: could not retrieve panel XML feed.') . '</error>';
				
				require_once(TOOLKIT . '/class.xsltprocess.php');
				$proc = new XsltProcess();
				$data = $proc->process(
					$xml,
					file_get_contents(EXTENSIONS . '/dashboard/lib/rss-reader.xsl'),
					array('show' => $config['show'])
				);
				
				$context['panel']->appendChild(new XMLElement('div', $data));
			
			break;
			
			case 'html_block':
				
				require_once(TOOLKIT . '/class.gateway.php');
				require_once(CORE . '/class.cacheable.php');
				
				$cache_id = md5('html_block_' . $config['url']);
				$cache = new Cacheable(Administration::instance()->Database());
				$data = $cache->check($cache_id);

				if(!$data) {
					
						$ch = new Gateway;
						$ch->init();
						$ch->setopt('URL', $config['url']);
						$ch->setopt('TIMEOUT', 6);
						$new_data = $ch->exec();
						$writeToCache = true;
						
						if ((int)$config['cache'] > 0) {
							$cache->write($cache_id, $new_data, $config['cache']);
						}
						
						$html = $new_data;
						if (empty($html) && $data) $html = $data['data'];
					
				} else {
					$html = $data['data'];
				}
				
				if(!$html) $html = '<p class="invalid">' . __('Error: could not retrieve panel HTML.') . '</p>';
				
				$context['panel']->appendChild(new XMLElement('div', $html));
			
			break;
			
			case 'symphony_overview':
				
				$container = new XMLElement('div');
				
				$dl = new XMLElement('dl');
				$dl->appendChild(new XMLElement('dt', __('Website Name')));
				$dl->appendChild(new XMLElement('dd', Symphony::Configuration()->get('sitename', 'general')));
				
				$current_version = Symphony::Configuration()->get('version', 'symphony');
				
				require_once(TOOLKIT . '/class.gateway.php');
				$ch = new Gateway;
				$ch->init();
				$ch->setopt('URL', 'https://api.github.com/repos/symphonycms/symphony-2/tags');
				$ch->setopt('TIMEOUT', $timeout);
				$repo_tags = $ch->exec();
				
				// tags request found
				if(is_array($repo_tags)) {
					$repo_tags = json_decode($repo_tags);
					$tags = array();

					foreach($repo_tags as $tag) {
						// remove tags that contain strings
						if(preg_match('/[a-zA]/i', $tag->name)) continue;
						$tags[] = $tag->name;
					}

					natsort($tags);
					rsort($tags);
					
					$latest_version = reset($tags);
					
				}
				// request for tags failed, assume current version is latest
				else {
					$latest_version = $current_version;
				}

				$needs_update = version_compare($latest_version, $current_version, '>');
				
				$dl->appendChild(new XMLElement('dt', __('Version')));
				$dl->appendChild(new XMLElement(
					'dd',
					$current_version . (($needs_update) ? ' (<a href="http://getsymphony.com/download/releases/version/'.$latest_version.'/">' . __('Latest is %s', array($latest_version)) . "</a>)" : '')
				));
				
				$container->appendChild(new XMLElement('h4', __('Configuration')));
				$container->appendChild($dl);
				
				$entries = 0;
				foreach(SectionManager::fetch() as $section) {
					$entries += EntryManager::fetchCount($section->get('id'));
				}
				
				$dl = new XMLElement('dl');
				$dl->appendChild(new XMLElement('dt', __('Sections')));
				$dl->appendChild(new XMLElement('dd', (string)count(SectionManager::fetch())));
				$dl->appendChild(new XMLElement('dt', __('Entries')));
				$dl->appendChild(new XMLElement('dd', (string)$entries));
				$dl->appendChild(new XMLElement('dt', __('Data Sources')));
				$dl->appendChild(new XMLElement('dd', (string)count(DatasourceManager::listAll())));
				$dl->appendChild(new XMLElement('dt', __('Events')));
				$dl->appendChild(new XMLElement('dd', (string)count(EventManager::listAll())));
				$dl->appendChild(new XMLElement('dt', __('Pages')));
				$dl->appendChild(new XMLElement('dd', (string)count(PageManager::fetch())));
				
				$container->appendChild(new XMLElement('h4', __('Statistics')));
				$container->appendChild($dl);
				
				$context['panel']->appendChild($container);
				
			break;
			
			case 'markdown_text':
				
				$formatter = TextformatterManager::create($config['formatter']);
				$html = $formatter->run($config['text']);

				$context['panel']->appendChild(new XMLElement('div', $html));
			
			break;
			
		}
		
	}
		
}
