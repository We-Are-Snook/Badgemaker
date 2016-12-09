<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"]))) . '/config.php'); // allows going up through a symlink
require_once($CFG->libdir . '/badgeslib.php');

require_login();

$PAGE->set_url('/local/badgemaker/about_open_badges.php');

if (empty($CFG->badges_allowcoursebadges) && $courseid != 0) {
    print_error('coursebadgesdisabled', 'badges');
}

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin'); // or 'incourse'
$PAGE->set_heading($SITE->fullname);
$title = get_string('about_open_badges', 'local_badgemaker');

$PAGE->set_title($title);
$output = $PAGE->get_renderer('core', 'badges');

echo $output->header();
echo $OUTPUT->heading($title);

// Begin about page.
echo 'At <a href="http://www.wearesnook.com">Snook</a> we love <a href="http://openbadges.org">Open Badges</a>.  Let\'s make Open Badges in Moodle better together.';


echo $output->footer();
