<?php
/**
*
* @package mxpadmin
* @copyright (c) 2016 orynider
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'MXPADMIN'									=> 'MX-Publisher ACP',
	'ACP_MXPADMIN'							=> 'MXP ACP',
	'ACP_MXPADMIN_CONFIG'				=> 'MX-Publisher ACP',
	'CONFIGURATION_TITLE'					=> 'MX-Publisher ACP Link',	
	'ACP_SHORT_LINK'							=> 'MXP Link',	
	'ACP_SHORT_EXPLAIN'						=> 'Thank you for choosing phpBB as your board solution. This screen will give you a quick overview of all the various statistics of your board. The links on the left hand side of this screen allow you to control every aspect of your board experience. Each page will have instructions on how to use the tools.',	
	'PORTAL_MANAGE_INTRO'				=> 'Manage Configuration',	
	'CONFIGURATION_EXPLAIN'				=> 'Use the link in this pannel to enter MX-Publisher ACP, and preview the configuration of MX-Publisher CMS that You validate bellow.',	
	'MXPADMIN_DONATE'						=> '<a href="https://www.paypal.me/orynider"><strong>Donate</strong></a>',
	'MXPADMIN_DONATE_EXPLAIN'		=> 'If you like this extension considers a donation',
	'ACP_INTRO_MXPADMIN' 				=> 'Thank you for choosing MX-Publisher as your portal/cms solution and phpBB as your forum solution. <br />This screen will give you a quick overview of all the various statistics of your site. You can get back to this page by clicking on the <span style="text-decoration: underline;">Admin Index</span> link in the left panel. To return to the index of your board, click the logo that is also in the left panel. The other links on the left hand side of this screen will allow you to control every aspect of your portal and forum experience. Each screen will have instructions on how to use the provided tools.<br />',
	'PORTAL_BACKEND'							=> 'MXP Session backend',	
	'READONLY'										=> ' (read only)',
	
	'PORTAL_ADMIN' 								=> 'Portal Administration',
	'PORTAL_ADMIN_EXPLAIN' 				=> 'Use this form to customize your portal',
	
	'PORTAL_GENERAL_CONFIG' 			=> 'Portal Configuration',
	'PORTAL_GENERAL_CONFIG_EXPLAIN'	=> 'Use this form to manage the main settings of your MX-Publisher site.',
	'PORTAL_GENERAL_SETTINGS'				=> 'General Settings',
	'PORTAL_STYLE_SETTINGS'					=> 'Style Settings',
	
	'PORTAL_GENERAL_CONFIG_INFO' 		=> 'General Portal Config Info ',
	'PORTAL_GENERAL_CONFIG_INFO_EXPLAIN'		=> 'Current setup info from config.php (no editing needed)',
	
	'PORTAL_NAME' 									=> 'Portal Name',
	'PORTAL_DESC'										=> 'A little text to describe your website.',	
	'PORTAL_PHPBB_URL' 							=> 'URL to your phpBB installation',
	'PORTAL_URL' 										=> 'URL to MX-Publisher',
	'PORTAL_CONFIG_UPDATED' 				=> 'Portal Configuration Updated Successfully',

	'PORTAL_VERSION' 								=> 'MX-Publisher Version',

	'PORTAL_OVERALL_HEADER' 				=> 'Overall Header File (default value)',
	'PORTAL_OVERALL_HEADER_EXPLAIN' 	=> '- This is the default template overall_header file, e.g. overall_header.tpl.',

	'PORTAL_OVERALL_FOOTER' 				=> 'Overall Footer File (default value)',
	'PORTAL_OVERALL_FOOTER_EXPLAIN' 	=> '- This is the default template overall_footer file, e.g. overall_footer.tpl.',

	'PORTAL_MAIN_LAYOUT' 						=> 'Main Layout File (default value)',
	'PORTAL_MAIN_LAYOUT_EXPLAIN' 		=> '- This is the default template main_layout file, e.g. mx_main_layout.tpl.',
	'PORTAL_NAVIGATION_BLOCK' 			=> 'Overall Navigation Block (default value)',
	'PORTAL_NAVIGATION_BLOCK_EXPLAIN' 		=> '- This is the page header navigation block, provided you\'ve chosen a overall header file which supports page navigation.',

	'PORTAL_STATUS' 							=> 'Enable portal',
	'PORTAL_STATUS_EXPLAIN' 				=> 'Handy switch, when reconstructing the site. Only admin is able to view pages and browse around normally. While disabled, the message below is displayed.',
	'DISABLED_MESSAGE' 						=> 'Portal disabled message',

	'MX_USE_CACHE' 								=> 'Use MX-Publisher Block Cache',
	'MX_USE_CACHE_EXPLAIN' 				=> 'Block data is cached to individual cache/block_*.xml files. Block cache files are created/updated when blocks are edited.',
	'MX_MOD_REWRITE' 						=> 'Use mod_rewrite',
	'MX_MOD_REWRITE_EXPLAIN' 			=> 'If you\'re running on an Apache server and have mod_rewrite activated, you may rewrite URLS; for example, you can rewrite pages like \'page=x\' with more intuitive alternatives. Please read further documentation for the mx_mod_rewrite module.',
	
	'PORTAL_BACKEND' 							=> 'User/Session backend',
	'PORTAL_BACKEND_EXPLAIN' 			=> 'Select internal, phpBB2 or phpBB3 sessions and users',
	'PORTAL_BACKEND_PATH' 				=> 'Relative path to phpBB [non-internal]',
	'PORTAL_BACKEND_PATH_EXPLAIN' => 'If using non-internal sessions and users, enter the relative path to phpbb, eg \'phpBB2/\' or \'../phpBB2/\'. Note: slashes are important.',
	'PORTAL_BACKEND_SUBMIT' 			=> 'Change and validate Backend',
	'PORTAL_CONFIG_VALID' 					=> 'Current Backend Status',
	'PORTAL_CONFIG_VALID_TRUE' 		=> '<b><font color="green">Valid</font></b>',
	'PORTAL_CONFIG_VALID_FALSE' 		=> '<b><font color="red">Bad Setup. Either your phpBB relative path is wrong or phpBB is uninstalled (your phpBB database is unavailable). Thus, \'internal\' backend is used.</font></b>',

	// Autologin time - added 2.0.18
	'AUTOLOGIN_TIME' 					=> 'Automatic login key expiry',
	'AUTOLOGIN_TIME_EXPLAIN' 	=> 'How long a autologin key is valid for in days if the user does not visit the board. Set to zero to disable expiry.',

	'LOGIN_RESET_TIME' 				=> 'Login lock time',
	'LOGIN_RESET_TIME_EXPLAIN' 	=> 'Time in minutes the user have to wait until he is allowed to login again after exceeding the number of allowed login attempts.',
	
	'ACP_INFO'							=> 'phpBB Informations',

	'PHPBB_VERSION'					=> 'phpBB Version',
	'PHPBB_RELATIVE_PATH'		=> 'phpBB Relative Path',
	'PHPBB_SCRIPT_PATH'			=> 'phpBB Script Path',
	'PHPBB_SERVER_NAME'		=> 'phpBB Domain (server_name)',
	
));
