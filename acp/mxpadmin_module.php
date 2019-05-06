<?php
/**
 *
* @package phpBB Extension - mxpadmin
* @copyright (c) 2016 orynider - http://mxpcms.sourceforge.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2 (GPL-2.0)
 *
 */

namespace orynider\mxpadmin\acp;

/**
 * mxpadmin ACP module.
 */
class mxpadmin_module
{
	public $u_action;
	protected $action;
	protected $table;
	protected $config;
	protected $db;
	protected $user;
	protected $template;
	protected $request;
	
	public function main($id, $mode)
	{
		global $config, $db, $phpbb_container, $user, $template, $request, $table_prefix;
		
		// Get an instance of the admin controller
		$admin_controller = $phpbb_container->get('orynider.mxpadmin.controller.admin.controller');

		$user->add_lang_ext('orynider/mxpadmin', 'common');
		
		// Requests
		$action = $request->variable('action', '');
		if ($request->is_set_post('add'))
		{
			$action = 'add';
		}
		
		// Make the $u_action url available in the admin controller
		$admin_controller->set_page_url($this->u_action);
		
		// Here we set the main switches to use within the ACP
		switch ($mode)
		{
			case 'manage':
			default:
				$this->page_title = $user->lang('ACP_PORTAL_CONFIG');
				$this->tpl_name = 'admin_mx_portal';
				$admin_controller->manage_portal_config();
			break;	
		}
	}
}