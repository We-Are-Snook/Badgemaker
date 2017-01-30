<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))) . '/config.php'); // allows going up through a symlink
require_once($CFG->libdir . '/badgeslib.php');
require_once(dirname(dirname(__FILE__)).'/renderer.php');
require_once(dirname(__FILE__).'/lib.php');


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

$badgesPerPage = 10;

if ($clearsearch) {
    $search = '';
}

// drop down taken from course/management.php
$viewmode = optional_param('view', 'default', PARAM_ALPHA); // Can be one of default, course or site.


/* constrain params to only valid values */
if (!in_array($sortby, array('name', 'status', 'course', 'recipients'))) { // cant sort by recipients cause different query.
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


/* Rebuild a URL with only valid parameters */
/* This one will be modified or added to by any filtering actions */
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
if ($course = $DB->get_record('course', array('id' => $courseid))) {
    //$url->param('type', $type);
    $url->param('id', $course->id);
} else {
    //$url->param('type', $type);
}

// allows the URL to be accessed by the lib and renderer
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

// own css used for table...
$PAGE->requires->css('/local/badgemaker/style/badgemaker.css');

/* Site Badge Actions - that were posted to this page from a previous click on an action in the table */
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

//$output = $PAGE->get_renderer('badgemaker', 'local_badgemaker');

// Include JS files for backpack support.
badges_setup_backpack_js(); // MH must be before header is output

echo $OUTPUT->header();

// echo $OUTPUT->box($img, 'boxwidthwide boxaligncenter');
// echo '<p>';

// echo $OUTPUT->heading($title);

/* Begin All Badges */

$PAGE->set_context(context_system::instance());

// we will use these params if decide to persist search term between tabs.
$context = context_system::instance();
$params = array('page' => $page);
// we will use these params if decide to persist search term between tabs.
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

local_badgemaker_library_print_heading($baseurl, $title);

// if recipient search then we will sort in code later, so blank it for SQL
if ($sortby == 'recipients') {
  $recipientSort = true;
  $sortby = '';
} else {
  $recipientSort = false;
}

$records = local_badgemaker_get_badges($type, 0, $sortby, $sorthow, 0, 0, 0, $search);

$totalcount = count($records);

$badges             = new badge_management($records);
$badges->sort       = $sortby;
$badges->dir        = $sorthow;
$badges->page       = $page;
$badges->perpage    = $badgesPerPage;
$badges->totalcount = $totalcount;
$badges->search     = $search;

// if we are sorting by recipients then we sort manually here in code...
if ($recipientSort) {
  function badgeRecipientSortAsc($b1, $b2) {
    if ($b1->awards > $b2->awards) {
      return 1;
    }
    return -1;
  }
  function badgeRecipientSortDesc($b1, $b2) {
    if ($b1->awards > $b2->awards) {
      return -1;
    }
    return 1;
  }
  $tosort = $badges->badges;
  uasort($tosort, $sorthow == 'ASC' ? 'badgeRecipientSortAsc' : 'badgeRecipientSortDesc');
  $badges->badges = $tosort;
}

if (!empty($search)) {
    $realtotal = local_badgemaker_get_badges($type, 0, $sortby, $sorthow, 0, 0, 0);
    $realTotalCount = count($realtotal);
    $libhead = "$totalcount ".get_string('matching_badges_out_of', 'local_badgemaker')." $realTotalCount ".get_string('total_badges', 'local_badgemaker');
} else {
    $libhead = "$totalcount ".get_string('total_badges', 'local_badgemaker');
}

//$heading = $output->heading(get_string('badgestoearn', 'badges', $totalcount), 4);

//echo $output->helper_search_form($badges->search);

$viewmodes = array( // MH
    'combined' => get_string('course_and_site_badges', 'local_badgemaker'),
    'course' => get_string('course_badges', 'local_badgemaker'),
    'site' => get_string('site_badges', 'local_badgemaker')
);
$menu = local_badgemaker_view_mode_menu($viewmodes, $viewmode);
echo $output->library_heading($libhead, get_string('viewing'), $menu, $badges->search);

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

if (!$totalcount) {
    echo $output->notification(get_string('nobadges', 'badges'));
  }

echo $output->render($badges); // also outputs add new badge button.




// decided not to put this button on here because don't know course so would only be site badge
// also removed from renderer which is what adds it to table
//    if (has_capability('moodle/badges:createbadge', $PAGE->context)) {
//        echo $OUTPUT->single_button(new moodle_url('newbadge.php', array('type' => BADGE_TYPE_SITE, 'id' => $courseid)),
//            get_string('newbadge', 'badges'));
//    }
// }
echo $OUTPUT->footer();
