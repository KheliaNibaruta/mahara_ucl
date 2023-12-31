<?php
/**
 * Show group info in a preview window.
 *
 * @package    mahara
 * @subpackage core
 * @author     Catalyst IT Limited <mahara@catalyst.net.nz>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

define('INTERNAL', 1);
define('JSON', 1);
require(dirname(dirname(__FILE__)) . '/init.php');
require_once(get_config('libroot') . 'group.php');

$id = param_integer('id');
$group = get_group_by_id($id, true);

$group = group_get_groupinfo_data($group);

$smarty = smarty_core();
$smarty->assign('group', $group);
$html = $smarty->fetch('group/groupdata.tpl');

json_reply(false, array(
    'message' => null,
    'html' => $html,
));
