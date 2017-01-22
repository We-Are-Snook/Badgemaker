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

$path =  '/local/badgemaker/library/my.php';

// $type       = 1;//required_param('type', PARAM_INT); // 1 = site, 2 = course.
// $courseid   = 0;//optional_param('id', 0, PARAM_INT);
$deactivate = optional_param('lock', 0, PARAM_INT);
$sortby     = optional_param('sort', 'name', PARAM_ALPHA);
$sorthow    = optional_param('dir', 'ASC', PARAM_ALPHA);
//$confirm    = optional_param('confirm', false, PARAM_BOOL);
//$delete     = optional_param('delete', 0, PARAM_INT);
//$archive    = optional_param('archive', 0, PARAM_INT);
//$msg        = optional_param('msg', '', PARAM_TEXT);

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

if (!in_array($sortby, array('name', 'course', 'date'))) {
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

$PAGE->set_url($url);

$PAGE->set_context(context_system::instance());
$title = get_string('badge_library', 'local_badgemaker') . ': ' . get_string('my_badges', 'local_badgemaker');
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');
// $PAGE->set_heading($title);

$PAGE->requires->js('/badges/backpack.js');
$PAGE->requires->js_init_call('check_site_access', null, false);

// We need our own CSS for the badge list now...
$PAGE->requires->css('/local/badgemaker/style/badgemaker.css');

/* My Badge Actions */
$output = new badgemaker_renderer($PAGE, '');

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

// Include JS files for backpack support.
badges_setup_backpack_js(); // MH must be before header is output

echo $OUTPUT->header();

// Combine image and title into a single heading...
$img = html_writer::empty_tag('img', array('src' => '../BM_icon.png', 'width' => '10%')); // align center does not work, right does though.
echo $OUTPUT->heading_with_help($img.$title, 'localbadgesh', 'badges');

/* Begin My Badges modified from badges/mhbadges.php */

$context = context_user::instance($USER->id);
$PAGE->set_context(context_system::instance());
require_capability('moodle/badges:manageownbadges', $context);


// echo $OUTPUT->heading_with_help($title, 'localbadgesh', 'badges');

// we will use these params if decide to persist search term between tabs.
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

if ($sortby == 'date') {
  $sortby = 'dateissued';
}
// echo "Sorting by $sortby in dir $sorthow";
$records = local_badgemaker_get_badges(0, 0, $sortby, $sorthow, 0, 0, $USER->id, $search);

if (empty($search)) {
  // echo "EMPTY SEARCH";
  $withoutSearchCount = -1;
} else {
  // echo "SEARCH ACTIVE";
  $withoutSearchCount = count(local_badgemaker_get_badges(0, 0, $sortby, $sorthow, 0, 0, $USER->id));
}
$totalcount = count($records);

$userbadges = new badge_user_collection($records, $USER->id);
$userbadges->sort = $sortby; //'dateissued';
$userbadges->dir = $sorthow; //'DESC';
$userbadges->page = $page;
$userbadges->perpage = $badgesPerPage;
$userbadges->totalcount = $totalcount;
$userbadges->search = $search;

echo $output->badgemaker_render_badge_user_collection($userbadges, $withoutSearchCount);

echo $OUTPUT->footer();
