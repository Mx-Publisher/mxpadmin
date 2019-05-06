<?php
/**
*
* @package phpBB Extension - mxpadmin
* @version $Id: admin_controller.php,v 1.53 2014/05/19 18:14:40 orynider Exp $
* @copyright (c) 2002-2008 MX-Publisher Project Team
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2 (GPL-2.0)
* @link http://mxpcms.sourceforge.net/
*
*/

namespace orynider\mxpadmin\controller;

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

class admin_controller
{
	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \phpbb\cache\cache */
	protected $cache;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\extension\manager "Extension Manager" */
	protected $ext_manager;

	/** @var \phpbb\pagination */
	protected $pagination;

	/** @var ContainerBuilder */
	protected $phpbb_container;

	/** @var \phpbb\path_helper */
	protected $path_helper;

	/** @var string */
	protected $php_ext;

	/** @var string phpBB root path */
	protected $root_path;

	/**
	* The database tables
	*
	* @var string
	*/
	protected $portal_config_table;

	/** @var \phpbb\files\factory */
	protected $files_factory;

	/**
	* Constructor
	*
	* @param \phpbb\template\template		 			$template
	* @param \phpbb\user											$user
	* @param \phpbb\log											$log
	* @param \phpbb\cache\service							$cache
	* @param \phpbb\db\driver\driver_interface			$db
	* @param \phpbb\request\request		 				$request
	* @param \phpbb\pagination								$pagination
	* @param \phpbb\extension\manager					$ext_manager
	* @param \phpbb\path_helper								$path_helper
	* @param string 													$php_ext
	* @param string 													$root_path
	* @param \phpbb\files\factory								$files_factory
	*
	*/
	public function __construct(
		\phpbb\template\template $template,
		\phpbb\user $user,
		\phpbb\log\log $log,
		\phpbb\cache\service $cache,
		\phpbb\config\config $config,
		\phpbb\db\driver\driver_interface $db,
		\phpbb\request\request $request,
		\phpbb\extension\manager $ext_manager,
		\phpbb\path_helper $path_helper,
		$php_ext, 
		$root_path,
		$portal_config_table,
		$auth,
		\phpbb\files\factory $files_factory = null)
	{
		$this->template 					= $template;
		$this->user 							= $user;
		$this->log 							= $log;
		$this->cache 						= $cache;
		$this->config						= $config;
		$this->db 							= $db;
		$this->request 					= $request;
		$this->ext_manager	 			= $ext_manager;
		$this->path_helper	 			= $path_helper;
		$this->php_ext 					= $php_ext;
		$this->root_path 					= $root_path;
		$this->portal_config_table	= $portal_config_table;
		$this->auth 							= $auth;
		$this->files_factory 				= $files_factory;
		
		$this->ext_name = $this->request->variable('ext_name', 'orynider/mxpadmin');
		$this->module_root_path	= $this->ext_path = $this->ext_manager->get_extension_path($this->ext_name, true);
		$this->ext_path_web = $this->path_helper->update_web_root_path($this->module_root_path);

		if (!class_exists('parse_message'))
		{
			include($this->root_path . 'includes/message_parser.' . $this->php_ext);
		}

		global $debug;
		
		define('INCLUDES', 'includes/'); //Main Includes folder
		
		//$this->user->_load_lang($this->module_root_path, 'info_acp_mxpadmin');
		$this->user->add_lang('acp/board');
		$this->user->add_lang_ext($this->ext_name, 'info_acp_mxpadmin');
		
		// Read out config values
		//$portal_config = $this->config_values();
		$this->backend = $this->confirm_backend();

		// get packs installed and init some variables
		$this->language_from = (isset($this->config['default_lang'])) ? $this->config['default_lang'] : 'en';
		$this->language_into	= (isset($user->lang['USER_LANG'])) ? $user->lang['USER_LANG'] : $this->language_from;

	}
	
	/**
	 * manage_portal_config() based on load_backend()
	 *
	 * Define Users/Group/Sessions backend, and validate
	 * Set $portal_config, $phpbb_root_path, $tplEx, $table_prefix & PORTAL_BACKEND/$mx_backend
	 *
	 */
	public function manage_portal_config($portal_id = 1)
	{
		$form_action = $this->u_action . '&amp;action=add';

		$this->tpl_name = 'admin_mx_portal';
		$this->page_title = $this->user->lang('PORTAL_TITLE');
		$mx_table_prefix = 'mx_';
		$form_key = 'acp_portal';
		add_form_key($form_key);
		
		$sql = 'SELECT *
	            FROM ' . $this->portal_config_table . '
				WHERE portal_id = ' . $portal_id . ' 
	            ORDER BY portal_id ASC';
		$result = $this->db->sql_query($sql);
		while( $row = $this->db->sql_fetchrow($result) )
		{
			//Populate info to display starts
			
			$portal_path = $row['portal_path'];
			$portal_url = $row['portal_url'];
			$user_id = $row['user_id'];
			
		}
		$this->db->sql_freeresult($result);
		
		
		// Get MX-Publisher temp config settings
		$portal_config = $this->get_portal_config($portal_id);
		$board_config = $this->config;
		
		$this->template->assign_vars(array(
			"PORTAL_ID"   					=> $portal_config['portal_id'], //
			"PORTAL_PATH" 				=> $portal_config['portal_path'],			
		));
		
		$this->mx_root_path = $mx_root_path = $this->root_path . (empty($portal_config['portal_path']) ? '../' : $portal_config['portal_path']);
	
		define('IN_PORTAL', 1); //Allows us to include mxp files
		
		$phpEx = $this->php_ext;
		
		/*
		* Read main config file	
		* using local_file_exists($file_path = $mx_root_path)
		*/
		if ($this->local_file_exists($mx_root_path . "config.$phpEx"))
		{
			$mx_info = $this->get_mxp_info($mx_root_path . "config.$phpEx", $this->backend, $this->config['version']);
			
			// MXP x.x auto-generated config file
			// Do not change anything in this file!
			$mx_dbms 			= $mx_info['dbms'];
			$mx_dbhost 			= $mx_info['dbhost'];
			$mx_dbname 		= $mx_info['dbname'];
			$mx_dbuser 			= $mx_info['dbuser'];
			$mx_dbpasswd 	= $mx_info['dbpasswd'];
			$this->mx_table_prefix = $mx_table_prefix 	= isset($mx_info['mx_table_prefix']) ? $mx_info['mx_table_prefix'] : 'mx_';
		
		}
		else if ((@include($mx_root_path . "/config.$phpEx")) === false)
		{
			$this->message_die(GENERAL_ERROR, 'Couldnt validate mxp configuration', '', __LINE__, __FILE__, $sql);
		}
		
		//$portal_config['portal_backend'] = 'olympus';
		//$portal_config['portal_backend_path'] = '../phpBBSeo/';
		
		$this->portal_table = $mx_table_prefix . 'portal';
		
		// Check some vars
		if (!$portal_config['portal_version'])
		{
			$portal_config = $this->obtain_mxbb_config(false, $portal_id, $mx_dbname);
		}
		
		if (!$portal_config['portal_backend_path'])
		{
			$portal_config = $this->obtain_mxbb_config(false, $portal_id, $mx_dbname);
		}

		// Overwrite Backend not to break ACP
		$this->portal_backend = $portal_config['portal_backend'];
		$portal_config['portal_backend'] = $this->backend;
			
		if (($portal_config['portal_backend']) && @file_exists($portal_config['portal_backend_path'] . "index.$phpEx"))
		{
			$this->backend = $portal_config['portal_backend'];
			$this->backend_path = (!$portal_config['portal_backend_path']) ? $this->backend_path : $portal_config['portal_backend_path'];
		}
		
		if ($this->backend)
		{
			$portal_config['portal_backend'] = $this->backend;
		}
		
		// No backend defined ? MXP-CMS not updated to v. 3 ?
		if ((!$portal_config['portal_backend']) && @file_exists($this->backend_path . "profile.$phpEx"))
		{
			$portal_config['portal_backend'] = 'phpbb2';
			$portal_config['portal_backend_path'] = $this->backend_path;
		}
		
		// No backend defined ? Try Internal.	
		if (!$portal_config['portal_backend'])
		{
			$portal_config['portal_backend'] = 'internal';
			$portal_config['portal_backend_path'] = $this->backend_path;
		}
		
		// Load backend
		$phpbb_root_path = $this->root_path; 
		//require($this->mx_root_path . 'includes/sessions/'.$portal_config['portal_backend'].'/core.'. $this->php_ext); 
		
		//Redirect to upgrade or redefine portal backend path
		if (!$portal_config['portal_backend_path'])
		{
			if(@file_exists($this->mx_root_path . "install"))
			{
				// Redirect via an HTML form for PITA webservers
				if (@preg_match('/Microsoft|WebSTAR|Xitami/', getenv('SERVER_SOFTWARE')))
				{
					header('Refresh: 0; URL=' . $this->mx_root_path . "install/mx_install.$phpEx");
					echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"><html><head><meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"><meta http-equiv="refresh" content="0; url=' . PORTAL_URL . $url . '"><title>Redirect</title></head><body><div align="center">If your browser does not support meta redirection please click <a href="' . PORTAL_URL . $url . '">HERE</a> to be redirected</div></body></html>';
					exit;
				}

				// Behave as per HTTP/1.1 spec for others
				header('Location: ' . $this->mx_root_path . "install/mx_install.$phpEx");
				exit;
			}
			else
			{
				$portal_config['portal_backend_path'] = $this->root_path;
			}
		}
		
		// Instantiate the mx_backend class $mx_backend = new mx_backend();
		
		// Validate backend
		define('PORTAL_BACKEND', $portal_config['portal_backend']);
		
		// Now, load backend specific constants	
		if ($this->config_path !== false)
		{	
			require($mx_root_path  . 'includes/sessions/'.PORTAL_BACKEND.'/constants.' . $this->php_ext);	
		}					
		
		$submit = ($this->request->is_set_post('submit')) ? true : false;

		if ($submit)
		{		
			if (!$this->request->is_set_post('portal_path'))
			{
				$this->message_die(GENERAL_ERROR, "Failed to update portal configuration, you didn't specified valid values or your admin templates are incompatible with this version of MXP.");
			}
			
			$new['portal_id'] = $this->request->variable('portal_id', $portal_id);
			$new['portal_path'] = $this->request->variable('portal_path', '', true);
			
			/*
			* This code is added here commented only for refrence
			*	
			$new['portal_name'] 			= utf8_normalize_nfc($this->request->variable('portal_name' . 'MX-Publisher', '', true, \phpbb\request\request_interface::POST));
			$new['portal_desc'] 				= utf8_normalize_nfc($this->request->variable('portal_desc', 'Modular system'));
			$new['portal_status'] 			= $this->request->variable('portal_status', '0');
			$new['disabled_message'] 	= $this->request->variable('disabled_message', 'Site disabled.');
			$new['server_name'] 			= $this->request->variable('server_name', '');
			$new['server_name'] 			= str_replace('http://', '', $new['server_name']);
			$new['script_path'] 				= $this->request->variable('script_path', '');
			$new['server_port'] 				= $this->request->variable('server_port', '');
			$new['default_dateformat'] 	= $this->request->variable('default_dateformat', '');
			$new['board_timezone'] 		= $this->request->variable('board_timezone', '1');
			$new['gzip_compress'] 			= $this->request->variable('gzip_compress', '');
			$new['mx_use_cache'] 			= $this->request->variable('mx_use_cache', '1');
			$new['mod_rewrite'] 			= $this->request->variable('mod_rewrite', '0');

			$new['portal_backend'] 	= $this->request->variable('portal_backend', 'internal');
			$new['portal_backend_path'] 	= $this->request->variable('portal_backend_path', '');

			$new['cookie_domain'] 		= $this->request->variable('cookie_domain', '');
			$new['cookie_name'] 			= $this->request->variable('cookie_name', '');
			$new['cookie_name'] 			= str_replace('.', '_', $new['cookie_name']);
			$new['cookie_path'] 				= $this->request->variable('cookie_path', '');
			$new['cookie_secure'] 			= $this->request->variable('cookie_secure', '');
			$new['session_length'] 			= $this->request->variable('session_length', '');
			$new['allow_autologin'] 		= $this->request->variable('allow_autologin', '');
			$new['max_autologin_time'] = $this->request->variable('max_autologin_time', '');
			$new['max_login_attempts'] 	= $this->request->variable('max_login_attempts', '');
			$new['login_reset_time'] 		= $this->request->variable('login_reset_time', '');

			//	$new['portal_url'] 					= $this->request->variable('portal_url', '');
			//	$new['portal_phpbb_url'] 		= $this->request->variable('portal_phpbb_url', '');

			$new['default_lang'] 			= $this->request->variable('default_lang', '-1');
			$new['default_style'] 			= $this->request->variable('mx_default_style', '-1');
			$new['override_user_style'] 	= $this->request->variable('mx_override_user_style', '1');
			$new['default_admin_style'] 	= $this->request->variable('mx_default_admin_style', '-1');
			$new['overall_header'] 			= $this->request->variable('overall_header', 'overall_header.tpl');
			$new['overall_footer'] 			= $this->request->variable('overall_footer', 'overall_footer.tpl');
			$new['main_layout'] 				= $this->request->variable('main_layout', 'mx_main_layout.tpl');
			$new['navigation_block'] 		= $this->request->variable('navigation_block', '0');
			$new['top_phpbb_links'] 		= $this->request->variable('top_phpbb_links', '0');
			$new['allow_html'] 				= $this->request->variable('allow_html', '1');
			$new['allow_html_tags'] 		= $this->request->variable('allow_html_tags', '1');
			$new['allow_bbcode'] 			= $this->request->variable('allow_bbcode', '1');
			$new['allow_smilies'] 			= $this->request->variable('allow_smilies', '1');
			$new['smilies_path'] 				= $this->request->variable('smilies_path', '');

			$new['board_email'] 			= $this->request->variable('board_email', '0');
			$new['board_email_sig'] 		= $this->request->variable('board_email_sig', '0');
			$new['smtp_delivery'] 			= $this->request->variable('smtp_delivery', '0');
			$new['smtp_host'] 				= $this->request->variable('smtp_host', '0');
			$new['smtp_username'] 		= $this->request->variable('smtp_username', '0');
			$new['smtp_password'] 		= $this->request->variable('smtp_password', '0');
			

			//Portal DB Upgrade
			$sql = "UPDATE  " . $this->portal_table . " SET " . $this->db->sql_build_array('UPDATE', utf8_normalize_nfc($new)) . "WHERE portal_id = " . $portal_id;
			
			if( !($this->db->sql_query($sql)) )
			{
				$this->message_die(GENERAL_ERROR, "Failed to update portal configuration ", "", __LINE__, __FILE__, $sql);
			}
			//Portal configuration file update
			$message = $this->update_portal_backend($new['portal_backend']) ? "The CMS configuration file was upgraded ...<br /><br />" : $this->update_portal_backend($new['portal_backend']);
			*/
			
			//self::set_config($board_config_name, $board_config_value, $portal_id)
			$this->set_config('portal_path', $new['portal_path'], $new['portal_id']);
			
			//
			// Update MX-Publisher page/block cache
			//
			$this->cache->trash(); // Empty cache folder
			$this->cache->update(); // Regenerate all page_ and block_ files

			//
			// Update config/custom cache
			//
			$this->cache->tidy(); // Not really needed
			$this->cache->destroy('phpbb_config'); // Not really needed
			$this->cache->destroy('mxbb_config'); // Not really needed
			$this->cache->unload(); // Regenerate data_global.php

			$message .= $this->user->lang('Cache_generate') . "<br /><br />";
			$this->cache->put('mxbb_config', $new);
			
			$message .= $this->user->lang('Portal_Config_updated') . "<br /><br />" . sprintf($this->user->lang('Click_return_portal_config'), "<a href=\"" . $this->u_action . "\">", "</a>") . "<br /><br />" . sprintf($this->user->lang['Click_return_admin_index'], "<a href=\"" . $this->u_action . "?i=1" . "\">", "</a>");

			//$this->message_die(GENERAL_MESSAGE, $message);
			//$this->db->sql_freeresult($result);
			
			$this->cache->put('portal_config', $new);
			
			// Log message
			$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_CONFIG_UPDATED');
			trigger_error($message. $this->user->lang['ACP_CONFIG_SUCCESS'] . adm_back_link($this->u_action));
		}
		
		$top_phpbb_links_yes = ( $portal_config['top_phpbb_links'] == 1 ) ? 'checked="checked"' : '';
		$top_phpbb_links_no = ( $portal_config['top_phpbb_links'] == 0 ) ? 'checked="checked"' : '';

		$mx_use_cache_yes = ( $portal_config['mx_use_cache'] == 1 ) ? 'checked="checked"' : '';
		$mx_use_cache_no = ( $portal_config['mx_use_cache'] == 0 ) ? 'checked="checked"' : '';

		$mx_mod_rewrite_yes = ( $portal_config['mod_rewrite'] == 1 ) ? 'checked="checked"' : '';
		$mx_mod_rewrite_no = ( $portal_config['mod_rewrite'] == 0 ) ? 'checked="checked"' : '';

		$mx_portal_status_yes = ( $portal_config['portal_status'] == 1 ) ? 'checked="checked"' : '';
		$mx_portal_status_no = ( $portal_config['portal_status'] == 0 ) ? 'checked="checked"' : '';

		$disabled_message = $portal_config['disabled_message'];

		$phpbb_rel_path = substr( "$phpbb_root_path", 3 );

		//$navigation_block_list = get_list_formatted('block_list', $portal_config['navigation_block'], 'navigation_block', 'mx_menu_nav.' . $phpEx, false, 'mx_site_nav.' . $phpEx);

		$portal_version = $portal_config['portal_version'];
		$phpbb_version = isset($this->config['version']) ? $this->config['version'] : '0.0.0';

		$script_path = isset($portal_config['script_path']) ? $portal_config['script_path'] : $board_config['script_path'];
		$server_name = isset($portal_config['server_name']) ? $portal_config['server_name'] : $board_config['server_name'];

		$phpbb_script_path = $this->config['script_path'];
		$phpbb_server_name = $this->config['server_name'];


		$portal_config['default_lang'] = ($portal_config['default_lang'] == -1) ? $board_config['default_lang'] : $portal_config['default_lang'];

		// Default to phpBB default
		$portal_config['default_admin_style'] = $portal_config['default_admin_style'] == -1 ? $board_config['default_style'] : $portal_config['default_admin_style'];
		$portal_config['default_style'] = $portal_config['default_style'] == -1 ? $board_config['default_style'] : $portal_config['default_style'];
		$portal_config['override_user_style'] = $portal_config['override_user_style'] == -1 ? $board_config['override_user_style'] : $portal_config['override_user_style'];

		$portal_backend_select = $this->get_list_static('portal_backend', 
															array('internal' => 'Internal', 
																		'smf2' => 'SMF2', 
																		'mybb' => 'myBB', 
																		'phpbb2' => 'phpBB2', 
																		'phpbb3' => 'phpBB3', 
																		'olympus' => 'Olympus', 
																		'ascraeus' => 'Ascraeus',
																		'rhea' => 'Rhea',
																		'proteus' => 'Proteus',
																		'phpbb4' => 'phpBB4'
																		), 
																		$this->portal_backend);

		$style_select = style_select($portal_config['default_style'], 'default_style');
		$style_admin_select = style_select($portal_config['default_admin_style'], 'default_admin_style');

		if (isset($this->user->data['user_timezone']))
		{
			$portal_config['board_timezone'] = $this->config['board_timezone'] = $this->user->data['user_timezone'];
		}
		else
		{
			$portal_config['board_timezone'] = $this->config['board_timezone'];
		}

		$lang_select =language_select($portal_config['default_lang'], 'default_lang', "language");
		
		//Pick a timezone phpbb_timezone_select() from tz_select($default = '', $truncate = false)
		$timezone_select = phpbb_timezone_select($this->template, $this->user, $portal_config['board_timezone'], false);

		$current_phpbb_version = $this->get_phpbb_version(); // Empty if mxp is used standalone

		//
		// Valid portal backend
		//
		$valid_backend_text = $this->confirm_backend() ? $lang['Portal_config_valid_true'] : $lang['Portal_config_valid_false'];

		$this->template->assign_vars(array(
			"U_ACTION" => $this->u_action,

			//"L_PORTAL_BACKEND_VALID" => $lang['Portal_config_valid'],
			"S_PORTAL_BACKEND_VALID_STATUS" => $this->confirm_backend(),

			//"L_YES" => $lang['Yes'],
			//"L_NO" => $lang['No'],

			//"L_ENABLED" => $lang['Enabled'],
			//"L_DISABLED" => $lang['Disabled'],

			//"L_SUBMIT" => $lang['Submit'],
			//"L_SUBMIT_BACKEND" => $lang['Portal_Backend_submit'],
			//"L_RESET" => $lang['Reset'],

			//"L_CONFIGURATION_TITLE" => $lang['Portal_General_Config'],
			//"L_CONFIGURATION_EXPLAIN" => $lang['Portal_General_Config_explain'],
			//"L_GENERAL_SETTINGS" => $lang['Portal_General_settings'],
			//"L_GENERAL_CONFIG_INFO" => $lang['Portal_General_config_info'] . "<br />" . $lang['Portal_General_config_info_explain'],
			//"L_STYLE_SETTINGS" => $lang['Portal_Style_settings'],

			//
			// General
			//
			//"L_PORTAL_NAME" => $lang['Portal_Name'], // Will override phpBB 'sitename'
			"PORTAL_NAME" => str_replace('"', '&quot;', strip_tags($portal_config['portal_name'])),

			//"L_PORTAL_DESC" => $lang['Portal_Desc'], // Will override phpBB 'site_desc'
			"PORTAL_DESC" => str_replace('"', '&quot;', $portal_config['portal_desc']),

			//"L_PORTAL_STATUS" => $lang['Portal_status'], // Will  override phpBB 'board_disable'
			//"L_PORTAL_STATUS_EXPLAIN" => $lang['Portal_status_explain'],
			//"S_PORTAL_STATUS_YES" => ( $portal_config['portal_status'] == 1 ) ? 'checked="checked"' : '',
			//"S_PORTAL_STATUS_NO" => ( $portal_config['portal_status'] == 0 ) ? 'checked="checked"' : '',

			//"L_DISABLED_MESSAGE" => $lang['Disabled_message'], // Will  override phpBB3 'board_disable_msg'
			//"DISABLED_MESSAGE" => $portal_config['disabled_message'],

			//"L_SERVER_NAME" => $lang['Server_name'],
			//"L_SERVER_NAME_EXPLAIN" => $lang['Server_name_explain'],
			"SERVER_NAME" => $server_name,

			//"L_SERVER_PORT" => $lang['Server_port'],
			//"L_SERVER_PORT_EXPLAIN" => $lang['Server_port_explain'],
			"SCRIPT_PATH" => $script_path,

			//"L_SCRIPT_PATH" => $lang['Script_path'],
			//"L_SCRIPT_PATH_EXPLAIN" => $lang['Script_path_explain'],
			"SERVER_PORT" => $portal_config['server_port'],

			//"L_DATE_FORMAT" => 'MXP' . $lang['Date_format'],
			//"L_DATE_FORMAT_EXPLAIN" => $lang['Date_format_explain'],
			"DEFAULT_DATEFORMAT" => $portal_config['default_dateformat'],

			//"L_SYSTEM_TIMEZONE" => 'MXP ' . $lang['System_timezone'],
			"TIMEZONE_SELECT" => $timezone_select, // board_timezone

			//"L_ENABLE_GZIP" => 'MXP ' . $lang['Enable_gzip'],
			"GZIP_YES" => ( $portal_config['gzip_compress'] ) ? "checked=\"checked\"" : "",
			"GZIP_NO" => ( !$portal_config['gzip_compress'] ) ? "checked=\"checked\"" : "",

			"PORTAL_PHPBB_URL" => ((null !== PHPBB_URL) ? PHPBB_URL : ''),
			"OVERALL_HEADER" => $portal_config['overall_header'],
			
			"OVERALL_FOOTER" => $portal_config['overall_footer'],
			"MAIN_LAYOUT" => $portal_config['main_layout'],
			"NAVIGATION_BLOCK" => $navigation_block_list,

			//"L_PHPBB_RELATIVE_PATH" => $lang['Phpbb_path'],
			//"L_PHPBB_RELATIVE_PATH_EXPLAIN" => $lang['Phpbb_path_explain'],
			//"PHPBB_RELATIVE_PATH" => $phpbb_rel_path,
			
			//"L_PORTAL_VERSION" => $lang['Portal_version'],
			//"PORTAL_VERSION" => $portal_version,

			//"L_PHPBB_INFO" => $lang['PHPBB_info'],

			//"L_PHPBB_SERVER_NAME" => $lang['PHPBB_server_name'],
			//"PHPBB_SERVER_NAME" => $phpbb_server_name,
			
			//"L_PHPBB_SCRIPT_PATH" => $lang['PHPBB_script_path'],
			//"PHPBB_SCRIPT_PATH" => $phpbb_script_path,
			
			//"L_PHPBB_VERSION" => $lang['PHPBB_version'],
			"PHPBB_VERSION" => $phpbb_version,

			//"L_TOP_PHPBB_LINKS" => $lang['Top_phpbb_links'] . "<br />" . $lang['Top_phpbb_links_explain'],
			"S_TOP_PHPBB_LINKS_YES" => $top_phpbb_links_yes,
			"S_TOP_PHPBB_LINKS_NO" => $top_phpbb_links_no,
			"TOP_PHPBB_LINKS" => $portal_config['top_phpbb_links'],

			//"L_MX_USE_CACHE" => $lang['Mx_use_cache'],
			//"L_MX_USE_CACHE_EXPLAIN" => $lang['Mx_use_cache_explain'],
			"S_MX_USE_CACHE_YES" => ( $portal_config['mx_use_cache'] == 1 ) ? 'checked="checked"' : '',
			"S_MX_USE_CACHE_NO" => ( $portal_config['mx_use_cache'] == 0 ) ? 'checked="checked"' : '',
			"MX_USE_CACHE" => $portal_config['mx_use_cache'],

			//"L_MX_MOD_REWRITE" => $lang['Mx_mod_rewrite'],
			//"L_MX_MOD_REWRITE_EXPLAIN" => $lang['Mx_mod_rewrite_explain'],
			"S_MX_MOD_REWRITE_YES" => ( $portal_config['mod_rewrite'] == 1 ) ? 'checked="checked"' : '',
			"S_MX_MOD_REWRITE_NO" => ( $portal_config['mod_rewrite'] == 0 ) ? 'checked="checked"' : '',
			"MX_MOD_REWRITE" => $portal_config['mod_rewrite'],


			//
			// Cookies and Sessions
			//
			//"L_PORTAL_BACKEND" => $lang['Portal_Backend'],
			//"L_PORTAL_BACKEND_EXPLAIN" => $lang['Portal_Backend_explain'],
			"PORTAL_BACKEND_SELECT" => $portal_backend_select,
			"PORTAL_DB_BACKEND" => $this->portal_backend,
			"PORTAL_CURRENT_BACKEND" => $portal_config['portal_backend'],
			//"L_PORTAL_BACKEND_PATH" => $lang['Portal_Backend_path'],
			//"L_PORTAL_BACKEND_PATH_EXPLAIN" => $lang['Portal_Backend_path_explain'],
			"PORTAL_BACKEND_PATH" => $portal_config['portal_backend_path'],
			
			//"L_COOKIE_SETTINGS" => $lang['Cookie_settings'],
			//"L_COOKIE_SETTINGS_EXPLAIN" => $lang['Cookie_settings_explain'],
			//"L_COOKIE_SETTINGS_EXPLAIN_MXP" => $lang['Cookie_settings_explain_mxp'],

			//"L_COOKIE_DOMAIN" => $lang['Cookie_domain'],
			"COOKIE_DOMAIN" => $portal_config['cookie_domain'],

			//"L_COOKIE_NAME" => $lang['Cookie_name'],
			"COOKIE_NAME" => $portal_config['cookie_name'],

			//"L_COOKIE_PATH" => $lang['Cookie_path'],
			"COOKIE_PATH" => $portal_config['cookie_path'],

			//"L_COOKIE_SECURE" => $lang['Cookie_secure'],
			//"L_COOKIE_SECURE_EXPLAIN" => $lang['Cookie_secure_explain'],
			"S_COOKIE_SECURE_ENABLED" => ( $portal_config['cookie_secure'] ) ? "checked=\"checked\"" : "",
			"S_COOKIE_SECURE_DISABLED" => ( !$portal_config['cookie_secure'] ) ? "checked=\"checked\"" : "",

			//"L_SESSION_LENGTH" => $lang['Session_length'],
			"SESSION_LENGTH" => $portal_config['session_length'],

			//"L_ALLOW_AUTOLOGIN" => $lang['Allow_autologin'],
			//"L_ALLOW_AUTOLOGIN_EXPLAIN" => $lang['Allow_autologin_explain'],
			'ALLOW_AUTOLOGIN_YES' => ($portal_config['allow_autologin']) ? 'checked="checked"' : '',
			'ALLOW_AUTOLOGIN_NO' => (!$portal_config['allow_autologin']) ? 'checked="checked"' : '',

			//"L_AUTOLOGIN_TIME" => $lang['Autologin_time'],
			//"L_AUTOLOGIN_TIME_EXPLAIN" => $lang['Autologin_time_explain'],
			'AUTOLOGIN_TIME' => (int) $portal_config['max_autologin_time'],

			//'L_MAX_LOGIN_ATTEMPTS' => $lang['Max_login_attempts'],
			//'L_MAX_LOGIN_ATTEMPTS_EXPLAIN' => $lang['Max_login_attempts_explain'],
			'MAX_LOGIN_ATTEMPTS' => $portal_config['max_login_attempts'],

			//'L_LOGIN_RESET_TIME' => $lang['Login_reset_time'],
			//'L_LOGIN_RESET_TIME_EXPLAIN' => $lang['Login_reset_time_explain'],
			'LOGIN_RESET_TIME'	=> $portal_config['login_reset_time'],

			//
			// User & Styling
			//
			//"L_DEFAULT_LANG" => $lang['Default_language'] . ' (' . $mx_user->lang_name . '/' . $portal_config['default_lang'] . ') ',
			//"L_DEFAULT_LANGUAGE" => $lang['Default_language'],
			"LANG_SELECT" => $lang_select,

			"L_DEFAULT_STYLE" => $lang['DEFAULT_STYLE'] . ' (' . $portal_config['default_style'] . ') ',

			//"L_DEFAULT_ADMIN_STYLE" => $lang['DEFAULT_ADMIN_STYLE'] . ' (' . $portal_config['default_admin_style'] . ') ',
			
			//"L_OVERRIDE_STYLE" => $lang['Override_style'],
			//"L_OVERRIDE_STYLE_EXPLAIN" => $lang['Override_style_explain'],
			
			"STYLE_SELECT" => $style_select,
			"ADMIN_STYLE_SELECT" => $style_admin_select,

			"OVERRIDE_STYLE_YES" => ( $portal_config['override_user_style'] ) ? "checked=\"checked\"" : "",
			"OVERRIDE_STYLE_NO" => ( !$portal_config['override_user_style'] ) ? "checked=\"checked\"" : "",

			//"L_MX_MOD_REWRITE" => $lang['Mx_mod_rewrite'],
			//"L_MX_MOD_REWRITE_EXPLAIN" => $lang['Mx_mod_rewrite_explain'],
			"S_MX_MOD_REWRITE_YES" => $mx_mod_rewrite_yes,
			"S_MX_MOD_REWRITE_NO" => $mx_mod_rewrite_no,
			"MX_MOD_REWRITE" => $portal_config['mod_rewrite'],

			//"L_OVERALL_HEADER" => $lang['Portal_Overall_header'] . "<br />" . $lang['Portal_Overall_header_explain'],
			"OVERALL_HEADER" => $portal_config['overall_header'],

			//"L_OVERALL_FOOTER" => $lang['Portal_Overall_footer'] . "<br />" . $lang['Portal_Overall_footer_explain'],
			"OVERALL_FOOTER" => $portal_config['overall_footer'],

			//"L_MAIN_LAYOUT" => $lang['Portal_Main_layout'] . "<br />" . $lang['Portal_Main_layout_explain'],
			"MAIN_LAYOUT" => $portal_config['main_layout'],

			//"L_NAVIGATION_BLOCK" => $lang['Portal_Navigation_block'] . "<br />" . $lang['Portal_Navigation_block_explain'],
			"NAVIGATION_BLOCK" => $navigation_block_list,

			//"L_TOP_PHPBB_LINKS" => $lang['Top_phpbb_links'] . "<br />" . $lang['Top_phpbb_links_explain'],
			"S_TOP_PHPBB_LINKS_YES" => ( $portal_config['top_phpbb_links'] ) ? 'checked="checked"' : '',
			"S_TOP_PHPBB_LINKS_NO" => ( !$portal_config['top_phpbb_links'] ) ? 'checked="checked"' : '',
			"TOP_PHPBB_LINKS" => $portal_config['top_phpbb_links'],

			//"L_ALLOW_HTML" => $lang['Allow_HTML'],
			"HTML_YES" => ( $portal_config['allow_html'] ) ? "checked=\"checked\"" : "",
			"HTML_NO" => ( !$portal_config['allow_html'] ) ? "checked=\"checked\"" : "",

			//"L_ALLOWED_TAGS" => $lang['Allowed_tags'],
			//"L_ALLOWED_TAGS_EXPLAIN" => $lang['Allowed_tags_explain'],
			"HTML_TAGS" => $portal_config['allow_html_tags'],

			//"L_ALLOW_BBCODE" => $lang['Allow_BBCode'],
			"BBCODE_YES" => ( $portal_config['allow_bbcode'] ) ? "checked=\"checked\"" : "",
			"BBCODE_NO" => ( !$portal_config['allow_bbcode'] ) ? "checked=\"checked\"" : "",

			//"L_ALLOW_SMILIES" => $lang['Allow_smilies'],
			"SMILE_YES" => ( $portal_config['allow_smilies'] ) ? "checked=\"checked\"" : "",
			"SMILE_NO" => ( !$portal_config['allow_smilies'] ) ? "checked=\"checked\"" : "",

			//"L_SMILIES_PATH" => $lang['Smilies_path'],
			//"L_SMILIES_PATH_EXPLAIN" => $lang['Smilies_path_explain'],
			"SMILIES_PATH" => $portal_config['smilies_path'],

			// Email
			//
			//"L_EMAIL_SETTINGS" => $lang['Email_settings'],

			//"L_ADMIN_EMAIL" => $lang['Admin_email'],
			"EMAIL_FROM" => $portal_config['board_email'],

			//"L_EMAIL_SIG" => $lang['Email_sig'],
			//"L_EMAIL_SIG_EXPLAIN" => $lang['Email_sig_explain'],
			"EMAIL_SIG" => $portal_config['board_email_sig'],

			//"L_USE_SMTP" => $lang['Use_SMTP'],
			//"L_USE_SMTP_EXPLAIN" => $lang['Use_SMTP_explain'],
			"SMTP_YES" => ( $portal_config['smtp_delivery'] ) ? "checked=\"checked\"" : "",
			"SMTP_NO" => ( !$portal_config['smtp_delivery'] ) ? "checked=\"checked\"" : "",

			//"L_SMTP_SERVER" => $lang['SMTP_server'],
			"SMTP_HOST" => $portal_config['smtp_host'],

			//"L_SMTP_USERNAME" => $lang['SMTP_username'],
			//"L_SMTP_USERNAME_EXPLAIN" => $lang['SMTP_username_explain'],
			"SMTP_USERNAME" => $portal_config['smtp_username'],

			//"L_SMTP_PASSWORD" => $lang['SMTP_password'],
			//"L_SMTP_PASSWORD_EXPLAIN" => $lang['SMTP_password_explain'],
			"SMTP_PASSWORD" => $portal_config['smtp_password'],

			//
			// Backend info
			//
			//"L_PHPBB_INFO" => $lang['PHPBB_info'],

			//"L_PHPBB_RELATIVE_PATH" => $lang['Phpbb_path'],
			//"L_PHPBB_RELATIVE_PATH_EXPLAIN" => $lang['Phpbb_path_explain'],
			"PHPBB_RELATIVE_PATH" => substr( "$phpbb_root_path", 3 ),

			//"L_PORTAL_STATUS" => $lang['Portal_status'],
			//"L_PORTAL_STATUS_EXPLAIN" => $lang['Portal_status_explain'],
			"S_PORTAL_STATUS_YES" => $mx_portal_status_yes,
			"S_PORTAL_STATUS_NO" => $mx_portal_status_no,

			//"L_DISABLED_MESSAGE" => $lang['Disabled_message'],
			"DISABLED_MESSAGE" => $disabled_message,

			//"L_PHPBB_SERVER_NAME" => $lang['PHPBB_server_name'],
			"PHPBB_SERVER_NAME" => $board_config['server_name'],

			//"L_PHPBB_SCRIPT_PATH" => $lang['PHPBB_script_path'],
			"PHPBB_SCRIPT_PATH" => $board_config['script_path'],

			//"L_PHPBB_VERSION" => $lang['PHPBB_version'],
			"PHPBB_VERSION" => $current_phpbb_version,

			//"L_PORTAL_VERSION" => $lang['Portal_version'],
			"PORTAL_VERSION" => $portal_config['portal_version'],

			'PHPBB_BACKEND'	=> !(PORTAL_BACKEND === 'internal'),
			
			'U_ACP' => ($this->auth->acl_get('a_') && !empty($this->user->data['is_registered'])) ? append_sid("{$this->mx_root_path}admin/index.$phpEx", false, true, $this->user->session_id) : '')
		);
	}

	 
	/**
	 * Validate backend
	 *
	 * Define Users/Group/Sessions backend, and validate
	 * Set $phpbb_root_path, $tplEx, $table_prefix
	 *
	 */
	function validate_backend($cache_config = array())
	{
		$phpEx = $this->php_ext;
		$mx_root_path = $this->mx_root_path;
		global $acm_type, $dbms, $dbhost, $dbname, $dbuser, $dbpasswd, $table_prefix;
		
		$backend_table_prefix = '';
		
		//
		// Define backend template extension
		//
		$tplEx = 'html';
		
		$portal_config = is_array($cache_config) ? $cache_config : $this->portal_config;
		
		//
		// Define relative path to phpBB, and validate
		//
		$phpbb_root_path = $this->root_path ? $this->root_path : $this->mx_root_path . $portal_config['portal_backend_path'];
		str_replace("//", "/", $phpbb_root_path);
		$portal_backend_valid_file = @file_exists($phpbb_root_path . "mcp.$phpEx");
		
		//
		// Load phpbb config.php (to get table prefix)
		// If this fails MXP2 will not work
		//
		if ((is_file($mx_root_path . "config.$phpEx") == true))
		{
			$backend_info = $this->get_mxp_info($mx_root_path . "config.$phpEx");
			
			// phpBB x.x auto-generated config file
			// Do not change anything in this file!
			$this->mx_dbms 			= $dbms;
			$this->mx_dbhost 		= $dbhost; 
			$this->mx_dbname 		= $dbname; 
			$this->mx_dbuser 		= $dbuser; 
			$this->mx_dbpasswd 	= $dbpasswd; 
			$this->table_prefix		= $table_prefix;
			
			$dbms 	= $backend_info['dbms'];
			$this->dbhost 	= $dbhost 	= $backend_info['dbhost'];
			
			$this->dbms = $dbms = $this->get_keys_sufix($dbms);
			$acm_type = $this->get_keys_sufix($acm_type);
			
			$this->dbname = $dbname 		= $backend_info['dbname'];
			$this->dbuser 	= $dbuser 			= $backend_info['dbuser'];
			$this->dbpasswd = $dbpasswd 		= $backend_info['dbpasswd'];
			$this->mx_table_prefix = $mx_table_prefix 	= $backend_info['mx_table_prefix'];
			
			if( !isset($backend_info['dbms']) || empty($backend_info['dbms']) || $backend_info['dbhost'] != $dbhost || $backend_info['dbname'] != $dbname || $backend_info['dbuser'] != $dbuser || $backend_info['dbpasswd'] != $dbpasswd || $backend_info['table_prefix'] != $table_prefix )
			{
				if ((include $mx_root_path . "config.$phpEx") === false)
				{
					print('mx_backend::validate_backend(); Configuration file (config) for  '. basename( __DIR__  ) . ' ' . $phpbb_root_path . "/config.$phpEx" . ' couldn\'t be opened.');
				}
			}
			
			//
			// Validate db connection for backend

			if ($mx_dbms !== $dbms)
			{
				// Load dbal and initiate class
				//require($this->mx_root_path . INCLUDES . 'db/' . $dbms . '.' . $phpEx); 
				//require($mx_root_path . 'includes/db/' . $dbms . '.'.$phpEx);
				//@var \phpbb\db\driver\driver_interface $db
				//$this->db = new $dbms();
				//$this->db	= new $sql_db;
				//$this->db->sql_connect($dbhost, $dbuser, $dbpasswd, $dbname, $dbport, false, true);
			}
			/*
			if (($mx_dbhost !== $dbhost) || ($mx_dbname !== $dbname))
			{
				if(!$this->db->sql_connect($dbhost, $dbuser, $dbpasswd, $dbname, false))
				{
					mx_message_die(CRITICAL_ERROR, "Could not connect to the backend database");
				}
			}
			*/			
			$sql = "SELECT config_value from " . $table_prefix . "config WHERE config_name = 'cookie_domain'";
			if(!$_result = $this->db->sql_query($sql))
			{
				//For php 5.3.0 or less
				$db_sql_error = $this->db->sql_error('');
				print('Configuration file opened but backend check query failed for backend: '. basename( __DIR__  ) .  ', line: ' . __LINE__ . ', file: ' . __FILE__ . '<br /><br />SQL Error : ' . $db_sql_error['code'] . ' ' . $db_sql_error['message']);
			}
			$portal_backend_valid_db = $this->db->sql_numrows($_result) != 0;
		}
		else
		{
			print('Configuration file for this backend (config) ' . $phpbb_root_path . "config.$phpEx" . ' couldn\'t be opened.');

			if ((include $phpbb_root_path . "config.$phpEx") === false)
			{
				print('Configuration file (config) ' . $phpbb_root_path . "/config.$phpEx" . ' couldn\'t be opened.');
			}
			//
			// Validate db connection for backend
			//
			$_result = $this->db->sql_query( "SELECT config_value from " . $table_prefix . "config WHERE config_name = 'cookie_domain'" );
			$portal_backend_valid_db = $this->db->sql_numrows($_result) != 0;
		}
		
		return $portal_backend_valid_file && !empty($table_prefix) && $portal_backend_valid_db;
	}	

	/**
	 * Log Message
	 *
	 * @return message
	 * @access private
	*/
	private function log_message($log_message, $title, $user_message)
	{
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, $log_message, time(), array($title));

		trigger_error($this->user->lang[$user_message] . adm_back_link($this->u_action));
	}
	
	/**
	 * update config.php values.
	 *
	 */
	function update_portal_backend($new_backend = PORTAL_BACKEND)
	{
		if( @file_exists($this->mx_root_path . "config.".$this->php_ext) )
		{
			@require($this->mx_root_path . "config." . $this->php_ext);
		}

		$mx_portal_name = 'MX-Publisher Modular System';
		$dbcharacter_set = "uft8";

		/*
		$board_config = array(
			'dbms'		=> $dbms,
			'dbhost'		=> $dbhost,
			'dbname'		=> $dbname,
			'dbuser'		=> $dbuser,
			'dbpasswd'		=> $dbpasswd,
			'mx_table_prefix'		=> $mx_table_prefix,
			'portal_backend'		=> (!empty($portal_backend) ? $portal_backend : 'internal'),
		);
		*/

		$new_backend = ($new_backend) ? $new_backend  : 'internal';

		switch ($new_backend)
		{
			case 'internal':
			case 'phpbb3':
			case 'olympus':	
			case 'ascraeus':
			case 'mybb':		
				$dbcharacter_set = defined('DBCHARACTER_SET') ? DBCHARACTER_SET : 'utf8';
			break;
			
			case 'phpbb2':
				$dbcharacter_set = defined('DBCHARACTER_SET') ? DBCHARACTER_SET : 'latin1';
			break;
				
		}

		$process_msgs[] = 'Writing config ...<br />';

		$board_config_data = "<"."?php\n\n";
		$board_config_data .= "// $mx_portal_name auto-generated config file\n// Do not change anything in this file!\n\n";
		$board_config_data .= "// This file must be put into the $mx_portal_name directory, not into the phpBB directory.\n\n";
		$board_config_data .= '$'."dbms = '$dbms';\n\n";
		$board_config_data .= '$'."dbhost = '$dbhost';\n";
		$board_config_data .= '$'."dbname = '$dbname';\n";
		$board_config_data .= '$'."dbuser = '$dbuser';\n";
		$board_config_data .= '$'."dbpasswd = '$dbpasswd';\n\n";
		$board_config_data .= '$'."mx_table_prefix = '$mx_table_prefix';\n\n";
		$board_config_data .= "define('DBCHARACTER_SET', '$dbcharacter_set');\n\n";
		$board_config_data .= "define('MX_INSTALLED', true);\n\n";
		$board_config_data .= '?' . '>';	// Done this to prevent highlighting editors getting confused!

		@umask(0111);
		@chmod($this->mx_root_path . "config.$phpEx", 0644);

		if ( !($fp = @fopen($this->mx_root_path . 'config.' . $this->php_ext, 'w')) )
		{
			$process_msgs[] = "Unable to write config file " . $this->mx_root_path . "config." . $this->php_ext . "<br />\n";
		}
		$result = @fputs($fp, $board_config_data, strlen($board_config_data));
		@fclose($fp);

		$process_msgs[] = '<span style="color:pink;">'.str_replace("\n", "<br />\n", htmlspecialchars($board_config_data)).'</span>';

		$message = '<hr />';
		for ($i = 0; $i < count($process_msgs); $i++)
		{
			$message .= $process_msgs[$i] . ( $process_msgs[$i] == '<hr />' ? '' : '<br />' ) . "\n";
		}
		$message .= '<hr />';

		return $message;
	}

	/**
	 * $mx_backend->setup_backend()
	 *
	 * Define some general backend definitions
	 * PORTAL_URL, PHPBB_URL, PORTAL_VERSION & $board_config
	 *
	 */
	function setup_backend()
	{
		$portal_config = $this->portal_config;
		$board_config = $this->config;
		$phpbb_root_path = $this->root_path;
		$phpEx = $this->php_ext;
		
		$script_name = preg_replace('/^\/?(.*?)\/?$/', "\\1", trim($portal_config['script_path']));
		$server_name = trim($portal_config['server_name']);
		$server_protocol = ( $portal_config['cookie_secure'] ) ? 'https://' : 'http://';
		$server_port = (($portal_config['server_port']) && ($portal_config['server_port'] <> 80)) ? ':' . trim($portal_config['server_port']) . '/' : '/';

		$server_url = $server_protocol . str_replace("//", "/", $server_name . $server_port . $script_name . '/'); //On some server the slash is not added and this trick will fix it

		define('PORTAL_URL', $server_url);
		define('PORTAL_VERSION', $portal_config['portal_version']);

		//
		// Grab phpBB global variables, re-cache if necessary
		// - optional parameter to enable/disable cache for config data. If enabled, remember to refresh the MX-Publisher cache whenever updating phpBB config settings
		// - true: enable cache, false: disable cache
		$board_config = $this->obtain_phpbb_config(false);
		$script_name_phpbb = preg_replace('/^\/?(.*?)\/?$/', "\\1", trim($board_config['script_path'])) . '/';

		$server_url_phpbb = $server_protocol . $server_name . $server_port . $script_name_phpbb;
		define('PHPBB_URL', $server_url_phpbb);
		
		// Check whether the session is still valid if we have one
		$method = basename(trim($board_config['auth_method']));
		
		//
		// Instantiate the mx_auth class
		//$mx_auth = $phpbb_auth = new phpbb_auth();
		
		// Define backend template extension
		$tplEx = 'html';
		if (!defined('TPL_EXT')) define('TPL_EXT', $tplEx);
		//
		// Now sync Configs
		// In phpBB mode, we rely on native phpBB configs, thus we need to sync mxp and phpbb settings
		//
		$this->sync_configs();
	}
	
	/**
	* Set page url
	*
	* @param string $u_action Custom form action
	* @return null
	* @access public
	*/
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}

	function manage_pages_header( $page = 1, $depth = 0 )
	{
		// Read out config values
		$pafiledb_config = $this->config_values();
		$this->tpl_name = 'acp_custom_header_pages';
		$action = $this->request->variable('action', '');
		$form_action = $this->u_action. '&amp;action='.$action;
		
		$this->user->lang_mode = $this->user->lang['ACP_MANAGE_PAGES'];

		$page_id = $this->request->is_set('page') ? $this->request->variable('page') : $page;

		//$this->user->add_lang('common');
		
		$this->template->assign_vars(array(
			'BASE'	=> $this->u_action,
		));	

		return;
	}

	/**
	 * This class is used for general pafiledb handling
	 *
	 * @param unknown_type $config_name
	 * @param unknown_type $config_value
	 */
	function set_config($board_config_name, $board_config_value, $portal_id = 1)
	{
		$portal_config = array();
		// Read out config values
		$sql = 'SELECT *
	            FROM ' . $this->portal_config_table . '
				WHERE portal_id = ' . $portal_id . ' 
	            ORDER BY portal_id ASC';
		$result = $this->db->sql_query($sql);
		while( $portal_config = $this->db->sql_fetchrow($result) )
		{
			//Populate info to display starts
			
			$portal_path = $portal_config['portal_path'];
			$portal_url = $portal_config['portal_url'];
			$user_id = $portal_config['user_id'];
			
		}
		$this->db->sql_freeresult($result);
		//$portal_config = $this->get_config();
		//$this->portal_config_table = $this->table_prefix . 'portal_config';
		// Read out config values
		if (isset($portal_config[$board_config_name]))
		{
			$sql = 'UPDATE ' . $this->portal_config_table . "
				SET " . $this->db->sql_escape($board_config_name) . " = " . $this->db->sql_escape($board_config_value) . "
				WHERE portal_id = " . $portal_id;
			$this->db->sql_query($sql);
		}
		else
		{
			$this->mxpadmin_config[$board_config_name] = $portal_config[$board_config_name] = $board_config_value;
			$sql = "UPDATE  " . $this->portal_config_table . " SET " . $this->db->sql_build_array('UPDATE', utf8_normalize_nfc($portal_config)) . "
				WHERE portal_id = " . $portal_id;			$this->db->sql_query($sql);
		}
		return true;
	}

	/**
	 * get_config()
	 *
	 * @return unknown
	 */
	function get_config($use_cache = false, $portal_id = 1, $dbname = '')
	{
		$db_name = empty($dbname) ? $this->dbname : $dbname;
		
		if (($portal_config = $this->cache->get('portal_config')) && ($use_cache) && ($portal_id == 1))
		{
			return $portal_config;
		}
		else
		{
			//Wile we install only have demo portal_id = 1 
			if (!empty($dbname))
			{
				$sql = "SELECT *
					FROM `" . $dbname . "`.`" . $this->portal_table . "`
					WHERE portal_id = ". $portal_id;
			}
			else
			{		
				$sql = "SELECT *
					FROM " . $this->portal_table . "
					WHERE portal_id = ". $portal_id;
			}					
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			
			if (empty($row))
			{
				$this->message_die(GENERAL_ERROR, 'Couldnt query portal configuration', '', __LINE__, __FILE__, $sql);
					
				return array(		
					'portal_id' => 1,
					'portal_path' => '../',
				);		
			}
				
			foreach ($row as $config_name => $config_value)
			{
				$portal_config[$config_name] = trim($config_value);
			}
		}	
		$this->cache->put('portal_config', $portal_config);		
		return($portal_config);
	}

	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	function get_portal_config($use_cache = true, $portal_id = 1, $db_name = '')
	{
		$db_name = empty($db_name) ? $this->mx_dbname : $db_name;
		
		if (($portal_config = $this->cache->get('portal_config')) && ($use_cache) && ($portal_id == 1))
		{
			return $portal_config;
		}
		else
		{
			//Wile we install only have demo portal_id = 1 
			if (!empty($db_name))
			{
				$sql = "SELECT *
					FROM `" . $db_name . "`.`" . $this->portal_config_table . "`
					WHERE portal_id = ". $portal_id;
			}
			else
			{		
				$sql = "SELECT *
					FROM " . $this->portal_config_table . "
					WHERE portal_id = ". $portal_id;
			}					
			$result = $this->db->sql_query($sql);
			$row = $this->db->sql_fetchrow($result);
			$this->db->sql_freeresult($result);
			
			if (empty($row))
			{
				$this->message_die(GENERAL_ERROR, 'Couldnt query portal configuration', '', __LINE__, __FILE__, $sql);
					
				return array(		
					'portal_id' => 1,
					'portal_path' => '../',
				);		
			}
				
			foreach ($row as $config_name => $config_value)
			{
				$portal_config[$config_name] = trim($config_value);
			}
		}
		
		$this->cache->put('portal_config', $portal_config);		
		return($portal_config);
	}
	
	/**
	 * Dummy function
	 */
	function message_die($msg_code, $msg_text = '', $msg_title = '', $err_line = '', $err_file = '', $sql = '')
	{		
		//
		// Get SQL error if we are debugging. Do this as soon as possible to prevent
		// subsequent queries from overwriting the status of sql_error()
		//
		if (DEBUG && ($msg_code == GENERAL_ERROR || $msg_code == CRITICAL_ERROR))
		{
				
			if ( isset($sql) )
			{
				$sql_error = $this->db->sql_error($sql);
				$sql_error['message'] = $sql_error['message'] ? $sql_error['message'] : '<br /><br />SQL : ' . $sql; 
				$sql_error['code'] = $sql_error['code'] ? $sql_error['code'] : 0;
			}
			else
			{
				$sql_error = $this->db->sql_error_returned;
				$sql_error['message'] = $sql_error['message'] ? $sql_error['message'] : '<br /><br />SQL : ' . $sql; 
				$sql_error['code'] = $sql_error['code'] ? $sql_error['code'] : 0;
			}
			
			$debug_text = '';

			if ( isset($sql_error['message']) )
			{
				$debug_text .= '<br /><br />SQL Error : ' . $sql_error['code'] . ' ' . $sql_error['message'];
			}

			if ( isset($sql_store) )
			{
				$debug_text .= "<br /><br />$sql_store";
			}

			if ( isset($err_line) && isset($err_file) )
			{
				$debug_text .= '</br /><br />Line : ' . $err_line . '<br />File : ' . $err_file;
			}
		}
		
		switch($msg_code)
		{
			case GENERAL_MESSAGE:
				if ( $msg_title == '' )
				{
					$msg_title = $this->user->lang('Information');
				}
			break;

			case CRITICAL_MESSAGE:
				if ( $msg_title == '' )
				{
					$msg_title = $this->user->lang('Critical_Information');
				}
			break;

			case GENERAL_ERROR:
				if ( $msg_text == '' )
				{
					$msg_text = $this->user->lang('An_error_occured');
				}

				if ( $msg_title == '' )
				{
					$msg_title = $this->user->lang('General_Error');
				}
			break;

			case CRITICAL_ERROR:

				if ($msg_text == '')
				{
					$msg_text = $this->user->lang('A_critical_error');
				}

				if ($msg_title == '')
				{
					$msg_title = 'phpBB : <b>' . $this->user->lang('Critical_Error') . '</b>';
				}
			break;
		}
		
		//
		// Add on DEBUG info if we've enabled debug mode and this is an error. This
		// prevents debug info being output for general messages should DEBUG be
		// set TRUE by accident (preventing confusion for the end user!)
		//
		if ( DEBUG && ( $msg_code == GENERAL_ERROR || $msg_code == CRITICAL_ERROR ) )
		{
			if ( $debug_text != '' )
			{
				$msg_text = $msg_text . '<br /><br /><b><u>DEBUG MODE</u></b> ' . $debug_text;
			}
		}
		
		trigger_error($msg_title . ': ' . $msg_text);
	}
	
	/**
	 * Confirm Forum Backend Name
	 *
	* @return $backend
	 */
	function confirm_backend($backend_name = true)
	{
		if (isset($this->config['version'])) 
		{
			if ($this->config['version']  >= '4.0.0')
			{
				$this->backend = 'phpbb4';
			}
			if (($this->config['version']  >= '3.3.0') && ($this->config['version'] < '4.0.0'))
			{
				$this->backend = 'proteus';
			}
			if (($this->config['version']  >= '3.2.0') && ($this->config['version'] < '3.3.0'))
			{
				$this->backend = 'rhea';
			}
			if (($this->config['version']  >= '3.1.0') && ($this->config['version'] < '3.2.0'))
			{
				$this->backend = 'ascraeus';
			}
			if (($this->config['version']  >= '3.0.0') && ($this->config['version'] < '3.1.0'))
			{
				$this->backend = 'olympus';
			}
			if (($this->config['version']  >= '2.0.0') && ($this->config['version'] < '3.0.0'))
			{
				$this->this->backend = 'phpbb2';
			}
			if (($this->config['version']  >= '1.0.0') && ($this->config['version'] < '2.0.0'))
			{
				$this->backend = 'phpbb';
			}
		}
		else if (isset($this->config['portal_backend']))
		{
			$this->backend = $this->config['portal_backend'];
		}
		else
		{
			$this->backend = 'internal';
		}
		
		$this->is_phpbb20	= phpbb_version_compare($this->config['version'], '2.0.0@dev', '>=') && phpbb_version_compare($this->config['version'], '3.0.0@dev', '<');		
		$this->is_phpbb30	= phpbb_version_compare($this->config['version'], '3.0.0@dev', '>=') && phpbb_version_compare($this->config['version'], '3.1.0@dev', '<');		
		$this->is_phpbb31	= phpbb_version_compare($this->config['version'], '3.1.0@dev', '>=') && phpbb_version_compare($this->config['version'], '3.2.0@dev', '<');
		$this->is_phpbb32	= phpbb_version_compare($this->config['version'], '3.2.0@dev', '>=') && phpbb_version_compare($this->config['version'], '3.3.0@dev', '<');		
		$this->is_phpbb33	= phpbb_version_compare($this->config['version'], '3.3.0@dev', '>=') && phpbb_version_compare($this->config['version'], '3.4.0@dev', '<');		
		
		$this->is_block = isset($this->config['portal_backend']) ? true : false;
		
		if ($this->config['version'] < '3.1.0')
		{
			define('EXT_TABLE',	$table_prefix . 'ext');
		}
		
		if ($backend_name == true)
		{
			return $this->backend;
		}
	}
	
	/**
	 * Enter description here...
	 *
	 * @return unknown
	 */
	function get_phpbb_version()
	{
		return $this->config['version'];
	}
	
	/**
	 * Get MX-Publisher config data
	 *
	 * @access public
	 * @return unknown
	 */
	public function obtain_mxbb_config($use_cache = false, $portal_id = 1, $db_name = '')
	{

		if ( ($portal_config = $this->cache->get('mx_config')) && ($use_cache) )
		{
			return $portal_config;
		}
		else
		{
			if (!empty($db_name))
			{
				$sql = "SELECT *
					FROM `" . $db_name . "`.`" . $this->portal_table . "`
					WHERE portal_id = ". $portal_id;
			}
			else
			{		
				$sql = "SELECT *
					FROM " . $this->portal_table . "
					WHERE portal_id = ". $portal_id;
			}	
			if ( !($result = $this->db->sql_query($sql)) )
			{
				$this->message_die(GENERAL_ERROR, 'Couldnt query portal configuration', '', __LINE__, __FILE__, $sql );
			}
			$row = $this->db->sql_fetchrow($result);
			
			foreach ($row as $config_name => $config_value)
			{
				$portal_config[$config_name] = trim($config_value);
			}
			$this->db->sql_freeresult($result);
			$this->cache->put('mx_config', $portal_config);

			return ($portal_config);
		}
	}
	
	/**
	 * get_mxp_info
	 *
	 * @param unknown_type $root_path
	 * @access private
	 */
	function get_mxp_info($root_path, $backend = 'rhea', $phpbbversion = '3.2.9')
	{
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		
		if (strpos($root_path, '.') !== false)
		{
			// Nested file
			$filename_ext = substr(strrchr($root_path, '.'), 1);
			$filename = basename($root_path, '.' . $filename_ext);
			$current_dir = dirname(realpath($root_path));
			$root_path = dirname($root_path);			
		}
		else
		{
			$filename_ext = substr(strrchr(__FILE__, '.'), 1);
			$filename = "config";
			$current_dir = $root_path;
			$root_path = dirname($root_path);			
		}		
		
		$config = $root_path . "/config.$phpEx";
		$this->config_path = true; 
		
		//
		if ((@include $config) === false)
		{
			$this->config_path = false; 
			
			print('Configuration file ' . $config . ' couldn\'t be opened.');
		}
		if ((@include $this->root_path . "language/en/install.$phpEx") !== false)
		{
			$left_piece1 = explode('. You', $lang['CONVERT_COMPLETE_EXPLAIN']);	
			$left_piece2 = explode('phpBB', $left_piece1[0]);
			$phpbbversion = strrchr($left_piece2[1], ' ');
			
			switch (true)
			{
				case (preg_match('/3.0/i', $phpbbversion)):
					$backend = 'olympus';
				break;
				case (preg_match('/3.1/i', $phpbbversion)):
					$backend = 'ascraeus';
				break;
				case (preg_match('/3.2/i', $phpbbversion)):
					$backend = 'rhea';
				break;
				case (preg_match('/3.3/i', $phpbbversion)):
					$backend = 'proteus';
				break;
				case (preg_match('/4.0/i', $phpbbversion)):
					$backend = 'phpbb4';
				break;
			}
		}		
		
		//
		// Check the prefix length to ensure that index names are not too long and does not contain invalid characters
		switch ($backend)
		{
			case 'internal':
			// no break;
			case 'phpbb2':
				$phpbb_adm_relative_path = 'admin';
			break;
			
			case 'phpbb3':
				$phpbb_adm_relative_path = 'adm';
			break;
			
			case 'ascraeus':
			case 'rhea':
			case 'proteus':
			default:
				$phpbb_adm_relative_path = (isset($phpbb_adm_relative_path)) ? $phpbb_adm_relative_path : 'adm/';
				$dbms = $this->get_keys_sufix($dbms);
				$acm_type = $this->get_keys_sufix($acm_type);
			break;
		}
		
		// If we are on PHP < 5.0.0 we need to force include or we get a blank page
		if (version_compare(PHP_VERSION, '5.0.0', '<')) 
		{
			$dbms = str_replace('mysqli', 'mysql4', $dbms); //this version of php does not have mysqli extension and my crash the installer if finds a forum using this		
		}
		
		return array(
			'dbms'						=> $dbms,
			'dbhost'					=> $dbhost,
			'dbname'					=> $dbname,
			'dbuser'					=> $dbuser,
			'dbpasswd'				=> $dbpasswd,
			'mx_table_prefix'		=> $mx_table_prefix,	
			//'table_prefix'	=> $table_prefix,
			'backend'					=> $backend,
			'version'					=> $phpbbversion,
			'acm_type'				=> isset($acm_type) ? $acm_type : 'file',
			//'phpbb_root_path'		=> $phpbb_root_path,
			'status'						=> defined('MX_INSTALLED') ? true : false,
		);
	}	
	
	/**
	 * get_phpbb_info
	 *
	 * @param unknown_type $root_path
	 * @access private
	 */
	function get_phpbb_info($root_path, $backend = 'phpbb2', $phpbbversion = '2.0.24')
	{
		$phpEx = substr(strrchr(__FILE__, '.'), 1);
		
		if (strpos($root_path, '.') !== false)
		{
			// Nested file
			$filename_ext = substr(strrchr($root_path, '.'), 1);
			$filename = basename($root_path, '.' . $filename_ext);
			$current_dir = dirname(realpath($root_path));
			$root_path = dirname($root_path);			
		}
		else		
		{
			$filename_ext = substr(strrchr(__FILE__, '.'), 1);
			$filename = "config";
			$current_dir = $root_path;
			$root_path = dirname($root_path);			
		}		
		
		$config = $root_path . "/config.$phpEx";
		
		//
		if ((@include $config) === false)
		{
			die('Configuration file ' . $config . ' couldn\'t be opened.');
		}
		//
		
		if ((@include $root_path . "language/en/install.$phpEx") !== false)
		{
			$left_piece1 = explode('. You', $lang['CONVERT_COMPLETE_EXPLAIN']);	
			$left_piece2 = explode('phpBB', $left_piece1[0]);
			$phpbbversion = strrchr($left_piece2[1], ' ');
			
			switch (true)
			{
				case (preg_match('/3.0/i', $phpbbversion)):
					$backend = 'olympus';
				break;
				case (preg_match('/3.1/i', $phpbbversion)):
					$backend = 'ascraeus';
				break;
				case (preg_match('/3.2/i', $phpbbversion)):
					$backend = 'rhea';
				break;
				case (preg_match('/3.3/i', $phpbbversion)):
					$backend = 'proteus';
				break;
				case (preg_match('/4.0/i', $phpbbversion)):
					$backend = 'phpbb4';
				break;
			}
		}	
		
		// Check the prefix length to ensure that index names are not too long and does not contain invalid characters
		switch ($backend)
		{
			case 'internal':
			// no break;
			case 'phpbb2':
				$phpbb_adm_relative_path = 'admin';
			break;
			
			case 'olympus':
				$phpbb_adm_relative_path = 'adm';
			break;
			
			case 'phpbb3':
			case 'ascraeus':
			case 'rhea':
			case 'proteus':	
				$phpbb_adm_relative_path = (isset($phpbb_adm_relative_path)) ? $phpbb_adm_relative_path : 'adm/';
				$dbms = get_keys_sufix($dbms);
				$acm_type = get_keys_sufix($acm_type);
			break;
		}
		
		// If we are on PHP < 5.0.0 we need to force include or we get a blank page
		if (version_compare(PHP_VERSION, '5.0.0', '<')) 
		{
			$dbms = str_replace('mysqli', 'mysql4', $dbms); //this version of php does not have mysqli extension and my crash the installer if finds a forum using this		
		}
		
		return array(
			'dbms'			=> $dbms,
			'dbhost'		=> $dbhost,
			'dbname'		=> $dbname,
			'dbuser'		=> $dbuser,
			'dbpasswd'	=> $dbpasswd,
			'table_prefix'	=> $table_prefix,
			'backend'		=> $backend,
			'version'		=> $phpbbversion,
			'acm_type'	=> isset($acm_type) ? $acm_type : '',
			'status'			=> defined('PHPBB_INSTALLED') ? true : false,
		);
	}
	
	/**
	 * Set and get value from posted or cookie
	 * @return mixed value generated from posted, geted or cookie
	 * @param $name string cookie name of the value
	 * @param $value mixed value which should be setted for cookie
	 */
	function phpbb_cookie($name, $value = '')
	{
		$board_config = $this->config; /* cookie_name', 'phpbb3_li1e6', 0 */
		$cookie_board_name = $name;
		$return = '';
		if ($value != '')
		{
			$return = $value;
			// Currently not working under linux machines [Ubuntu GG]
			//setcookie( $cookie_board_name, $value, (time()+21600), $board_config['cookie_path'], $board_config['cookie_domain'], $board_config['cookie_secure']);
			setcookie( $cookie_board_name, $value, (time() + 21600), $board_config['cookie_path']);
			
			$this->cookie[$cookie_board_name] = $value;
			
		}
		else if(isset($_COOKIE[$cookie_board_name]))
		{
			$value = $this->cookie[$cookie_board_name] = $this->request->variable($cookie_board_name, 0, false, \phpbb\request\request_interface::COOKIE);
			// Currently not working under linux machines [Ubuntu GG]
			//setcookie( $cookie_board_name, $_COOKIE[ $cookie_board_name], (time()+21600), $board_config['cookie_path'], $board_config['cookie_domain'], $board_config['cookie_secure']);
			setcookie($cookie_board_name, $value, (time() + 21600), $board_config['cookie_path']);
			
		}
		$this->cookie['test' . $name] = $value;		
		return $value;
	}
	
	/** /
	*
	* Credit: https://stackoverflow.com/users/2456038/rafasashi
	/**/
	function local_file_exists($file_path = '')
	{
		// Assume failure.
		 $file_exists = true;
		 $status = "unknown";
		
		//$file_path = 'http://php.net/images/logos/php-logo.svg';
		//clear cached results
		//clearstatcache();
		
		//trim path
		$file_dir = trim(dirname($file_path));
		
		//trim file name
		$file_name = trim(basename($file_path));
		
		//rebuild path
		$file_path = $file_dir . "/{$file_name}";
		
		global $mx_root_path, $phpbb_root_path;
		
		//If you simply want to check that some file (not directory) exists, 
		//and concerned about performance, try is_file() instead.
		//It seems like is_file() is almost 2x faster when a file exists 
		//and about the same when it doesn't.
			
		$file = $file_dir . '/' . $file_name;
		
		if (function_exists('is_file') && @is_file($file)) 
		{
			$status = "is_file";
			$file_exists = true;
		}
		
		if (function_exists('file_exists') && @file_exists(str_replace(array(PORTAL_URL, PHPBB_URL), array($this->mx_root_path, $this->root_path), $file_path))) 
		{
			$status = "file_exists";
			$file_exists = true;
		}
			
		if (function_exists('filesize') && @filesize(str_replace(array(PORTAL_URL, PHPBB_URL), array($this->mx_root_path, $this->root_path), $file_path))) 
		{
			$status = "filesize";
			$file_exists = true;
		}
		
		return $file_exists;
	}	
	
	/**
	 * Get html select list - from array().
	 * ported from mxp-cms by orynider
	 * This function generates and returns a html select list (name = $nameselect).
	 *
	 * @access public
	 * @param string $name_select select name
	 * @param array $row source data
	 * @param string $id needle
	 * @param boolean $full_list expanded or dropdown list
	 * @return unknown
	 */
	function get_list_static($name_select, $row, $id, $full_list = true)
	{
		$rows_count = ( count($row) < '25' ) ? count($row) : '25';
		$full_list_true = $full_list ? ' size="' . $rows_count . '"' : '';

		$column_list = '<select name="' . $name_select .'" ' . $full_list_true . '>';
		foreach( $row as $idfield => $namefield )
		{
			$selected = ( $idfield == $id ) ? ' selected="selected"' : '';
			$column_list .= '<option value="' . $idfield . '"' . $selected . '>' . $namefield . "</option>\n";
		}
		$column_list .= '</select>';

		unset($row);
		return $column_list;
	}
		
	/* replacement for eregi($pattern, $string); outputs 0 or 1*/
	function trisstr($pattern = '%{$regex}%i', $string, $matches = '') 
	{      
		return preg_match('/' . $pattern . '/i', $string, $matches);
	}
		
	/* replacement for stripslashes(); */
	function removeslashes($string, $all = true) 
	{  	
		//remove also slashes inside the string
		//or remove only leading and trailing slashes		
		return ($all !== false)  ? str_replace('/', '', $string)  : trim($string, '/');
	}
	
	/* replacement for print_r(array(), true); */
	function array_to_string($val, $all = true) 
	{  	
			if(is_array($val))
			{
				foreach($val as $k => $v)
				{
					$val[$k] = $this->removeslashes($v);
				}
				return $k . ', ' . $v;
			}
			else
			{
				$val = $this->removeslashes($val);
				return $val;
			}
	}
	
	function clean_string($string)
	{
		$array_find = array(
			"''",
			"'",
			"\r\n",
		);

		$array_replace = array(
			"'",
			"\'",
			"\n",
		);

		$string = str_replace($array_find, $array_replace, $this->array_to_string($string, true));
		return $string;
	}
	
	/**
	* Get key_sufix() from MX Installer
	* we have so far in beta 3
	* $dbms = 'phpbb\\db\\driver\\mysqli';
	* $acm_type = 'phpbb\\cache\\driver\\file';
	* We only need the sufix in this installer
	*/
	function get_keys_sufix($key)
	{
		$keys = explode("\\", $key);

		$i = count($keys) - 1;
		$rkey = $keys[$i];
		$rkey = str_replace("\\", "", $rkey);	

		return (isset($rkey)) ? $rkey : $key;
	}
}
// THE END
?>