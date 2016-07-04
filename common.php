<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        common.php
 * Began:       Tue Dec 17 2002
 * Date:        $Date: 2008-05-17 22:24:26 -0700 (Sat, 17 May 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 535 $
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

// error_reporting (E_ALL ^ E_NOTICE);
error_reporting (E_ALL);

// Default the site-wide variables
$gen_simple_header = false;
$eqdkp_root_path   = ( isset($eqdkp_root_path) ) ? preg_replace('/[^a-z\.\/]/', '', $eqdkp_root_path) : './';

if ( !is_file($eqdkp_root_path . 'config.php') )
{
    die('Error: could not locate configuration file.');
}

require_once($eqdkp_root_path . 'config.php');

if ( !defined('EQDKP_INSTALLED') )
{
    header('Location: ' . $eqdkp_root_path . 'install/index.php');
}

// Constants
define('EQDKP_VERSION', '1.4.0 B2');
define('NO_CACHE', true);

// Debug level [0 = Off / 1 = Render time, Query count / 2 = 1 + Show queries]
// Fixed in 1.3 so it works from config.php and obeys URL parsing of ?debug=2

if ( isset($debug) && $debug == 0 ) {

   $debug = ( isset($_GET['debug']) ) ? intval($_GET['debug']) : 0;

}

define('DEBUG', $debug);

// User Levels
define('ANONYMOUS', -1);

// User activation
define('USER_ACTIVATION_NONE',  0);
define('USER_ACTIVATION_SELF',  1);
define('USER_ACTIVATION_ADMIN', 2);

//gehALTERNATES
define('URI_ALTERNATE_ID', 'alternate_id');
define('URI_MEMBER_ID',    'member_id');
define('WOW_ARMORY_IMAGES', 'http://www.wowarmory.com/_images');
//gehALTERNATES

// Auth Options
define('A_EVENT_ADD',    1);
define('A_EVENT_UPD',    2);
define('A_EVENT_DEL',    3);
define('A_GROUPADJ_ADD', 4);
define('A_GROUPADJ_UPD', 5);
define('A_GROUPADJ_DEL', 6);
define('A_INDIVADJ_ADD', 7);
define('A_INDIVADJ_UPD', 8);
define('A_INDIVADJ_DEL', 9);
define('A_ITEM_ADD',    10);
define('A_ITEM_UPD',    11);
define('A_ITEM_DEL',    12);
define('A_NEWS_ADD',    13);
define('A_NEWS_UPD',    14);
define('A_NEWS_DEL',    15);
define('A_RAID_ADD',    16);
define('A_RAID_UPD',    17);
define('A_RAID_DEL',    18);
define('A_TURNIN_ADD',  19);
define('A_CONFIG_MAN',  20);
define('A_MEMBERS_MAN', 21);
define('A_USERS_MAN',   22);
define('A_LOGS_VIEW',   23);
define('U_EVENT_LIST',  24);
define('U_EVENT_VIEW',  25);
define('U_ITEM_LIST',   26);
define('U_ITEM_VIEW',   27);
define('U_MEMBER_LIST', 28);
define('U_MEMBER_VIEW', 29);
define('U_RAID_LIST',   30);
define('U_RAID_VIEW',   31);
define('A_PLUGINS_MAN', 32);
define('A_STYLES_MAN',  33);
define('A_BACKUP',      36);

// Backwards compatibility for pre-1.4
$dbms = ( !isset($dbms) && isset($dbtype) ) ? $dbtype : $dbms;

require($eqdkp_root_path . 'includes/functions.php');
require($eqdkp_root_path . 'includes/functions_paths.php');
require($eqdkp_root_path . 'includes/db/' . $dbms . '.php');
require($eqdkp_root_path . 'includes/eqdkp.php');
require($eqdkp_root_path . 'includes/session.php');
require($eqdkp_root_path . 'includes/class_template.php');
require($eqdkp_root_path . 'includes/eqdkp_plugins.php');
require($eqdkp_root_path . 'includes/input.php');
require($eqdkp_root_path . 'games/game_manager.php');

$tpl  = new Template();
$in   = new Input();
$user = new Session();
$db   = new $sql_db();

// Connect to the database
$db->sql_connect($dbhost, $dbname, $dbuser, $dbpass, false);

// Initialize the eqdkp module
$eqdkp = new EQdkp($eqdkp_root_path);

// Set the locale
// TODO: Shouldn't this be per-user? That was rhetorical. It should.
$cur_locale = $eqdkp->config['default_locale'];
setlocale(LC_ALL, $cur_locale);

// Ensure that, if we're not upgrading, the install folder is gone or unreadable
install_check();

// Start up the user/session management
$user->start();
$user->setup($in->get('style', 0));

// Initialize the Game Manager
$gm = new Game_Manager();

// Start plugin management
$pm = new EQdkp_Plugin_Manager(true, DEBUG);

// Populate the admin menu if we're in an admin page, they have admin permissions, and $gen_simple_header is false
if ( defined('IN_ADMIN') )
{
    if ( $user->check_auth('a_', false) )
    {
        require_once($eqdkp_root_path . 'includes/functions_admin.php');
        if ( !$gen_simple_header )
        {
            include($eqdkp_root_path . 'admin/index.php');
        }
    }
}

/**
 * Ensure that the install folder is deleted or unreadable
 *
 * @ignore
 */
function install_check()
{
    $path = dirname(__FILE__);

    // Let the page go through if we're performing an upgrade
    if ( preg_match('/(upgrade|login)\.php$/', $_SERVER['PHP_SELF']) )
    {
        return;
    }
    
    if ( file_exists($path . '/install/') && is_readable($path . '/install/') )
    {
        die("EQdkp cannot run while the <b>/install/</b> folder is readable. Please delete it or make it unreadable to continue.");
    }
}
