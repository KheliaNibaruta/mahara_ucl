<?php
/**
 *
 * @package    mahara
 * @subpackage notification
 * @author     Catalyst IT Limited <mahara@catalyst.net.nz>
 * @license    https://www.gnu.org/licenses/gpl-3.0.html GNU GPL version 3 or later
 * @copyright  For copyright information on Mahara, please see the README file distributed with this software.
 *
 */

defined('INTERNAL') || die();

/**
 * Helper interface to hold IPluginNotification's abstract static methods
 */
interface IPluginNotification {
    public static function notify_user($user, $data);
}

abstract class PluginNotification extends Plugin implements IPluginNotification {

    public static function get_plugintype_name() {
        return 'notification';
    }

    public static function can_be_disabled() {
        return false;
    }
}
