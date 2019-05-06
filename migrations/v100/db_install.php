<?php
/**
 *
* @package phpBB3 Extension - mxpadmin
* @version $Id: db_install.php,v 1.2 2008/10/26 08:36:06 orynider Exp $
* @copyright (c) 2002-2006 [Jon Ohlsson, Mohd Basri, wGEric, PHP Arena, pafileDB, CRLin, FlorinCB] MXP Project Team
* @license http://opensource.org/licenses/gpl-license.php GNU General Public License v2 (GPL-2.0)
 *
 */
 
/**#@+
* @ignore
*/
namespace orynider\mxpadmin\migrations\v100;

use \phpbb\db\migration\container_aware_migration;

/**#@-*/
class db_install extends \phpbb\db\migration\container_aware_migration
{
	/**
	 * Assign migration file dependencies for this migration
	 *
	 * @return void
	 * @access public
	 */
	static public function depends_on()
	{
		//return array('\phpbb\db\migration\data\v31x\v314');
		return array('\phpbb\db\migration\data\v320\v320');
	}

	/**
	 * Add the mxpadmin table schema to the database
	 *
	 * @return void
	 * @access public
	 */
	public function update_schema()
	{
		return array(
			'add_tables'	=> array(
				// --------------------------------------------------------
				// Table structure for table 'phpbb_portal_config'
				$this->table_prefix . 'portal_config'	=> array(
					'COLUMNS'	=> array(
						'portal_id'					=> array('UINT:8', null, 'auto_increment'),
						'portal_path'				=> array('VCHAR:255', ''), //langSubDir ie: 'movies'
						'portal_url'				=> array('MTEXT_UNI', ''),
						'user_id'					=> array('INT:8', 0),
					),
					'PRIMARY_KEY'	=> 'portal_id',
				),
			),
		);
	}

	/**
	 * Add or update data in the database
	 *
	 * @return void
	 * @access public
	 */
	public function update_data()
	{
		return array(
			
			// Add configs
			array('config.add', array('portal_enable', 1)),
			array('config.add', array('portal_version', '0.8.9')),
			
			// Add permissions
			array('permission.add', array('a_mxpadmin_use', true)),
			array('permission.add', array('a_mxpadmin', true)),

			// Set permission
			array('permission.permission_set', array('ADMINISTRATORS', 'a_mxpadmin', 'group')),
			array('permission.permission_set', array('ADMINISTRATORS', 'a_mxpadmin_use', 'group')),
		
			// Insert sample pafildb data
			array('custom', array(array($this, 'insert_sample_data'))),

			// Insert sample pafildb config settings   
			array('custom', array(array(&$this, 'install_config'))),
	
			// Add the ACP module
			array('module.add', array('acp', 'ACP_CAT_DOT_MODS', 'ACP_MXPADMIN',
				array(
					'module_enabled' => 1,
					'module_display' => 1,
					'module_langname' => 'ACP_MXPADMIN',
					'module_auth'=> 'ext_orynider/mxpadmin && acl_a_board',
				)				
			)),			
			// Add extension group to ACP \ Extensions
			// Add Settings link to the extension group
			array('module.add', array(
				'acp', 'ACP_MXPADMIN', array(
					'module_basename'	=> '\orynider\mxpadmin\acp\mxpadmin_module',
					'modes'					=> array('manage'),
				),
			)),
		);
	}

	/**
	 * Drop the mxpadmin table schema from the database
	 *
	 * @return void
	 * @access public
	 */
	public function revert_schema()
	{
		return array(
			'drop_tables'	=> array(
				$this->table_prefix . 'portal_config',
			),
		);
	}

	/**
	 * Custom function query permission roles
	 *
	 * @return void
	 * @access public
	 */
	private function role_exists($role)
	{
		$sql = 'SELECT role_id
			FROM ' . ACL_ROLES_TABLE . "
			WHERE role_name = '" . $this->db->sql_escape($role) . "'";
		$result = $this->db->sql_query_limit($sql, 1);
		$role_id = $this->db->sql_fetchfield('role_id');
		$this->db->sql_freeresult($result);

		return $role_id;
	}
	
	/**
	* Set config value. Creates missing config entry.
	* Only use this if your config value might exceed 255 characters, otherwise please use set_config
	*
	* @param string $config_name Name of config entry to add or update
	* @param mixed $config_value Value of config entry to add or update
	*/
	private function set_mxpadmin_config($board_config_name, $board_config_value, $use_cache = true)
	{
		// Read out config values
		$portal_config = $this->config_values();
		$this->portal_config_table = $this->table_prefix . 'portal_config';
		foreach (self::$configs as $board_config_name => $board_config_value)
		{
			// Read out config values
			if (isset($portal_config[$board_config_name]))
			{
				$sql = 'UPDATE ' . $this->portal_config_table . "
					SET " . $this->db->sql_escape($board_config_name) . " = " . $this->db->sql_escape($board_config_value) . "
					WHERE portal_id = 1";
				$this->db->sql_query($sql);
			}
			else
			{
				$this->mxpadmin_config[$board_config_name] = $portal_config[$board_config_name] = $board_config_value;
				
				$sql = "INSERT INTO ".PORTAL_TABLE." (".
						implode(', ', array_keys($portal_config)).
						") VALUES (".
						implode(', ', array_values($portal_config)).
						")";
				$this->db->sql_query($sql);
			}
		}
	}

	/**
	* install config values. 	
	*/	
	public function install_config()
	{
		// Read out config values
		$portal_config = $this->config_values();
		$this->portal_config_table = $this->table_prefix . 'portal_config';
		foreach (self::$configs as $board_config_name => $board_config_value)
		{
			// Read out config values
			if (isset($portal_config[$board_config_name]))
			{
				$sql = 'UPDATE ' . $this->portal_config_table . "
					SET " . $this->db->sql_escape($board_config_name) . " = " . $this->db->sql_escape($board_config_value) . "
					WHERE portal_id = 1";
				$this->db->sql_query($sql);
			}
			else
			{
				$this->mxpadmin_config[$board_config_name] = $portal_config[$board_config_name] = $board_config_value;
				
				$sql = "INSERT INTO ".PORTAL_TABLE." (".
						implode(', ', array_keys($portal_config)).
						") VALUES (".
						implode(', ', array_values($portal_config)).
						")";
				$this->db->sql_query($sql);
			}
		}
		return true;
	}

	/**
	* Obtain mxpadmin config values
	*/
	public function config_values($use_cache = true)
	{	
		$user = $this->container->get('user');

		if ($this->db_tools->sql_table_exists($this->table_prefix . 'portal_config'))
		{
			//Wile we install only have demo portal_id = 1
			$sql = "SELECT *
				FROM " . $this->table_prefix . "portal_config
				WHERE portal_id = 1";	
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			if (empty($row))
			{
				if (!function_exists('message_die'))
				{
					die("cache::obtain_config(); Couldnt query portal configuration, Allso this hosting or server is using a cache optimizer not compatible with MX-Publisher or just lost connection to database wile query.");
				}
				else
				{
					message_die(GENERAL_ERROR, 'Couldnt query portal configuration', '', __LINE__, __FILE__, $sql);
				}
			}
			foreach ($row as $config_name => $config_value)
			{
				$portal_config[$config_name] = trim($config_value);
			}
			return ($portal_config);
		}
		else
		{
			return array();
		}
	}
	
	static public $is_dynamic = array(
		'portal_id',
		'portal_path',
	);	
	
	static public $configs = array(
		
		//
		// Configs values
		//
		
		// Add configs

		// Add positions to configuration

	);

	/**
	 * Custom function to add sample data to the database
	 *
	 * @return void
	 * @access public
	 */
	public function insert_sample_data()
	{
		$user = $this->container->get('user');
		
		global $phpbb_log, $request;
		$phpbb_log->add($user->data['user_id'], $user->data['user_ip'], time(), 'admin', 'MXPAdmin extension Install/Upgrade', 'Version 1.0.0');
		
		// Define sample article data
		$sample_portal_data = array(
			array(
				'portal_id'					=> 1,
				'portal_path'				=> '../', 
				'portal_url'				=> ($request->server('HTTP_HOST', 'localhost') == 'localhost') ? 'localhost' : (($request->server('SERVER_PORT', 80) == 443 ? 'https' : 'http') . '://' . $request->server('HTTP_HOST', 'localhost')),
				'user_id'					=> $user->data['user_id'],
			)
		);
		
		// Insert sample data
		$this->db->sql_multi_insert($this->table_prefix . 'portal_config', $sample_portal_data);
	}
}
