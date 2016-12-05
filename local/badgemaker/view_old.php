<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/badgeslib.php');

// URL Params

$sortby = optional_param('sort', 'name', PARAM_ALPHA);
if (!in_array($sortby, array('name', 'dateissued'))) {
    $sortby = 'name';
}
$sorthow = optional_param('dir', 'DESC', PARAM_ALPHA);
if ($sorthow != 'ASC' && $sorthow != 'DESC') {
    $sorthow = 'ASC';
}
//$type       = required_param('type', PARAM_INT);
//$courseid   = optional_param('id', 0, PARAM_INT);
$page       = 0;//optional_param('page', 0, PARAM_INT); // paging disabled because we have multiple tables now.
$editing = optional_param('edit', -1, PARAM_BOOL);

require_login();

//$hassiteconfig = has_capability('moodle/site:config', context_system::instance());
//if ($hassiteconfig && moodle_needs_upgrading()) {
//    redirect(new moodle_url('/admin/index.php'));
//}

$strmymoodle = get_string('myhome');

//if (isguestuser()) {  // Force them to see system default, no editing allowed
//    // If guests are not allowed my moodle, send them to front page.
//    if (empty($CFG->allowguestmymoodle)) {
//        redirect(new moodle_url('/', array('redirect' => 0)));
//    }

//$userid = null;
//$USER->editing = $edit = 0;  // Just in case
$context = context_system::instance();
//$PAGE->set_blocks_editing_capability('moodle/my:configsyspages');  // unlikely :)
$header = "$SITE->shortname: $strmymoodle (GUEST)";
//$pagetitle = $header;
$pagetitle = get_string('badge_library', 'local_badgemaker');

//} else {        // We are trying to view or edit our own My Moodle page
//    $userid = $USER->id;  // Owner of the page
//    $context = context_user::instance($USER->id);
//    $PAGE->set_blocks_editing_capability('moodle/my:manageblocks');
//    $header = fullname($USER);
//    $pagetitle = $strmymoodle;
//}

// Get the My Moodle page info.  Should always return something unless the database is broken.
//if (!$currentpage = my_get_page($userid, MY_PAGE_PRIVATE)) {
//    print_error('mymoodlesetup');
//}

// Start setting up the page
$params = array();
$PAGE->set_context($context);
//$PAGE->set_url('/my/index.php', $params);
$PAGE->set_url('/local/badgemaker/badge_library.php', array('sort' => $sortby, 'dir' => $sorthow));
$PAGE->set_pagelayout('standard'); // mydashboard or 'incourse'
$PAGE->set_pagetype('mod');
$PAGE->set_blocks_editing_capability('moodle/site:manageblocks');

//$PAGE->blocks->add_region('content');
//$PAGE->set_subpage($currentpage->id);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($SITE->fullname); // $header

if (!isguestuser()) {   // Skip default home page for guests
    if (get_home_page() != HOMEPAGE_MY) {
        if (optional_param('setdefaulthome', false, PARAM_BOOL)) {
            set_user_preference('user_home_page_preference', HOMEPAGE_MY);
        } else if (!empty($CFG->defaulthomepage) && $CFG->defaulthomepage == HOMEPAGE_USER) {
            $frontpagenode = $PAGE->settingsnav->add(get_string('frontpagesettings'), null, navigation_node::TYPE_SETTING, null);
            $frontpagenode->force_open();
            $frontpagenode->add(get_string('makethismyhome'), new moodle_url('/my/', array('setdefaulthome' => true)),
                navigation_node::TYPE_SETTING);
        }
    }
}

// Toggle the editing state and switches
/*
if (empty($CFG->forcedefaultmymoodle) && $PAGE->user_allowed_editing()) {
    if ($reset !== null) {
        if (!is_null($userid)) {
            require_sesskey();
            if (!$currentpage = my_reset_page($userid, MY_PAGE_PRIVATE)) {
                print_error('reseterror', 'my');
            }
            redirect(new moodle_url('/my'));
        }
    } else if ($edit !== null) {             // Editing state was specified
        $USER->editing = $edit;       // Change editing state
    } else {                          // Editing state is in session
        if ($currentpage->userid) {   // It's a page we can edit, so load from session
            if (!empty($USER->editing)) {
                $edit = 1;
            } else {
                $edit = 0;
            }
        } else {
            // For the page to display properly with the user context header the page blocks need to
            // be copied over to the user context.
            if (!$currentpage = my_copy_page($USER->id, MY_PAGE_PRIVATE)) {
                print_error('mymoodlesetup');
            }
            $context = context_user::instance($USER->id);
            $PAGE->set_context($context);
            $PAGE->set_subpage($currentpage->id);
            // It's a system page and they are not allowed to edit system pages
            $USER->editing = $edit = 0;          // Disable editing completely, just to be safe
        }
    }

    // Add button for editing page
    $params = array('edit' => !$edit);

    $resetbutton = '';
    $resetstring = get_string('resetpage', 'my');
    $reseturl = new moodle_url("$CFG->wwwroot/my/index.php", array('edit' => 1, 'reset' => 1));

    if (!$currentpage->userid) {
        // viewing a system page -- let the user customise it
        $editstring = get_string('updatemymoodleon');
        $params['edit'] = 1;
    } else if (empty($edit)) {
        $editstring = get_string('updatemymoodleon');
    } else {
        $editstring = get_string('updatemymoodleoff');
        $resetbutton = $OUTPUT->single_button($reseturl, $resetstring);
    }

    $url = new moodle_url("$CFG->wwwroot/my/index.php", $params);
    $button = $OUTPUT->single_button($url, $editstring);
    $PAGE->set_button($resetbutton . $button);

} else {
    $USER->editing = $edit = 0;
}
*/

// Normal case.
if ($PAGE->user_allowed_editing() && $editing != -1) {
    $USER->editing = $editing;
}

if ($PAGE->user_allowed_editing()) {
    if ($PAGE->user_is_editing()) {
        $caption = get_string('blockseditoff');
        $url = new moodle_url($PAGE->url, array('edit'=>'0'));
    } else {
        $caption = get_string('blocksediton');
        $url = new moodle_url($PAGE->url, array('edit'=>'1'));
    }
    $PAGE->set_button($OUTPUT->single_button($url, $caption, 'get'));
}

/*

if (empty($CFG->enablebadges)) {
    print_error('badgesdisabled', 'badges');
}

if (empty($CFG->badges_allowcoursebadges) && $courseid != 0) {
    print_error('coursebadgesdisabled', 'badges');
}



$PAGE->set_context(context_system::instance());


//moodle/site:manageblocks



require_capability('moodle/badges:viewbadges', $PAGE->context);

$PAGE->set_title($title);

*/
$output = $PAGE->get_renderer('core', 'badges');

echo $output->header();

/* Page content begin */

echo $OUTPUT->heading('Sitewide'. ' ' . get_string('coursebadges', 'badges'));

$totalcount = count(badges_get_badges(BADGE_TYPE_SITE, 0, '', '', '', '', $USER->id));
$records = badges_get_badges(BADGE_TYPE_SITE, 0, $sortby, $sorthow, $page, BADGE_PERPAGE, $USER->id);

if ($totalcount) {
    echo $output->heading(get_string('badgestoearn', 'badges', $totalcount), 4);

    $badges = new badge_collection($records);
    $badges->sort = $sortby;
    $badges->dir = $sorthow;
    $badges->page = $page;
    $badges->perpage = BADGE_PERPAGE;
    $badges->totalcount = $totalcount;

    echo $output->render($badges);
} else {
    echo $output->notification(get_string('nobadges', 'badges'));
}

$courses = get_courses('all', 'c.sortorder ASC', 'c.id, c.shortname, c.fullname, c.startdate');
foreach($courses as $course) {

    $courseid = $course->id;

    // skip the site name which is course 1.
    if($courseid == 1){
        continue;
    }
    //$course = $DB->get_record('course', array('id' => $courseid));

    //require_login($course);
    $coursename = format_string($course->fullname, true, array('context' => context_course::instance($course->id)));
    $title = $coursename . ' ' . get_string('coursebadges', 'badges');
    $PAGE->set_context(context_course::instance($course->id)); // required for badge image to appear
    echo $OUTPUT->heading($title);

    $totalcount = count(badges_get_badges(BADGE_TYPE_COURSE, $courseid, '', '', '', '', $USER->id));
    $records = badges_get_badges(BADGE_TYPE_COURSE, $courseid, $sortby, $sorthow, $page, BADGE_PERPAGE, $USER->id);

    if ($totalcount) {
        echo $output->heading(get_string('badgestoearn', 'badges', $totalcount), 4);

        if ($course && $course->startdate > time()) {
            echo $OUTPUT->box(get_string('error:notifycoursedate', 'badges'), 'generalbox notifyproblem');
        }

        $badges = new badge_collection($records);
        $badges->sort = $sortby;
        $badges->dir = $sorthow;
        $badges->page = $page;
        $badges->perpage = BADGE_PERPAGE;
        $badges->totalcount = $totalcount;

        echo $output->render($badges);
    } else {
        echo $output->notification(get_string('nobadges', 'badges'));
    }
}

/* Page content end */

echo $output->footer();
