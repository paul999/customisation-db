<?php
/**
 *
 * @package titania
 * @version $Id$
 * @copyright (c) 2008 phpBB Customisation Database Team
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
* @ignore
*/
define('IN_TITANIA', true);
if (!defined('TITANIA_ROOT')) define('TITANIA_ROOT', './../');
if (!defined('PHP_EXT')) define('PHP_EXT', substr(strrchr(__FILE__, '.'), 1));
include(TITANIA_ROOT . 'common.' . PHP_EXT);
include(PHPBB_ROOT_PATH . 'includes/functions_module.' . PHP_EXT);
include(TITANIA_ROOT . 'includes/titania_cache.' . PHP_EXT);

$cache = new titania_cache();

$id		= request_var('id', 'main');
$mode	= request_var('mode', '');

$module = new p_master();

// Instantiate module system and generate list of available modules
$module->list_modules('mods');

// Select the active module
$module->set_active($id, $mode);

// Load and execute the relevant module
// trick the module class to allow modules to be loaded from the titania includes path.
$phpbb_root_path = TITANIA_ROOT;
$module->load_active();
$phpbb_root_path = PHPBB_ROOT_PATH;

// Assign data to the template engine for the list of modules
$module->assign_tpl_vars(append_sid(TITANIA_ROOT . 'mods/index.' . PHP_EXT));

// $titania->page_footer(false);
$titania->page_header($module->get_page_title(), false);

$template->set_filenames(array(
	'body' => $module->get_tpl_name(),
));

$titania->page_footer();
