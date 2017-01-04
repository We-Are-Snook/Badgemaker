<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))) . '/config.php'); // allows going up through a symlink
require_once($CFG->libdir . '/badgeslib.php');
require_once(dirname(dirname(__FILE__)).'/renderer.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');


$path = '/local/badgemaker/library/all.php';

//$type       = 1;//required_param('type', PARAM_INT); // 1 = site, 2 = course. Now using $viewmode
$courseid   = 0;//optional_param('id', 0, PARAM_INT);
$page       = 0;//optional_param('page', 0, PARAM_INT);
$deactivate = optional_param('lock', 0, PARAM_INT);
$sortby     = optional_param('sort', 'name', PARAM_ALPHA);
$sorthow    = optional_param('dir', 'ASC', PARAM_ALPHA);
$confirm    = optional_param('confirm', false, PARAM_BOOL);
$delete     = optional_param('delete', 0, PARAM_INT);
$archive    = optional_param('archive', 0, PARAM_INT);
$msg        = optional_param('msg', '', PARAM_TEXT);

// from badge/mybadges.php
$page        = optional_param('page', 0, PARAM_INT);
$search      = optional_param('search', '', PARAM_CLEAN);
$clearsearch = optional_param('clearsearch', '', PARAM_TEXT);
$download    = optional_param('download', 0, PARAM_INT);
$hash        = optional_param('hash', '', PARAM_ALPHANUM);
//$downloadall = optional_param('downloadall', false, PARAM_BOOL); // MH goes to the real page.
$hide        = optional_param('hide', 0, PARAM_INT);
$show        = optional_param('show', 0, PARAM_INT);

$badgesPerPage = 10; // BADGE_PERPAGE; // Where did this constant come from?  I don't see it.

// drop down taken from course/management.php
$viewmode = optional_param('view', 'default', PARAM_ALPHA); // Can be one of default, course or site.

if (!in_array($sortby, array('name', 'status'))) {
    $sortby = 'name';
}

if ($sorthow != 'ASC' and $sorthow != 'DESC') {
    $sorthow = 'ASC';
}

if ($page < 0) {
    $page = 0;
}

if (!in_array($viewmode, array('default', 'combined', 'course', 'site'))) {
    $viewmode = 'default';
}

require_login();

$err = '';

$url = new moodle_url($path); // '/badges/index.php'

if($sorthow !== 'ASC'){
    $url->param('dir', $sorthow);
}
if($sortby !== 'name'){
    $url->param('sort', $sortby);
}
if($search !== ''){
    $url->param('search', $search);
}
if($viewmode !== 'default'){
    $url->param('view', $viewmode);
}
if ($clearsearch) {
    $search = '';
}
if ($course = $DB->get_record('course', array('id' => $courseid))) {
    //$url->param('type', $type);
    $url->param('id', $course->id);
} else {
    //$url->param('type', $type);
}

$PAGE->set_url($url);

$PAGE->set_context(context_system::instance());
$title = get_string('badge_library', 'local_badgemaker') . ': ' . get_string('all_badges', 'local_badgemaker');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');
// $PAGE->set_heading($title);
//navigation_node::override_active_url(new moodle_url($path, array('type' => BADGE_TYPE_SITE)), true); // '/badges/index.php
navigation_node::override_active_url(local_badgemaker_libraryPageURL());



$PAGE->requires->js('/badges/backpack.js');
$PAGE->requires->js_init_call('check_site_access', null, false);

/* Site Badge Actions */
$output = $PAGE->get_renderer('core', 'badges');
if (($delete || $archive) && has_capability('moodle/badges:deletebadge', $PAGE->context)) {
    $badgeid = ($archive != 0) ? $archive : $delete;
    $badge = new badge($badgeid);
    if (!$confirm) {
        echo $output->header();
        // Archive this badge?
        echo $output->heading(get_string('archivebadge', 'badges', $badge->name));
        $archivebutton = $output->single_button(
            new moodle_url($PAGE->url, array('archive' => $badge->id, 'confirm' => 1)),
            get_string('archiveconfirm', 'badges'));
        echo $output->box(get_string('archivehelp', 'badges') . $archivebutton, 'generalbox');

        // Delete this badge?
        echo $output->heading(get_string('delbadge', 'badges', $badge->name));
        $deletebutton = $output->single_button(
            new moodle_url($PAGE->url, array('delete' => $badge->id, 'confirm' => 1)),
            get_string('delconfirm', 'badges'));
        echo $output->box(get_string('deletehelp', 'badges') . $deletebutton, 'generalbox');

        // Go back.
        echo $output->action_link($url, get_string('cancel'));

        echo $output->footer();
        die();
    } else {
        require_sesskey();
        $archiveonly = ($archive != 0) ? true : false;
        $badge->delete($archiveonly);
        redirect($url);
    }
}


if($viewmode === 'default' || $viewmode === 'combined'){
    $type = 0;
}
else if($viewmode === 'site') {
    $type = BADGE_TYPE_SITE;
}
else if($viewmode === 'course'){
    $type = BADGE_TYPE_COURSE;
}


$output = new badgemaker_renderer($PAGE, '');

// Include JS files for backpack support.
badges_setup_backpack_js(); // MH must be before header is output

echo $OUTPUT->header();

// Combine image and title into a single heading...
$img = html_writer::empty_tag('img', array('src' => '../BM_icon.png', 'width' => '10%')); // align center does not work, right does though.
echo $OUTPUT->heading($img.$title);
// $img = html_writer::empty_tag('img', array('src' => '../logo_web_800x600.png')); // align center does not work, right does though.
// echo $OUTPUT->box($img, 'boxwidthwide boxaligncenter');
// echo '<p>';

// echo $OUTPUT->heading($title);

/* Begin All Badges */

$PAGE->set_context(context_system::instance());

// we will use these params if decide to persist search term between tabs.
$context = context_system::instance();
$params = array('page' => $page);
//if ($contextid) {
//    $params['contextid'] = $contextid;
//}
//if ($searchquery) {
//    $params['search'] = $searchquery;
//}
//if ($showall) {
//    $params['showall'] = true;
//}
$baseurl = new moodle_url($path, $params);

if ($editcontrols = local_badgemaker_tabs($context, $baseurl)) {
    echo $OUTPUT->render($editcontrols);
}

//$totalcount = count(badges_get_badges($type, $courseid, '', '' , '', ''));
$records = local_badgemaker_get_badges($type, 0, $sortby, $sorthow, 0, 0, 0, $search);
$totalcount = count($records);

$badges             = new badge_management($records);
$badges->sort       = $sortby;
$badges->dir        = $sorthow;
$badges->page       = $page;
$badges->perpage    = $badgesPerPage;
$badges->totalcount = $totalcount;
$badges->search     = $search;

    //$heading = $output->heading(get_string('badgestoearn', 'badges', $totalcount), 4);

    echo $output->library_heading($totalcount . ' badges', $viewmode, null, $badges);
    //echo $output->heading('All badges available on this site');
    // echo $OUTPUT->box('', 'notifyproblem hide', 'check_connection'); // MB... pretty sure this is only needed if there is a backpack connect link.

    if ($course && $course->startdate > time()) {
        echo $OUTPUT->box(get_string('error:notifycoursedate', 'badges'), 'generalbox notifyproblem');
    }

    if ($err !== '') {
        echo $OUTPUT->notification($err, 'notifyproblem');
    }

    if ($msg !== '') {
        echo $OUTPUT->notification(get_string($msg, 'badges'), 'notifysuccess');
    }



    // die("There are $totalcount badges.  There should be $badgesPerPage per page, and we should now get page $page");

    echo $output->render($badges); // also outputs add new badge button.


if (!$totalcount) {
    echo $output->notification(get_string('nobadges', 'badges'));
  }

// decided not to put this button on here because don't know course so would only be site badge
// also removed from renderer which is what adds it to table
//    if (has_capability('moodle/badges:createbadge', $PAGE->context)) {
//        echo $OUTPUT->single_button(new moodle_url('newbadge.php', array('type' => BADGE_TYPE_SITE, 'id' => $courseid)),
//            get_string('newbadge', 'badges'));
//    }
// }
echo $OUTPUT->footer();
