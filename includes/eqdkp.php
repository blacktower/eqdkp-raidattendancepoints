<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        eqdkp.php
 * Began:       Sat Dec 21 2002
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 516 $
 */

if ( !defined('EQDKP_INC') )
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

/**
 * EQdkp foundation class
 * Common page functionality
 * Available to all pages as $eqdkp
 */
class EQdkp
{
    // General vars
    var $config     = array();                  // Config values            @var config
    var $row_class  = 'row1';                   // Alternating row class    @var row_class

    // Output vars
    var $root_path         = './';              // Path to EQdkp's root     @var root_path
    var $gen_simple_header = false;             // Use a simple header?     @var gen_simple_header
    var $page_title        = '';                // Page title               @var page_title
    var $template_file     = '';                // Template file to parse   @var template_file
    var $template_path     = '';                // Path to template_file    @var template_path
    var $extra_css         = '';                // Extra CSS styles         @var extra_css

    // Timer vars
    var $timer_start = 0;                       // Page timer start         @var timer_start
    var $timer_end   = 0;                       // Page timer end           @var timer_end

    function eqdkp($eqdkp_root_path = './')
    {
        // Start a script timer if we're debugging
        if ( DEBUG )
        {
            $mc_split = split(' ', microtime());
            $this->timer_start = $mc_split[0] + $mc_split[1];
            unset($mc_split);
        }

        $this->root_path = $eqdkp_root_path;

        $this->config();
    }

    function config()
    {
        global $db;

        if ( !is_object($db) )
        {
            trigger_error('Database object not instantiated', E_USER_ERROR);
        }

        $sql = 'SELECT config_name, config_value
                FROM __config';

        if ( !($result = $db->query($sql)) )
        {
            trigger_error('Could not obtain configuration information', E_USER_ERROR);
        }
        while ( $row = $db->fetch_record($result) )
        {
            if ( !is_numeric($row['config_name']) )
            {
                $this->config[$row['config_name']] = $row['config_value'];
            }
        }

        return true;
    }

    function config_set($config_name, $config_value='')
    {
        global $db;

        if ( is_object($db) )
        {
            if ( is_array($config_name) )
            {
                foreach ( $config_name as $d_name => $d_value )
                {
                    $this->config_set($d_name, $d_value);
                }
            }
            else
            {
                $sql = "REPLACE INTO __config (config_name, config_value)
                        VALUES ('" . $db->escape($config_name) . "', '" . $db->escape($config_value) . "')";
                $db->query($sql);

                // Update or insert the array value for immediate use
                $this->config[$config_name] = $config_value;

                return true;
            }
        }

        return false;
    }

    function switch_row_class($set_new = true)
    {
        $row_class = ( $this->row_class == 'row1' ) ? 'row2' : 'row1';

        if ( $set_new )
        {
            $this->row_class = $row_class;
        }

        return $row_class;
    }

    /**
     * Set object variables
     * NOTE: If the last var is 'display' and the val is TRUE, EQdkp::display() is called
     *   automatically
     *
     * @var $var Var to set
     * @var $val Value for Var
     * @return bool
     */
    function set_vars($var, $val = '', $append = false)
    {
        if ( is_array($var) )
        {
            foreach ( $var as $d_var => $d_val )
            {
                $this->set_vars($d_var, $d_val);
            }
        }
        else
        {
            if ( empty($val) )
            {
                return false;
            }
            if ( ($var == 'display') && ($val === true) )
            {
                $this->display();
            }
            else
            {
                if ( $append )
                {
                    if ( is_array($this->$var) )
                    {
                        $this->{$var}[] = $val;
                    }
                    elseif ( is_string($this->$var) )
                    {
                        $this->$var .= $val;
                    }
                    else
                    {
                        $this->$var = $val;
                    }
                }
                else
                {
                    $this->$var = $val;
                }
            }
        }

        return true;
    }

    function display()
    {
        $this->page_header();
        $this->page_tail();
    }

    function page_header()
    {
        global $db, $user, $tpl, $pm;

        // Define a variable so we know the header's been included
        define('HEADER_INC', true);

        // Use gzip if available
        if ( $this->config['enable_gzip'] == '1' )
        {
            if ( (extension_loaded('zlib')) && (!headers_sent()) )
            {
                @ob_start('ob_gzhandler');
            }
        }

        // Send the HTTP headers
        $now = gmdate('D, d M Y H:i:s', time()) . ' GMT';
        if ( defined('NO_CACHE') )
        {
            @header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
            @header('Last-Modified: ' . $now);
            @header('Cache-Control: no-store, no-cache, must-revalidate');
            @header('Cache-Control: post-check=0, pre-check=0', false);
            @header('Pragma: no-cache');
            @header('Content-Type: text/html; charset=iso-8859-1');
        }
        else
        {
            @header('Last-Modified: ' . $now);
            @header('Content-Type: text/html; charset=iso-8859-1');
        }

        // Assign global template variables
        $tpl->assign_vars(array(
            'ENCODING'        => $user->lang['ENCODING'],
            'XML_LANG'        => $user->lang['XML_LANG'],
            'PAGE_TITLE'      => sanitize($this->page_title, TAG),
            'MAIN_TITLE'      => sanitize($this->config['main_title']),
            'SUB_TITLE'       => sanitize($this->config['sub_title']),
            'EQDKP_ROOT_PATH' => $this->root_path,
            'TEMPLATE_PATH'   => $this->root_path . 'templates/' . $user->style['template_path']
        ));

        $s_in_admin = ( defined('IN_ADMIN') ) ? IN_ADMIN : false;
        $s_in_admin = ( ($s_in_admin) && ($user->check_auth('a_', false)) ) ? true : false;

        $tpl->assign_vars(array(
            'S_NORMAL_HEADER' => false,
            'S_ADMIN'         => $user->check_auth('a_', false),
            'S_IN_ADMIN'      => $s_in_admin,

            'URI_ADJUSTMENT' => URI_ADJUSTMENT,
            'URI_EVENT'      => URI_EVENT,
            'URI_ITEM'       => URI_ITEM,
            'URI_LOG'        => URI_LOG,
            'URI_NAME'       => URI_NAME,
            'URI_NEWS'       => URI_NEWS,
            'URI_ORDER'      => URI_ORDER,
            'URI_PAGE'       => URI_PAGE,
            'URI_RAID'       => URI_RAID,
            'URI_SESSION'    => URI_SESSION,

            // NOTE: Legacy support to prevent breaking URLs for people who don't update templates
            'SID' => '?s=',
        
            // Theme Settings
            'T_FONTFACE1'          => $user->style['fontface1'],
            'T_FONTFACE2'          => $user->style['fontface2'],
            'T_FONTFACE3'          => $user->style['fontface3'],
            'T_FONTSIZE1'          => $user->style['fontsize1'],
            'T_FONTSIZE2'          => $user->style['fontsize2'],
            'T_FONTSIZE3'          => $user->style['fontsize3'],
            'T_FONTCOLOR1'         => $user->style['fontcolor1'],
            'T_FONTCOLOR2'         => $user->style['fontcolor2'],
            'T_FONTCOLOR3'         => $user->style['fontcolor3'],
            'T_FONTCOLOR_NEG'      => $user->style['fontcolor_neg'],
            'T_FONTCOLOR_POS'      => $user->style['fontcolor_pos'],
            'T_BODY_BACKGROUND'    => $user->style['body_background'],
            'T_TABLE_BORDER_WIDTH' => $user->style['table_border_width'],
            'T_TABLE_BORDER_COLOR' => $user->style['table_border_color'],
            'T_TABLE_BORDER_STYLE' => $user->style['table_border_style'],
            'T_BODY_LINK'          => $user->style['body_link'],
            'T_BODY_LINK_STYLE'    => $user->style['body_link_style'],
            'T_BODY_HLINK'         => $user->style['body_hlink'],
            'T_BODY_HLINK_STYLE'   => $user->style['body_hlink_style'],
            'T_HEADER_LINK'        => $user->style['header_link'],
            'T_HEADER_LINK_STYLE'  => $user->style['header_link_style'],
            'T_HEADER_HLINK'       => $user->style['header_hlink'],
            'T_HEADER_HLINK_STYLE' => $user->style['header_hlink_style'],
            'T_TH_COLOR1'          => $user->style['th_color1'],
            'T_TR_COLOR1'          => $user->style['tr_color1'],
            'T_TR_COLOR2'          => $user->style['tr_color2'],
            'T_INPUT_BACKGROUND'   => $user->style['input_color'],
            'T_INPUT_BORDER_WIDTH' => $user->style['input_border_width'],
            'T_INPUT_BORDER_COLOR' => $user->style['input_border_color'],
            'T_INPUT_BORDER_STYLE' => $user->style['input_border_style'],

//gehAVATARS
            'EXTRA_CSS' => getClassCSS() . $this->extra_css
//gehEND
        ));

        //
        // Menus
        //
        $menus = $this->gen_menus();
        $main_menu1 = '';
        $main_menu2 = '';

        foreach ( $menus as $number => $array )
        {
            foreach ( $array as $menu )
            {
                // Don't display the link if they don't have permission to view it
                if ( (empty($menu['check'])) || ($user->check_auth($menu['check'], false)) )
                {
                    $var = 'main_' . $number;
                    ${$var} .= '<a href="' . $menu['link'] . '" class="copy" target="_top">' . $menu['text'] . '</a> &middot; ';
                }
            }
        }
        // Remove the trailing ' | ' from menus
        $main_menu1 = preg_replace('# \&middot; $#', '', $main_menu1);
        $main_menu2 = preg_replace('# \&middot; $#', '', $main_menu2);

        if ( !$this->gen_simple_header )
        {
            $tpl->assign_vars(array(
                'LOGO_PATH' => $user->style['logo_path'],

                'S_NORMAL_HEADER' => true,
                'S_LOGGED_IN'     => ( $user->data['user_id'] != ANONYMOUS ) ? true : false,

                // Menu
                'MAIN_MENU1' => $main_menu1,
                'MAIN_MENU2' => $main_menu2
            ));
        }
    }

    function gen_menus()
    {
        global $user, $pm;

        //
        // Menu 1
        //
        $main_menu1 = array(
//gehSTART - Swap News for link to guild portal. Remove Events and Summary links from the main menu
            array('link' => 'http://sknights.com',            'text' => "SK HOME",     'check' => ''),
            array('link' => path_default('viewnews.php'),     'text' => $user->lang['menu_news'],      'check' => ''),
            array('link' => path_default('listmembers.php'),  'text' => $user->lang['menu_standings'], 'check' => 'u_member_list'),
            array('link' => path_default('listraids.php'),    'text' => $user->lang['menu_raids'],     'check' => 'u_raid_list'),
//            array('link' => path_default('listevents.php'),   'text' => $user->lang['menu_events'],    'check' => 'u_event_list'),
            array('link' => path_default('listitems.php'),    'text' => $user->lang['menu_itemval'],   'check' => 'u_item_list'),
            array('link' => path_default('listitems.php')
                          . path_params(URI_PAGE, 'history'), 'text' => $user->lang['menu_itemhist'],  'check' => 'u_item_list'),
//            array('link' => path_default('summary.php'),      'text' => $user->lang['menu_summary'],   'check' => 'u_raid_list'),
            array('link' => path_default('stats.php'),        'text' => $user->lang['menu_stats'],     'check' => 'u_member_list')
        );

        $main_menu1 = (is_array($pm->get_menus('main_menu1'))) ? array_merge($main_menu1, $pm->get_menus('main_menu1')) : $main_menu1;

        //
        // Menu 2
        //
        $main_menu2 = array();
        if ( $user->data['user_id'] != ANONYMOUS )
        {
            $main_menu2[] = array('link' => path_default('settings.php'), 'text' => $user->lang['menu_settings']);
        }
        else
        {
            $main_menu2[] = array('link' => path_default('register.php'), 'text' => $user->lang['menu_register']);
        }

        if ( $user->check_auth('a_', false) )
        {
            $main_menu2[] = array('link' => path_default('admin/index.php'), 'text' => $user->lang['menu_admin_panel']);
        }

        // Switch login/logout link
        if ( $user->data['user_id'] != ANONYMOUS )
        {
            $main_menu2[] = array('link' => path_default('login.php') . path_params('logout', 'true'), 'text' => $user->lang['logout'] . ' [ ' . sanitize($user->data['user_name']) . ' ]');
        }
        else
        {
            $main_menu2[] = array('link' => path_default('login.php'), 'text' => $user->lang['login']);
        }

        $main_menu2 = (is_array($pm->get_menus('main_menu2'))) ? array_merge($main_menu2, $pm->get_menus('main_menu2')) : $main_menu2;

        $menus = array(
            'menu1' => $main_menu1,
            'menu2' => $main_menu2
        );

        return $menus;
    }

    function page_tail()
    {
        global $db, $user, $tpl, $pm;

        if ( !empty($this->template_path) )
        {
            $tpl->set_template($user->style['template_path'], $this->template_path);
        }

        if ( empty($this->template_file) )
        {
            trigger_error('Template file is empty.', E_USER_ERROR);
            return false;
        }

        $tpl->set_filenames(array(
            'body' => $this->template_file
        ));

        // Hiding the copyright/debug info if gen_simple_header is set
        if ( !$this->gen_simple_header )
        {
            $tpl->assign_vars(array(
                'S_NORMAL_FOOTER' => true,
                'L_POWERED_BY'    => $user->lang['powered_by'],
                'EQDKP_VERSION'   => EQDKP_VERSION
            ));

            if ( DEBUG )
            {
                $mc_split = split(' ', microtime());
                $this->timer_end = $mc_split[0] + $mc_split[1];
                unset($mc_split);

                $s_show_queries = ( DEBUG == 2 ) ? true : false;

                $tpl->assign_vars(array(
                    'S_SHOW_DEBUG'     => true,
                    'S_SHOW_QUERIES'   => $s_show_queries,
                    'EQDKP_RENDERTIME' => substr($this->timer_end - $this->timer_start, 0, 5),
                    'EQDKP_QUERYCOUNT' => count($db->queries)
                ));

                if ( $s_show_queries )
                {
                    foreach ( $db->queries as $query )
                    {
                        $tpl->assign_block_vars('query_row', array(
                            'ROW_CLASS' => $this->switch_row_class(),
                            'QUERY'     => $this->sql_highlight(sanitize($query, ENT))
                        ));
                    }
                }
            }
            else
            {
                $tpl->assign_vars(array(
                    'S_SHOW_DEBUG'   => false,
                    'S_SHOW_QUERIES' => false
                ));
            }
        }
        else
        {
            $tpl->assign_vars(array(
                'S_NORMAL_FOOTER' => false
            ));
        }

        // Close our DB connection.
        $db->close_db();

        // Get rid of our template data
        $tpl->display('body');
        $tpl->destroy();

        exit;
    }

    /**
     * Highlight certain keywords in a SQL query
     *
     * @param $sql Query string
     * @return string Highlighted string
     */
    function sql_highlight($sql)
    {
        global $table_prefix;

        // Make table names bold
        $sql = preg_replace('/' . $table_prefix .'(\S+?)([\s\.,]|$)/', '<b>' . $table_prefix . "\\1\\2</b>", $sql);

        // Non-passive keywords
        $red_keywords = array('/(INSERT INTO)/','/(UPDATE\s+)/','/(DELETE FROM\s+)/', '/(CREATE TABLE)/', '/(IF (NOT)? EXISTS)/',
                              '/(ALTER TABLE)/', '/(CHANGE)/', '/(REPLACE INTO)/');
        $red_replace = array_fill(0, sizeof($red_keywords), '<span class="negative">\\1</span>');
        $sql = preg_replace($red_keywords, $red_replace, $sql);

        // Passive keywords
        $green_keywords = array('/(SELECT)/','/(FROM)/','/(WHERE)/','/(LIMIT)/','/(ORDER BY)/','/(GROUP BY)/',
                                '/(\s+AND\s+)/','/(\s+OR\s+)/','/(BETWEEN)/','/(DESC)/','/(LEFT JOIN)/');

        $green_replace = array_fill(0, sizeof($green_keywords), '<span class="positive">\\1</span>');
        $sql = preg_replace($green_keywords, $green_replace, $sql);

        return $sql;
    }
}

/**
 * EQdkp admin page foundation
 * Extended by admin page classes only
 */
class EQdkp_Admin
{
    // General vars
    var $buttons      = array();          // Submit buttons and their associated actions      @var buttons
    var $params       = array();          // GET parameters and their associated actions      @var params
    var $err_process  = 'display_form';   // Process to call when errors occur                @var err_process
    var $url_id       = 0;                // ID from _GET                                     @var url_id
    var $fv           = NULL;             // Form Validation object (not reference)           @var fv
    var $time         = 0;                // Current time                                     @var time

    // Delete confirmation vars
    var $confirm_text  = '';              // Message to display for confirmation              @var confirm_text
    var $script_name   = '';              // e.g., eqdkp.php                                  @var script_name
    var $uri_parameter = '';              // URI parameter                                    @var uri_parameter

    // Logging vars
    var $log_fields = array('log_id', 'log_date', 'log_type', 'log_action', 'log_ipaddress', 'log_sid', 'log_result', 'admin_id');
    var $log_values = array();            // Holds default log values                         @var log_values
    var $admin_user = '';                 // Username of admin                                @var admin_user

    function eqdkp_admin()
    {
        global $user;

        // Store our Form Validation object
        $this->fv = new Form_Validate;

        // Determine the script name based on PHP_SELF
        $this->script_name = preg_replace('#.+/(.+\.php)$#', '\1', $_SERVER['PHP_SELF']);

        // Default our log values
        $this->log_values = array(
            'log_id'        => 'NULL',
            'log_date'      => time(),
            'log_type'      => NULL,
            'log_action'    => NULL,
            'log_ipaddress' => ( isset($user->data['session_ip']) ) ? $user->data['session_ip'] : $user->ip,
            'log_sid'       => ( isset($user->data['session_id']) ) ? $user->data['session_id'] : null,
            'log_result'    => '{L_SUCCESS}',
            'admin_id'      => $user->data['user_id']
        );

        $this->admin_user = ( $user->data['user_id'] != ANONYMOUS ) ? $user->data['user_name'] : '';
        $this->time = time();
    }

    /**
     * Build the $buttons array
     *
     * @param $buttons Array of button => name/process/auth_check values
     * @return bool
     */
    function assoc_buttons($buttons)
    {
        if ( !is_array($buttons) )
        {
            return false;
        }

        foreach ( $buttons as $code => $button )
        {
            $this->buttons[$code] = $button;
        }

        return true;
    }

    function assoc_params($params)
    {
        if ( !is_array($params) )
        {
            return false;
        }

        foreach ( $params as $code => $param )
        {
            $this->params[$code] = $param;
        }

        return true;
    }

    function process()
    {
        global $user, $in;

        $errors_exist = false;
        $processed    = false;

        // Form has been submitted
        // NOTE: This is a rare case acceptable use of POST, do not change.
        if ( count($_POST) > 0 )
        {
            // Confirm is an automatic button option if confirm_delete is called
            if ( $in->get('confirm', false) )
            {
                if ( method_exists($this, 'process_confirm') )
                {
                    $processed = true;
                    if ( isset($this->buttons['delete']['check']) )
                    {
                        $user->check_auth($this->buttons['delete']['check']);
                    }
                    $this->process_confirm();
                }
            }
            // Cancel is an automatic button option if confirm_delete is called
            elseif ( $in->get('cancel', false) )
            {
                $processed = true;
                $this->process_cancel();
            }
            // Confirm/Delete weren't pressed, we're dealing with custom processes now
            else
            {
                // Check for errors
                $this->process_error_check();

                foreach ( $this->buttons as $code => $button )
                {
                    if ( $in->get($button['name'], false) )
                    {
                        $processed = true;
                        if ( isset($button['check']) )
                        {
                            $user->check_auth($button['check']);
                        }
                        $this->$button['process']();
                    }
                }
            }
        }

        // With the Input class, there's no longer a need to differentiate
        // between GET and POST, but I don't want to refactor this code, it's been
        // too long, and if it aint' broke...

        // No POST vars, check for GET vars and process as necessary
        foreach ( $this->params as $code => $param )
        {
            if ( $in->get($param['name'], false) )
            {
                if ( isset($param['value']) )
                {
                    if ( $in->get($param['name']) == $param['value'] )
                    {
                        $this->process_error_check();
                        $processed = true;
                        if ( isset($param['check']) )
                        {
                            $user->check_auth($param['check']);
                        }
                        $this->$param['process']();
                    }
                }
                else
                {
                    $this->process_error_check();
                    $processed = true;
                    if ( isset($param['check']) )
                    {
                        $user->check_auth($param['check']);
                    }
                    $this->$param['process']();
                }
            }
        }

        // Nothing was processed
        if ( !$processed )
        {
            if ( (isset($this->buttons['form'])) && (is_array($this->buttons['form'])) )
            {
                if ( isset($this->buttons['form']['check']) )
                {
                    $user->check_auth($this->buttons['form']['check']);
                }
                $process = $this->buttons['form']['process'];
                $this->$process();
            }
            else
            {
                return false;
            }
        }
    }

    function process_error_check()
    {
        // Check for errors
        if ( method_exists($this, 'error_check') )
        {
            $errors_exist = $this->error_check();

            // Errors exist, redisplay the form
            if ( $errors_exist )
            {
                $process = $this->err_process;
                $this->$process();
            }
        }
    }

    // ---------------------------------------------------------
    // Default process methods
    // ---------------------------------------------------------

    function process_delete()
    {
        $this->_confirmDelete($this->confirm_text, $this->uri_parameter, $this->url_id, $this->script_name);
    }

    function process_cancel()
    {
        if ( empty($this->script_name) )
        {
            message_die('Cannot redirect to an empty script name.');
        }

        if ( defined('PLUGIN') )
        {
            $script_path = 'plugins/' . PLUGIN . '/';
        }
        elseif ( defined('IN_ADMIN') )
        {
            $script_path = 'admin/';
        }
        else
        {
            $script_path = '';
        }

        if ( $this->url_id )
        {
            $redirect = path_default($script_path . $this->script_name) . path_params($this->uri_parameter, $this->url_id);
        }
        else
        {
            $redirect = path_default($script_path . $this->script_name);
        }

        redirect($redirect);
    }

    /**
     * Set object variables
     *
     * @var $var Var to set
     * @var $val Value for Var
     * @return bool
     */
    function set_vars($var, $val = '')
    {
        global $in;

        if ( is_array($var) )
        {
            foreach ( $var as $d_var => $d_val )
            {
                $this->set_vars($d_var, $d_val);
            }
        }
        else
        {
            if ( empty($val) )
            {
                return false;
            }

            $this->$var = $val;
        }

        //
        // Set url_id if it hasn't already been set
        if ( !$this->url_id )
        {
            // NOTE: We can't use Input::get's default argument here because the parameter won't always be an integer
            // This is the one spot so far where auto-discovering the variable type works against us.
            switch( $this->uri_parameter )
            {
                case URI_ADJUSTMENT:
                case URI_EVENT:
                case URI_ITEM:
                case URI_LOG:
                case URI_NEWS:
                case URI_RAID:
                    $this->url_id = $in->get($this->uri_parameter, 0);
                break;

                case URI_ORDER:
                    $this->url_id = $in->get($this->uri_parameter, 0.0);
                break;

                default:
                    $this->url_id = $in->get($this->uri_parameter, '');
                break;
            }
        }

        return true;
    }

    function make_log_action($action = array())
    {
        global $db, $in;

        $str_action = "\$log_action = array(";
        foreach ( $action as $k => $v )
        {
            $str_action .= "'" . $k . "' => '" . $db->escape($v) . "',";
        }
        $action = substr($str_action, 0, strlen($str_action) - 1) . ");";

        // Remove excessive spacing
        $action = preg_replace("/\s+/", ' ', $action);

        return $action;
    }

    function log_insert($values = array())
    {
        global $db;

        if ( sizeof($values) > 0 )
        {
            // If they set the value, we use theirs, otherwise we use the default
            foreach ( $this->log_fields as $field )
            {
                $values[$field] = ( isset($values[$field]) ) ? $values[$field] : $this->log_values[$field];

                if ( $field == 'log_action' )
                {
                    $values[$field] = $this->make_log_action($values[$field]);
                }
            }

            $db->query("INSERT INTO __logs :params", $values);

            return true;
        }
        return false;
    }

    /**
     * Takes two variables of the same type and compares them, marking in red
     * any items that the two don't have in common
     *
     * @param $value1 The first, or 'old' value
     * @param $value2 The second, or 'new' value
     * @param $return_var Which of the two to return
     */
    function find_difference($value1, $value2, $return_var = 2)
    {
        if ( ($return_var != 1) && ($return_var != 2) )
        {
            $return_var = 2;
        }

        // FIXME: The span tags are removed by sanitize() called by make_log_action()
        // Do we just mark this as an acceptable sacrifice for the improvement of safety?
        if ( (is_array($value1)) && (is_array($value2)) )
        {
            foreach ( $value1 as $k => $v )
            {
                $v = preg_replace("#(\\\){1,}\'#", "'", $v);

                if ( !in_array($v, $value2) )
                {
                    $value1[$k] = '<span class="negative">'.$v.'</span>';
                }
            }
            foreach ( $value2 as $k => $v )
            {
                $v = preg_replace("#(\\\){1,}\'#", "'", $v);

                if ( !in_array($v, $value1) )
                {
                    $value2[$k] = '<span class="negative">'.$v.'</span>';
                }
            }
        }
        elseif ( (!is_array($value1)) && (!is_array($value2)) )
        {
            $value1 = preg_replace("#(\\\){1,}\'#", "'", $value1);
            $value2 = preg_replace("#(\\\){1,}\'#", "'", $value2);

            if ( $value1 != $value2 )
            {
                $value2 = '<span class="negative">'.$value2.'</span>';
            }

            $value2 = addslashes($value2);
        }

        $valueX = 'value'.$return_var;

        return ${$valueX};
    }

    function admin_die($message, $link_list = array())
    {
        global $eqdkp, $user, $tpl, $pm;

        if ( (is_array($link_list)) && (sizeof($link_list) > 0) )
        {
            $message .= '<br /><br />' . $this->generate_link_list($link_list);
        }

        message_die(stripslashes($message));
    }

    /**
     * Returns a bulleted list of links to display after an admin event
     * has been completed
     *
     * @param $links Array of links
     * @return string Link list
     */
    function generate_link_list($links)
    {
        $link_list = '<ul>';

        if ( is_array($links) )
        {
            foreach ( $links as $k => $v )
            {
                $link_list .= '<li><a href="'.$v.'">'.$k.'</a></li>';
            }
        }
        $link_list .= '</ul>';

        return $link_list;
    }

    function gen_group_key($part1, $part2, $part3)
    {
        // Normalize data
        $part1 = htmlspecialchars(stripslashes($part1));
        $part2 = htmlspecialchars(stripslashes($part2));
        $part3 = htmlspecialchars(stripslashes($part3));

        // Get the first 10-11 digits of each md5 hash
        $part1 = substr(md5($part1), 0, 10);
        $part2 = substr(md5($part2), 0, 11);
        $part3 = substr(md5($part3), 0, 11);

        // Group the hashes together and create a new hash based on uniqid()
        $group_key = $part1 . $part2 . $part3;
        $group_key = md5(uniqid($group_key));

        return $group_key;
    }

    /**
     * Outputs a message asking the user if they're sure they want to delete something
     *
     * @param $confirm_text Confirm message
     * @param $uri_parameter URI_RAID, URI_NAME, etc.
     * @param $parameter_value Value of the parameter
     * @param $action Form action
     */
    function _confirmDelete($confirm_text, $uri_parameter, $parameter_value, $action = '')
    {
        global $eqdkp, $tpl, $user;
        global $gen_simple_header;

        if ( !defined('HEADER_INC') )
        {
            $eqdkp->set_vars(array(
                'page_title'        => page_title(),
                'gen_simple_header' => $gen_simple_header,
                'template_file'     => 'admin/confirm_delete.html'
            ));

            $eqdkp->page_header();
        }

        $tpl->assign_vars(array(
            'F_CONFIRM_DELETE_ACTION' => ( !empty($action) ) ? $action : $_SERVER['PHP_SELF'],

            'URI_PARAMETER'   => $uri_parameter,
            'PARAMETER_VALUE' => $parameter_value,

            'L_DELETE_CONFIRMATION' => $user->lang['delete_confirmation'],
            'L_CONFIRM_TEXT'        => $confirm_text,
            'L_YES'                 => $user->lang['yes'],
            'L_NO'                  => $user->lang['no']
        ));

        $eqdkp->page_tail();

        exit;
    }
}

/**
* Form Validate Class
* Validates various elements of a form and types of data
* Available through admin extensions as fv
*/
class Form_Validate
{
    var $errors = array();          // Error messages       @var errors

    /**
    * Constructor
    *
    * Initiates the error list
    */
    function form_validate()
    {
        $this->_reset_error_list();
    }

    /**
    * Resets the error list
    *
    * @access private
    */
    function _reset_error_list()
    {
        $this->errors = array();
    }

    /**
    * Returns the array of errors
    *
    * @return array Errors
    */
    function get_errors()
    {
        return $this->errors;
    }

    /**
    * Checks if errors exist
    *
    * @return bool
    */
    function is_error()
    {
        if ( @sizeof($this->errors) > 0 )
        {
            return true;
        }

        return false;
    }

    /**
    * Returns a string with the appropriate error message
    *
    * @param $field Field to generate an error for
    * @return string Error string
    */
    function generate_error($field)
    {
        global $eqdkp_root_path;

        if ( $field != '' )
        {
            if ( !empty($this->errors[$field]) )
            {
                $error = '<br /><img src="'.$eqdkp_root_path . 'images/error.png" align="middle" alt="Error" />&nbsp;<b>' . $this->errors[$field] . '</b>';
                return $error;
            }
            else
            {
                return '';
            }
        }
        else
        {
            return '';
        }
    }

    /**
    * Returns the value of a variable in _POST or _GET
    *
    * @access private
    * @param $field_name Field name
    * @return mixed Value of the field_name
    */
    function _get_value($field_name)
    {
        global $in;

        // NOTE: This method doesn't know what default type to supply to Input::get()
        // but it's mostly OK because this class doesn't really do anything with
        // the user input, just validates that it matches a certain format.
        return $in->get($field_name, '');
    }

    // Begin validator methods
    // Note: The validation methods can accept arrays for the $field param
    // in this form: $field['fieldname'] = "Error message";
    // and the validation will be performed on each key/val pair.
    // If an array if used for validation, the method will always return true

    /**
    * Checks if a field is filled out
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_filled($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_filled($k, $v);
            }
            return true;
        }
        else
        {
            $value = $this->_get_value($field);
            if ( trim($value) == '' )
            {
                $this->errors[$field] = $message;
                return false;
            }
            return true;
        }
    }

    /**
    * Checks if a field is numeric
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_number($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_number($k, $v);
            }
            return true;
        }
        else
        {
            $value = str_replace(' ','', $this->_get_value($field));
            if ( !is_numeric($value) )
            {
                $this->errors[$field] = $message;
                return false;
            }
            return true;
        }
    }

    /**
    * Checks if a field is alphabetic
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_alpha($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_alpha($k, $v);
            }
            return true;
        }
        else
        {
            $value = $this->_get_value($field);
            if ( !preg_match("/^[[:alpha:][:space:]]+$/", $value) )
            {
                $this->errors[$field] = $message;
                return false;
            }
            return true;
        }
    }

    /**
    * Checks if a field is a valid hexadecimal color code (#FFFFFF)
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_hex_code($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_hex_code($k, $v);
            }
            return true;
        }
        else
        {
            $value = $this->_get_value($field);
            if ( !preg_match("/(#)?[0-9A-Fa-f]{6}$/", $value) )
            {
                $this->errors[$field] = $message;
                return false;
            }
            return true;
        }
    }

    /**
    * Checks if a field is within a minimum and maximum range
     * Note: Will NOT accept an array of fields
    *
    * @param $field Field name to check
    * @param $min Minimum value
    * @param $max Maximum value
    * @param $message Error message to insert
    * @return bool
    */
    function is_within_range($field, $min, $max, $message = '')
    {
        $value = $this->_get_value($field);
        if ( (!is_numeric($value)) || ($value < $min) || ($value > $max) )
        {
            $this->errors[$field] = $message;
            return false;
        }
        return true;
    }

    /**
    * Checks if a field has a valid e-mail address pattern
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_email_address($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_email_address($k, $v);
            }
            return true;
        }
        else
        {
            $value = $this->_get_value($field);
            if ( !preg_match("/^([a-zA-Z0-9])+([\.a-zA-Z0-9_-])*@([a-zA-Z0-9_-])+(\.[a-zA-Z0-9_-]+)+/", $value) )
            {
                $this->errors[$field] = $message;
                return false;
            }
            return true;
        }
    }

    /**
    *  Checks if a field has a valid IP address pattern
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function is_ip_address($field, $message = '')
    {
        if ( is_array($field) )
        {
            foreach ( $field as $k => $v )
            {
                $this->is_ip_address($k, $v);
            }
            return true;
        }
        else
        {
            $value = $this->_get_value($field);
            if ( !preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $value) )
            {
                $this->errors[$field] = $v;
                return false;
            }
            return true;
        }
    }

    /**
    * Checks if two fields match eachother exactly
    * Used to verify the password/confirm password fields
    *
    * @param $field Field name to check
    * @param $message Error message to insert
    * @return bool
    */
    function matching_passwords($field1, $field2, $message = '')
    {
        $value1 = $this->_get_value($field1);
        $value2 = $this->_get_value($field2);

        if ( sha1($value1) != sha1($value2) )
        {
            $this->errors[$field1] = $message;
            return false;
        }
        return true;
    }
}
?>
