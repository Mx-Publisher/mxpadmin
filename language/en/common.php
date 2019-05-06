<?php
/**
*
* @package Topic title
* @copyright (c) 2016 FlorinCB (orynider)
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* DO NOT CHANGE
*/
if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

$lang = array_merge($lang, array(
	'MXPADMIN'							=> 'MXP ACP',
	'ACP_PORTAL_CONFIG'				=> 'MX-Publisher ACP Link',
	'TOPIC_TITLE_3110'	=> 'This extension requires at least phpBB version 3.1.10.  Please update your version of the forum software.',
));
