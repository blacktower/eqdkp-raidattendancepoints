<?php
/**
 * Project:     EQdkp - Open Source Points System
 * License:     http://eqdkp.com/?p=license
 * -----------------------------------------------------------------------
 * File:        listnews.php
 * Began:       Fri Dec 27 2002
 * Date:        $Date: 2008-05-23 16:44:49 -0700 (Fri, 23 May 2008) $
 * -----------------------------------------------------------------------
 * @author      $Author: rspeicher $
 * @copyright   2002-2008 The EQdkp Project Team
 * @link        http://eqdkp.com/
 * @package     eqdkp
 * @version     $Rev: 555 $
 */
 
define('EQDKP_INC', true);
define('IN_ADMIN', true);
$eqdkp_root_path = './../';
require_once($eqdkp_root_path . 'common.php');

$user->check_auth('a_news_');

$sort_order = array(
    0 => array('news_date desc', 'news_date'),
    1 => array('news_headline', 'news_headline desc'),
    2 => array('user_name', 'user_name desc')
);

$current_order = switch_order($sort_order);

$total_news = $db->query_first("SELECT COUNT(*) FROM __news");
$start = $in->get('start', 0);

$sql = "SELECT n.news_id, n.news_date, n.news_headline, n.news_message, u.user_name
        FROM __news AS n, __users AS u
        WHERE (n.`user_id` = u.`user_id`)
        ORDER BY {$current_order['sql']}
        LIMIT {$start},50";

if ( !($result = $db->query($sql)) )
{
    message_die('Could not obtain news information', '', __FILE__, __LINE__, $sql);
}
while ( $news = $db->fetch_record($result) )
{
    $tpl->assign_block_vars('news_row', array(
        'ROW_CLASS'   => $eqdkp->switch_row_class(),
        'DATE'        => date($user->style['date_time'], $news['news_date']),
        'USERNAME'    => sanitize($news['user_name']),
        'U_VIEW_NEWS' => edit_news_path($news['news_id']),
        'HEADLINE'    => sanitize($news['news_headline'])
    ));
}

$tpl->assign_vars(array(
    'L_DATE' => $user->lang['date'],
    'L_USERNAME' => $user->lang['username'],
    'L_HEADLINE' => $user->lang['headline'],
    
    'O_DATE' => $current_order['uri'][0],
    'O_USERNAME' => $current_order['uri'][2],
    'O_HEADLINE' => $current_order['uri'][1],
    
    'U_LIST_NEWS' => news_path(true),
    
    'START' => $start,
    'LISTNEWS_FOOTCOUNT' => sprintf($user->lang['listnews_footcount'], $total_news, 50),
    'NEWS_PAGINATION' => generate_pagination(news_path(true) . path_params(URI_ORDER, $current_order['uri']['current']), $total_news, 50, $start))
);

$eqdkp->set_vars(array(
    'page_title'    => page_title($user->lang['listnews_title']),
    'template_file' => 'admin/listnews.html',
    'display'       => true
));