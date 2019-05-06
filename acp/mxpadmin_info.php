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
 * MXP ACP module info.
 */
class mxpadmin_info
{
	function module()
	{
		return array(
			'filename'	=> '\orynider\mxpadmin\acp\mxpadmin_module',
			'title'		=> 'MXPADMIN',
			'modes'		=> array(
				'manage'	=> array('title' => 'ACP_MXPADMIN_CONFIG', 
									'auth' => 'ext_orynider/mxpadmin && acl_a_board', 
									'cat' => array('ACP_MXPADMIN')
				),
			),
		);
	}
}
