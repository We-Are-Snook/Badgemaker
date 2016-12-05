<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

class block_badgemaker_newest_created_badges_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $numberofbadges = array('0' => get_string('all'));
        for ($i = 1; $i <= 20; $i++) {
            $numberofbadges[$i] = $i;
        }

        $mform->addElement('select', 'config_numberofnewestcreatedbadges', get_string('numbadgestodisplay', 'block_badgemaker_newest_created_badges'), $numberofbadges);
        $mform->setDefault('config_numberofnewestcreatedbadges', 10);

        $badgeTypes = array(0 => get_string('both_badge_types', 'block_badgemaker_newest_created_badges'), BADGE_TYPE_SITE => get_string('site_badge_types', 'block_badgemaker_newest_created_badges'), BADGE_TYPE_COURSE => get_string('course_badge_types', 'block_badgemaker_newest_created_badges'));
        $mform->addElement('select', 'config_typeofnewestcreatedbadges', get_string('typeofbadgetodisplay', 'block_badgemaker_newest_created_badges'), $badgeTypes);
        $mform->setDefault('config_typeofnewestcreatedbadges', 0);
    }
}
