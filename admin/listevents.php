<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        listevents.php
 * Began:       Fri Dec 27 2002
 * Date:        $Date: 2008-03-08 07:29:17 -0800 (Sat, 08 Mar 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 516 $
 */
 
define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
require_once($eqdkp_root_path . 'common.php');

$user->check_auth('a_event_');

$sort_order = array(
    0 => array('event_name', 'event_name desc'),
    1 => array('event_value desc', 'event_value')
);
 
$current_order = switch_order($sort_order);

$total_events = $db->query_first("SELECT COUNT(*) FROM __events");

$start = $in->get('start', 0);

$sql = "SELECT event_id, event_name, event_value 
        FROM __events
        ORDER BY {$current_order['sql']}
        LIMIT {$start},{$user->data['user_elimit']}";
        
if ( !($events_result = $db->query($sql)) )
{
    message_die('Could not obtain event information', '', __FILE__, __LINE__, $sql);
}
while ( $event = $db->fetch_record($events_result) )
{
    $tpl->assign_block_vars('events_row', array(
        'ROW_CLASS' => $eqdkp->switch_row_class(),
        'U_VIEW_EVENT' => edit_event_path($event['event_id']),
        'NAME'         => sanitize($event['event_name']),
        'VALUE'        => number_format($event['event_value'], 2)
    ));
}
$db->free_result($events_result);

$tpl->assign_vars(array(
    'L_NAME' => $user->lang['name'],
    'L_VALUE' => $user->lang['value'],
    
    'O_NAME' => $current_order['uri'][0],
    'O_VALUE' => $current_order['uri'][1],
    
    'U_LIST_EVENTS' => event_path() . '&amp;',
    
    'START' => $start,    
    'LISTEVENTS_FOOTCOUNT' => sprintf($user->lang['listevents_footcount'], $total_events, $user->data['user_elimit']),
    'EVENT_PAGINATION'     => generate_pagination(event_path() . path_params(URI_ORDER, $current_order['uri']['current']), 
                                $total_events, $user->data['user_elimit'], $start)
));

$eqdkp->set_vars(array(
    'page_title'    => page_title($user->lang['listevents_title']),
    'template_file' => 'listevents.html',
    'display'       => true
));