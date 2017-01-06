<?php

/**
 * @package    Badgemaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

defined('MOODLE_INTERNAL') || die;
// block_badgemaker_library_button

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('block_badgemaker_library_button_showlogo', get_string('showlogo', 'block_badgemaker_library_button'),
                       get_string('showlogoconfig', 'block_badgemaker_library_button'), 0));
    $settings->add(new admin_setting_configtext('block_badgemaker_library_button_before',
    get_string('before', 'block_badgemaker_library_button'),
    get_string('beforeconfig', 'block_badgemaker_library_button'),
    ""
  ));
    $settings->add(new admin_setting_configtext('block_badgemaker_library_button_after',
    get_string('after', 'block_badgemaker_library_button'),
    get_string('afterconfig', 'block_badgemaker_library_button'),
    ""
  ));
}
