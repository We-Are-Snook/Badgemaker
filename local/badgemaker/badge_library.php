<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once(dirname(__FILE__).'/renderer.php');

$type       = 1;//required_param('type', PARAM_INT); // 1 = site, 2 = course.
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

//disable paging because we are showing two tables.
$badgesPerPage = PHP_INT_MAX; // BADGE_PERPAGE
$path = '/local/badgemaker/badge_library.php';

if (!in_array($sortby, array('name', 'status'))) {
    $sortby = 'name';
}

if ($sorthow != 'ASC' and $sorthow != 'DESC') {
    $sorthow = 'ASC';
}

if ($page < 0) {
    $page = 0;
}

require_login();

$err = '';
$urlparams = array('sort' => $sortby, 'dir' => $sorthow, 'page' => $page);

if ($course = $DB->get_record('course', array('id' => $courseid))) {
    $urlparams['type'] = $type;
    $urlparams['id'] = $course->id;
} else {
    $urlparams['type'] = $type;
}

$title = get_string('badge_library', 'local_badgemaker');

$returnurl = new moodle_url($path, $urlparams); // '/badges/index.php'
$PAGE->set_url($returnurl);

$PAGE->set_context(context_system::instance());
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');
$PAGE->set_heading($title);
navigation_node::override_active_url(new moodle_url($path, array('type' => BADGE_TYPE_SITE)), true); // '/badges/index.php



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
        echo $output->action_link($returnurl, get_string('cancel'));

        echo $output->footer();
        die();
    } else {
        require_sesskey();
        $archiveonly = ($archive != 0) ? true : false;
        $badge->delete($archiveonly);
        redirect($returnurl);
    }
}
/* My Badge Actions */
$output = new badgemaker_renderer($PAGE, '');

if ($clearsearch) {
    $search = '';
}
if ($hide) {
    require_sesskey();
    $DB->set_field('badge_issued', 'visible', 0, array('id' => $hide, 'userid' => $USER->id));
} else if ($show) {
    require_sesskey();
    $DB->set_field('badge_issued', 'visible', 1, array('id' => $show, 'userid' => $USER->id));
} else if ($download && $hash) {
    require_sesskey();
    $badge = new badge($download);
    $name = str_replace(' ', '_', $badge->name) . '.png';
    $filehash = badges_bake($hash, $download, $USER->id, true);
    $fs = get_file_storage();
    $file = $fs->get_file_by_hash($filehash);
    send_stored_file($file, 0, 0, true, array('filename' => $name));
}

if ($deactivate && has_capability('moodle/badges:configuredetails', $PAGE->context)) {
    require_sesskey();
    $badge = new badge($deactivate);
    if ($badge->is_locked()) {
        $badge->set_status(BADGE_STATUS_INACTIVE_LOCKED);
    } else {
        $badge->set_status(BADGE_STATUS_INACTIVE);
    }
    $msg = 'deactivatesuccess';
    $returnurl->param('msg', $msg);
    redirect($returnurl);
}

// Include JS files for backpack support.
badges_setup_backpack_js(); // MH must be before header is output

echo $OUTPUT->header();

$img = html_writer::empty_tag('img', array('src' => 'logo_web_800x600.png')); // align center does not work, right does though.
echo $OUTPUT->box($img, 'boxwidthwide boxaligncenter');
echo '<p>';
/* Begin My Badges modified from badges/mhbadges.php */

$context = context_user::instance($USER->id);
$PAGE->set_context(context_system::instance());
require_capability('moodle/badges:manageownbadges', $context);


$records = local_badgemaker_get_badges(0, 0, $sortby, $sorthow, $page, $badgesPerPage, $USER->id, $search);//badges_get_user_badges($USER->id, null, $page, $badgesPerPage, $search);
$totalcount = count($records);

$userbadges = new badge_user_collection($records, $USER->id);
$userbadges->sort = $sortby; //'dateissued';
$userbadges->dir = $sorthow; //'DESC';
$userbadges->page = $page;
$userbadges->perpage = $badgesPerPage;//BADGE_PERPAGE;
$userbadges->totalcount = $totalcount;
$userbadges->search = $search;

echo $output->render($userbadges);


/* Begin All Badges */

$PAGE->set_context(context_system::instance());
echo $output->heading('All badges available on this site');

echo $OUTPUT->box('', 'notifyproblem hide', 'check_connection');

//$totalcount = count(badges_get_badges($type, $courseid, '', '' , '', ''));
$records = local_badgemaker_get_badges(0, 0, $sortby, $sorthow, $page, $badgesPerPage, 0, $search);
$totalcount = count($records);

if ($totalcount) {
    echo $output->heading(get_string('badgestoearn', 'badges', $totalcount), 4);

    if ($course && $course->startdate > time()) {
        echo $OUTPUT->box(get_string('error:notifycoursedate', 'badges'), 'generalbox notifyproblem');
    }

    if ($err !== '') {
        echo $OUTPUT->notification($err, 'notifyproblem');
    }

    if ($msg !== '') {
        echo $OUTPUT->notification(get_string($msg, 'badges'), 'notifysuccess');
    }

    $badges             = new badge_management($records);
    $badges->sort       = $sortby;
    $badges->dir        = $sorthow;
    $badges->page       = $page;
    $badges->perpage    = $badgesPerPage;
    $badges->totalcount = $totalcount;

    echo $output->render($badges); // also outputs add new badge button.
} else {
    echo $output->notification(get_string('nobadges', 'badges'));

    if (has_capability('moodle/badges:createbadge', $PAGE->context)) {
        echo $OUTPUT->single_button(new moodle_url('newbadge.php', array('type' => $type, 'id' => $courseid)),
            get_string('newbadge', 'badges'));
    }
}

echo $OUTPUT->footer();
