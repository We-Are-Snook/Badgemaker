<?php

/**
 * @package    Badgemaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

class block_badgemaker_awarded_in_my_courses_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $numberofbadges = array('0' => get_string('all'));
        for ($i = 1; $i <= 20; $i++) {
            $numberofbadges[$i] = $i;
        }

        $mform->addElement('select', 'config_numberofbadges', get_string('numbadgestodisplay', 'block_badgemaker_awarded_in_my_courses'), $numberofbadges);
        $mform->setDefault('config_numberofbadges', 3);
    }
}
