<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

 defined('MOODLE_INTERNAL') || die;

 if ($hassiteconfig) {
   $settings = new admin_settingpage('local_badgemaker', get_string('pluginname', 'local_badgemaker'));
   $ADMIN->add('localplugins', $settings);
   $settings->add(new admin_setting_configcheckbox('badgemaker_public_optional', get_string('public_optional_title', 'local_badgemaker'),
                       get_string('public_optional_desc', 'local_badgemaker'), 0));
   $settings->add(new admin_setting_configtext('badgemaker_public_passphrase', get_string('public_badge_title', 'local_badgemaker'),
                       get_string('public_badge_desc', 'local_badgemaker'), get_string('default_public_phrase', 'local_badgemaker'), PARAM_TEXT, 75));

 }
