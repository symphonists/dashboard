<?php

Class Extension_Dashboard extends Extension{
	
	private $root_url = '/symphony/extension/dashboard/index/';
	
	public function about() {
		return array('name' => 'Dashboard',
					 'version' => '0.1',
					 'release-date' => '2010-07-16',
					 'author' => array('name' => 'Nick Dunn',
									   'website' => 'http://nick-dunn.co.uk',
									   'email' => ''),
						'description'   => 'Provide a dashboard for your users'
			 		);
	}

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
				'delegate'	=> 'ExtensionsAddToNavigation',
				'callback'	=> 'add_navigation'
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
		);
	}
	
	
	public function add_navigation($context) {
		$context['navigation'][-1] = array(
			'name'		=> __('Dashboard'),
			'index'		=> '1',
			'children'	=> array(
				array(
					'link'		=> '/' . preg_replace('/\/symphony\//', '', $this->root_url),
					'name'		=> __('Dashboard'),
					'visible'	=> 'yes'
				),
			),
		);
	}
	
	public function page_pre_generate($context) {
		// when arriving after logging-in, redirect to Dashboard
		if (preg_match('/\/symphony\/$/', $_SERVER['HTTP_REFERER'])) redirect($this->root_url);
	}
	
	public static function getPanels() {
		return Symphony::Database()->fetch('SELECT * FROM tbl_dashboard_panels ORDER BY sort_order ASC');
	}
	
	public static function getPanel($panel_id) {
		return Symphony::Database()->fetchRow(0, "SELECT * FROM tbl_dashboard_panels WHERE id='{$panel_id}'");
	}
	
	public static function deletePanel($panel) {
		return Symphony::Database()->query("DELETE FROM tbl_dashboard_panels WHERE id='{$panel['id']}'");
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
	
	public static function savePanel($panel=NULL, $config=NULL) {
		if ($panel['id'] == '') {
			
			return Symphony::Database()->query(sprintf(
				"INSERT INTO tbl_dashboard_panels 
				(label, type, config, placement, sort_order)
				VALUES('%s','%s','%s','%s','%d')",
				Symphony::Database()->cleanValue($panel['label']),
				Symphony::Database()->cleanValue($panel['type']),
				serialize($config),
				Symphony::Database()->cleanValue($panel['placement']),
				0
			));
			
		} else {
			
			return Symphony::Database()->query(sprintf(
				"UPDATE tbl_dashboard_panels SET
				label = '%s',
				config = '%s',
				placement = '%s'
				WHERE id = '%d'",
				Symphony::Database()->cleanValue($panel['label']),
				serialize($config),
				Symphony::Database()->cleanValue($panel['placement']),
				(int)$panel['id']
			));
			
		}

	}
	
	public static function buildPanelOptions($type, $panel_id) {
		
		$panel_config = self::getPanel($panel_id);
		
		$form = null;
		Administration::instance()->ExtensionManager->notifyMembers('DashboardPanelOptions', '/backend/', array(
			'type'				=> $type,
			'form'				=> &$form,
			'existing_config'	=> unserialize($panel_config['config'])
		));

		return $form;
		
	}
	
	public function dashboard_panel_types($context) {
		$context['types']['datasource_to_table'] = 'Datasource to Table';
		$context['types']['rss_reader'] = 'RSS Feed Reader';
		$context['types']['symphony_overview'] = 'Symphony Overview';
	}

	public function dashboard_panel_options($context) {
		
		$config = $context['existing_config'];
		
		switch($context['type']) {
			
			case 'datasource_to_table':
				
				require_once(TOOLKIT . '/class.datasourcemanager.php');
				$dsm = new DatasourceManager(Administration::instance());
				$datasources = array();
				foreach($dsm->listAll() as $ds) $datasources[] = array($ds['handle'], ($config['datasource'] == $ds['handle']), $ds['name']);

				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', 'Data Source to Table'));
				
				$label = Widget::Label('Data Source', Widget::Select('config[datasource]', $datasources));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;
				
			break;
			
			case 'rss_reader':
			
				$fieldset = new XMLElement('fieldset', NULL, array('class' => 'settings'));
				$fieldset->appendChild(new XMLElement('legend', 'RSS Reader'));
				
				$label = Widget::Label('Feed URL', Widget::Input('config[url]', $config['url']));
				$fieldset->appendChild($label);
				
				$label = Widget::Label('Items to display', Widget::Select('config[show]', array(
					array('all', ($config['show'] == 'all'), 'All items'),
					array('3', ($config['show'] == '3'), '3 items'),
					array('5', ($config['show'] == '5'), '5 items'),
					array('10', ($config['show'] == '10'), '10 items')
				)));
				$fieldset->appendChild($label);
				
				$label = Widget::Label('Cache feed XML (minutes)', Widget::Input('config[cache]', (int)$config['cache']));
				$fieldset->appendChild($label);

				$context['form'] = $fieldset;

			break;
		}

	}
		
	public function render_panel($context) {
		
		$config = $context['config'];
		
		switch($context['type']) {
			
			case 'datasource_to_table':

				require_once(TOOLKIT . '/class.datasourcemanager.php');
				$dsm = new DatasourceManager(Administration::instance());

				$ds = $dsm->create($config['datasource'], NULL, false);
				$xml = $ds->grab()->generate();

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
				
				require_once(TOOLKIT . '/class.xsltprocess.php');
				$proc = new XsltProcess();
				$data = $proc->process(
					$xml,
					file_get_contents(EXTENSIONS . '/dashboard/lib/rss-reader.xsl'),
					array('show' => $config['show'])
				);
				
				$context['panel']->appendChild(new XMLElement('div', $data));
			
			break;
			
			case 'symphony_overview':
				
				$container = new XMLElement('div');
				
				$dl = new XMLElement('dl');
				$dl->appendChild(new XMLElement('dt', 'Site name'));
				$dl->appendChild(new XMLElement('dd', Symphony::Configuration()->get('sitename', 'general')));
				$dl->appendChild(new XMLElement('dt', 'Version'));
				$dl->appendChild(new XMLElement('dd', Symphony::Configuration()->get('version', 'symphony')));
				$container->appendChild(new XMLElement('h4', 'Configuration'));
				$container->appendChild($dl);
				
				require_once(TOOLKIT . '/class.datasourcemanager.php');
				$dsm = new DatasourceManager(Administration::instance());
				
				require_once(TOOLKIT . '/class.eventmanager.php');
				$em = new EventManager(Administration::instance());
				
				require_once(TOOLKIT . '/class.sectionmanager.php');
				$sm = new SectionManager(Administration::instance());
				$sections = $sm->fetch();
				
				$entries = Administration::instance()->Database()->fetchRow(0, "SELECT count(id) AS `count` FROM tbl_entries");
				
				$pages = Administration::instance()->Database()->fetchRow(0, "SELECT count(id) AS `count` FROM tbl_pages");
				
				$dl = new XMLElement('dl');
				$dl->appendChild(new XMLElement('dt', 'Sections'));
				$dl->appendChild(new XMLElement('dd', count($sections)));
				$dl->appendChild(new XMLElement('dt', 'Entries'));
				$dl->appendChild(new XMLElement('dd', $entries['count']));
				$dl->appendChild(new XMLElement('dt', 'Data Sources'));
				$dl->appendChild(new XMLElement('dd', count($dsm->listAll())));
				$dl->appendChild(new XMLElement('dt', 'Events'));
				$dl->appendChild(new XMLElement('dd', count($em->listAll())));
				$dl->appendChild(new XMLElement('dt', 'Pages'));
				$dl->appendChild(new XMLElement('dd', $pages['count']));
				
				$container->appendChild(new XMLElement('h4', 'Statistics'));
				$container->appendChild($dl);
				
				$context['panel']->appendChild($container);
				
			break;
			
		}
		
	}
		
}