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

$path =  '/local/badgemaker/library/my.php';

// $type       = 1;//required_param('type', PARAM_INT); // 1 = site, 2 = course.
// $courseid   = 0;//optional_param('id', 0, PARAM_INT);
$deactivate = optional_param('lock', 0, PARAM_INT);
$sortby     = optional_param('sort', '', PARAM_ALPHA);
$sorthow    = optional_param('dir', '', PARAM_ALPHA);
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

// see $url->param section below if changing default sort.
// current default sort is issued descending, or if course/name then ascending
if (!in_array($sortby, array('name', 'course', 'dateissued'))) {
    $sortby = 'dateissued';
}

if ($sorthow != 'ASC' && $sorthow != 'DESC') {
    if($sortby == 'dateissued') {
        $sorthow = 'DESC';
    }else{
        $sorthow = 'ASC';
    }
}

if ($page < 0) {
    $page = 0;
}

require_login();

$err = '';
$url = new moodle_url($path); // '/badges/index.php'

// we put in any params that are not the defaults
 if($sortby == 'dateissued') {
     // default is descneind so store if is opposite.
     if ($sorthow !== 'DESC') {
         $url->param('dir', $sorthow);
     }
 }
 else {
     // if the sort isnt by date issued then the default is ascending
     $url->param('sort', $sortby);
     if ($sorthow !== 'ASC') {
         $url->param('dir', $sorthow);
     }
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

/* Begin My Badges modified from badges/mhbadges.php */

$context = context_user::instance($USER->id);
$PAGE->set_context(context_system::instance());
require_capability('moodle/badges:manageownbadges', $context);

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
local_badgemaker_library_print_heading($baseurl, $title, 'localbadgesh', 'badges');



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

if ($withoutSearchCount < 0) {
    $bes = $totalcount . ' ' . get_string('badges_earned_heading', 'local_badgemaker');
    $subheading = $output->heading($bes , 2, 'activatebadge');
} else {
    $pageBadges = array_slice($userbadges->badges, $userbadges->page * $userbadges->perpage, $userbadges->perpage);
    $subheading = $output->heading(count($pageBadges) . ' ' . get_string('matching_badges_out_of', 'local_badgemaker') . ' ' . $withoutSearchCount . ' ', 2, 'activatebadge');
}

$menu = local_badgemaker_library_sort_menu($sortby, $sorthow);
echo $output->library_heading($subheading, 'Sort by: ', $menu, $search);

echo $output->badgemaker_render_badge_user_collection($userbadges, $withoutSearchCount);

echo $OUTPUT->footer();
