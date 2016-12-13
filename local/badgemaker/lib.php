<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

 require_once($CFG->libdir . '/badgeslib.php');

 function local_badgemaker_autolink($str, $attributes=array()) {
   $str = str_replace("http://www","www",$str);
   $str = str_replace("https://www","www",$str);

   $attrs = '';
   foreach ($attributes as $attribute => $value) {
     $attrs .= " {$attribute}=\"{$value}\"";
   }
   $str = ' ' . $str;
   $str = preg_replace('`([^"=\'>])((http|https|ftp)://[^\s<]+[^\s<\.)])`i', '$1<a href="$2"'.$attrs.'>$2</a>', $str);
   $str = preg_replace('`([^"=\'>])((www).[^\s<]+[^\s<\.)])`i', '$1<a href="http://$2"'.$attrs.'>$2</a>', $str);
   $str = substr($str, 1);
   return $str;
 }

 function local_badgemaker_assignsubmission_file_pluginfile_user_has_badge_for_course_module_id($userid, $courseid, $cmid) {
   // get all the badges that the user has been awarded for this course
   $badges = badges_get_user_badges($userid, $courseid, 0, 0, '', true);

   // check against each badge we have found
   foreach($badges as $badge) {
     // reload the badge object as the badges_get_user_badges method doesn't return the right class of object
     $badge = new badge($badge->id);

     // check the badge hasn't expired yet
     if ($badge->can_expire()) {
       $expiry = $badge->calculate_expiry();
       $diff = time() - $expiry;
       if ($diff > 0) {
         // the badge has expire so ignore this one and skip to the next badge
         continue;
       }
     }

     // check that the badge is one that is permitted to show public links
     if (!local_badgemaker_badge_has_public_setting($badge)) {
       continue;
     }

     // get all the criteria for the badge
     $criteria = $badge->get_criteria();

     // loop all badge criteria
     foreach($criteria as $ccrit) {
       // check the activity criteria
       if (get_class($ccrit) == 'award_criteria_activity') {
         $activityCriteria = $ccrit;
         $activityParams = $activityCriteria->params;
         $badgeModuleId = null;
         // check each paramater
         foreach($activityParams as $ap) {
           // check if it is a module parameter
           if (!empty($ap['module'])) {
             $badgeModuleId = $ap['module'];
             // if the module is THIS module then the file is public
             if ($badgeModuleId == $cmid) {
               return true;
             }
           }
         }
       }
     }
   }
   return false;
 }

 /**
  * Returns true if the user has an ACTIVE badge that has a requirement that the
  * given course module be completed
  *
  * @param int $userid the ID of the user
  * @param int $courseid the ID of the course
  * @param int $cmid the ID of the course module
  * @return bool true if the user has an active badge with a requirement on the given cmid
  */
 function local_badgemaker_assignsubmission_file_pluginfile_is_badge_upload($userid, $course, $cm) {
   // get all the badges that the user has been awarded for this course
   $badges = badges_get_user_badges($userid, $course->id, 0, 0, '', true);

   // check against each badge we have found
   foreach($badges as $badge) {
     // reload the badge object as the badges_get_user_badges method doesn't return the right class of object
     $badge = new badge($badge->id);

     // check the badge hasn't expired yet
     if ($badge->can_expire()) {
       $expiry = $badge->calculate_expiry();
       $diff = time() - $expiry;
       if ($diff > 0) {
         // the badge has expire so ignore this one and skip to the next badge
         continue;
       }
     }

     if (!local_badgemaker_badge_has_public_setting($badge)) {
       continue;
     }

     // get all the criteria for the badge
     $criteria = $badge->get_criteria();

     // loop all badge criteria
     foreach($criteria as $ccrit) {
       // check the activity criteria
       if (get_class($ccrit) == 'award_criteria_activity') {
         $activityCriteria = $ccrit;
         $activityParams = $activityCriteria->params;
         $badgeModuleId = null;
         // check each paramater
         foreach($activityParams as $ap) {
           // check if it is a module parameter
           if (!empty($ap['module'])) {
             $badgeModuleId = $ap['module'];
             // if the module is THIS module then the file is public
             if ($badgeModuleId == $cm->id) {
               return true;
             }
           }
         }
       }
     }
   }
   return false;
 }

 /**
  * Returns true if the user has an ACTIVE badge in which the given file fullpath is a
  * requirement.
  *
  * @param int $userid the ID of the user
  * @param string $fullpath the fullpath of the local file
  * @return bool true if the user has an active badge with a requirement that uses the fullpath
  */
 function local_badgemaker_assignsubmission_file_pluginfile_is_used_by_badge_onlinetext($userid, $fullpath) {
   global $DB;
   // This is all the onlinetexts with course modules for this USER
   //$onlinetexts = $DB->get_records_sql('SELECT mdl_course_modules.id AS coursemoduleid, mdl_course_modules.course AS courseid, mdl_assignsubmission_onlinetext.onlinetext AS ontext FROM  mdl_assign_submission INNER JOIN mdl_assignsubmission_onlinetext ON mdl_assign_submission.id = mdl_assignsubmission_onlinetext.submission INNER JOIN mdl_course_modules ON mdl_assign_submission.assignment = mdl_course_modules.instance WHERE mdl_course_modules.module = 1 AND mdl_assign_submission.userid = :userid', array('userid' => $userid));
   // evifix
   $onlinetexts = $DB->get_records_sql('SELECT mdl_course_modules.id AS coursemoduleid, mdl_course_modules.course AS courseid, mdl_assignsubmission_onlinetext.onlinetext AS ontext FROM  mdl_assign_submission INNER JOIN mdl_assignsubmission_onlinetext ON mdl_assign_submission.id = mdl_assignsubmission_onlinetext.submission INNER JOIN mdl_course_modules ON mdl_assign_submission.assignment = mdl_course_modules.instance WHERE mdl_assign_submission.userid = :userid', array('userid' => $userid));

   foreach($onlinetexts as $ot) {
     if (strpos($ot->ontext, $fullpath) !== false) {
       if (local_badgemaker_assignsubmission_file_pluginfile_user_has_badge_for_course_module_id($userid, $ot->courseid, $ot->coursemoduleid)) {
         return true;
       }
     }
   }
   return false;
 }

 function local_badgemaker_badge_has_public_setting($badge) {
   global $CFG;
   if (!$CFG->badgemaker_public_optional) {
     return true;
   }
   $pp = $CFG->badgemaker_public_passphrase;
   if (strlen($pp) == 0) {
     $pp = get_string('default_public_phrase', 'local_badgemaker');
   }
   $res = preg_match("/$pp/i", $badge->description);
   if ($res) {
     return true;
   }
   return false;
 }

 function local_badgemaker_assignsubmission_file_pluginfile($course,
                                           $cm,
                                           context $context,
                                           $filearea,
                                           $args,
                                           $forcedownload) {
     global $DB, $CFG;

     if ($context->contextlevel != CONTEXT_MODULE) {
         return false;
     }

     require_once($CFG->dirroot . '/mod/assign/locallib.php');
     require_once($CFG->dirroot . '/lib/badgeslib.php');
     require_once($CFG->dirroot . '/course/lib.php');
     require_once($CFG->dirroot . '/mod/assign/submission/onlinetext/locallib.php');

     $itemid = (int)array_shift($args);
     $record = $DB->get_record('assign_submission',
                               array('id'=>$itemid),
                               'userid, assignment, groupid',
                               MUST_EXIST);
     $userid = $record->userid;
     $groupid = $record->groupid;

     // need to get the fullpath early now...
     $relativepath = implode('/', $args);

     $fullpath = "/{$context->id}/assignsubmission_file/$filearea/$itemid/$relativepath";
     // die($fullpath);

     // the default is that we are NOT going to let anyone see this file
     $public_badge_link = false;

     if (local_badgemaker_assignsubmission_file_pluginfile_is_used_by_badge_onlinetext($userid, $fullpath) || local_badgemaker_assignsubmission_file_pluginfile_is_badge_upload($userid, $course, $cm)) {
       $public_badge_link = true;
     }

     $assign = new assign($context, $cm, $course);

     if (!$public_badge_link) {
       if ($assign->get_instance()->id != $record->assignment) {
         return false;
       }

       if ($assign->get_instance()->teamsubmission &&
         !$assign->can_view_group_submission($groupid)) {
         return false;
       }

       if (!$assign->get_instance()->teamsubmission &&
         !$assign->can_view_submission($userid)) {
           return false;
         }
     }

     $fs = get_file_storage();
     if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
         return false;
     }

 		// var_dump($file);die();

     // die('would now return file');
     // Download MUST be forced - security!
    //  var_dump($file); die();
     send_stored_file($file, 0, 0, true);
 }

 function local_badgemaker_filter_nonissued($badges) {
   $filtered = array();
   foreach($badges as $badge) {
     if (empty($badge->dateissued)) {
       continue;
     }
     $filtered[] = $badge;
   }
   return $filtered;
 }

 function local_badgemaker_libraryPageURL() {
   return $url = new moodle_url('/local/badgemaker/badge_library.php');
 }

 function local_badgemaker_aboutPageURL() {
   return $url = new moodle_url('/badgemakerabout.php');
 }

 function local_badgemaker_badgeIssuedId($userid, $badgeid) {
   global $DB;

   $params = array('badgeid' => $badgeid, 'userid' => $userid);

   $sql = 'SELECT id FROM {badge_issued} bi WHERE bi.badgeid = :badgeid AND bi.userid = :userid';

   $issued = $DB->get_records_sql($sql, $params);

   // there will only be one match but using foreach means we don't need
   // to worry about what the index/key number is
   foreach ($issued as $iss) {
     return $iss->id;
   }

   return null;
 }

 // The badges that are available for the course minus the ones the user has already earned
 function local_badgemaker_earnable_badges($course, $user) {
   $badgesAvailable = badges_get_badges(BADGE_TYPE_COURSE, $course);
   $badgesAwarded = badges_get_user_badges($user, $course);
   $displayBadges = array();

   foreach($badgesAvailable as $ba) {
     $awarded = false;
     foreach ($badgesAwarded as $ab) {
       if ($ab->id == $ba->id) {
         $awarded = true;
         break;
       }
     }
     if (!$awarded) {
       $displayBadges[] = $ba;
     }
   }

   if (count($displayBadges) == 0) {
     return null;
   }
   return $displayBadges;
 }

 function local_badgemaker_badges_get_badges($type = 0, $courseid = 0, $sort = '', $dir = '', $page = 0, $perpage = BADGE_PERPAGE, $user = 0) {
     global $DB;
     $records = array();
     $params = array();
     $where = $type == 0 ? "b.status != :deleted " : "b.status != :deleted AND b.type = :type ";
     $params['deleted'] = BADGE_STATUS_ARCHIVED;

     $userfields = array('b.id, b.name, b.status');
     $usersql = "";
     if ($user != 0) {
         $userfields[] = 'bi.dateissued';
         $userfields[] = 'bi.uniquehash';
         $usersql = " LEFT JOIN {badge_issued} bi ON b.id = bi.badgeid AND bi.userid = :userid ";
         $params['userid'] = $user;
         $where .= " AND (b.status = 1 OR b.status = 3) ";
     }
     $fields = implode(', ', $userfields);

     if ($courseid != 0 ) {
         $where .= "AND b.courseid = :courseid ";
         $params['courseid'] = $courseid;
     }

     $sorting = (($sort != '' && $dir != '') ? 'ORDER BY ' . $sort . ' ' . $dir : '');
     if ($type != 0) {
       $params['type'] = $type;
     }

     $sql = "SELECT $fields FROM {badge} b $usersql WHERE $where $sorting";
     $records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

     $badges = array();
     foreach ($records as $r) {
         $badge = new badge($r->id);
         $badges[$r->id] = $badge;
         if ($user != 0) {
             $badges[$r->id]->dateissued = $r->dateissued;
             $badges[$r->id]->uniquehash = $r->uniquehash;
         } else {
             $badges[$r->id]->awards = $DB->count_records_sql('SELECT COUNT(b.userid)
                                         FROM {badge_issued} b INNER JOIN {user} u ON b.userid = u.id
                                         WHERE b.badgeid = :badgeid AND u.deleted = 0', array('badgeid' => $badge->id));
             $badges[$r->id]->statstring = $badge->get_status_name();
         }
     }
     return $badges;
 }

 function local_badgemaker_issuedBadgesCount($publicOnly = true) {
   global $DB;

   $params = array('pubo' => $publicOnly ? 1 : 0);

   $sql = 'SELECT count(id) AS total_issued FROM {badge_issued} bi WHERE visible = :pubo';

   $res = $DB->get_records_sql($sql, $params);
   if (count($res)) {
     // Trick to ignore the non-zero index
     foreach ($res as $r) {
       return $r->total_issued;
     }
   }
   return null;
 }

 function local_badgemaker_filesForCourseModule($cmid, $userid) {
   global $DB, $CFG;

   require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');
   require_once($CFG->dirroot . '/mod/assign/submission/file/locallib.php');
   $params = array('userid' => $userid, 'coursemoduleid' => $cmid, 'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA);

   $sql = 'SELECT f.pathnamehash FROM {course_modules} cm JOIN {assign_submission} sub ON cm.instance = sub.assignment JOIN {files} f ON sub.id = f.itemid WHERE cm.id = :coursemoduleid AND sub.userid = :userid AND f.filearea = :filearea AND f.filesize > 0';

   $res = $DB->get_records_sql($sql, $params);

   $fs = get_file_storage();

   $filesOnly = array();
   foreach ($res as $fileRecord) {
     // var_dump($fileRecord);die();
     $file = $fs->get_file_by_hash($fileRecord->pathnamehash);
     $filesOnly[] = $file;
   }

   return $filesOnly;
 }

 function local_badgemaker_filesAndAssignTitlesForCourseModule($cmid, $userid) {
   global $DB, $CFG;

   require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');
   require_once($CFG->dirroot . '/mod/assign/submission/file/locallib.php');
   $params = array('userid' => $userid, 'coursemoduleid' => $cmid, 'filearea' => ASSIGNSUBMISSION_FILE_FILEAREA, 'fuserid' => $userid);

   $sql = 'SELECT f.pathnamehash, a.name FROM {course_modules} cm JOIN {assign_submission} sub ON cm.instance = sub.assignment JOIN {assign} a ON a.id = sub.assignment JOIN {files} f ON sub.id = f.itemid WHERE cm.id = :coursemoduleid AND sub.userid = :userid AND f.filearea = :filearea AND f.userid = :fuserid AND f.filesize > 0';


   $res = $DB->get_records_sql($sql, $params);

   $fs = get_file_storage();

   $filesWithTitles = array();
   foreach ($res as $fileRecord) {
     // var_dump($fileRecord);die();
     $file = $fs->get_file_by_hash($fileRecord->pathnamehash);
     $combined = array();
     $combined['file'] = $file;
     $combined['name'] = $fileRecord->name;
     $filesWithTitles[] = $combined;
   }

   return $filesWithTitles;
 }

 function local_badgemaker_onlineTextsForCourseModule($cmid, $userid) {
   global $DB;

   $params = array('userid' => $userid, 'coursemoduleid' => $cmid);

   $sql = 'SELECT onlinetext FROM {course_modules} cm JOIN {assign_submission} asub ON cm.instance = asub.assignment JOIN {assignsubmission_onlinetext} ot ON ot.submission = asub.id WHERE cm.id = :coursemoduleid AND asub.userid = :userid';

   $ots = $DB->get_records_sql($sql, $params);
   $otsOnly = array();
   foreach ($ots as $ot) {
     $otsOnly[] = $ot->onlinetext;
   }

   return $otsOnly;
 }

 function local_badgemaker_onlineTextsWithAssignmentNamesForCourseModule($cmid, $userid) {
   global $DB;

   $params = array('userid' => $userid, 'coursemoduleid' => $cmid);

   $sql = 'SELECT onlinetext, name FROM {course_modules} cm JOIN {assign_submission} asub ON cm.instance = asub.assignment JOIN {assign} a ON a.id = asub.assignment JOIN {assignsubmission_onlinetext} ot ON ot.submission = asub.id WHERE cm.id = :coursemoduleid AND asub.userid = :userid';
	//die($sql);
   $ots = $DB->get_records_sql($sql, $params);
   $namedOts = array();
   foreach ($ots as $ot) {
     $not = array();
     $not['text'] = $ot->onlinetext;
     $not['name'] = $ot->name;
     $namedOts[] = $not;
   }

   return $namedOts;
 }

 function local_badgemaker_criteriaModulesForIssuedBadge($ibadge) {
   global $DB;

   $params = array('userid' => $ibadge->recipient->id, 'badgeid' => $ibadge->badgeid);

   $sql = 'SELECT bcp.value AS course_module FROM {badge_issued} bi JOIN {badge} b ON bi.`badgeid` = b.id JOIN {badge_criteria} bc ON bc.`badgeid` = b.id JOIN {badge_criteria_param} bcp ON bcp.`critid` = bc.`id` WHERE bcp.name LIKE \'module_%\' AND bi.userid = :userid and b.id = :badgeid';

   $modules = $DB->get_records_sql($sql, $params);
   $midsOnly = array();
   foreach ($modules as $module) {
     $midsOnly[] = $module->course_module;
   }

   return $midsOnly;
 }

 function local_badgemaker_criteriaModulesForRecipientAndBadge($recipientId, $badgeId) {
   global $DB;

   $params = array('userid' => $recipientId, 'badgeid' => $badgeId);

   $sql = 'SELECT bcp.value AS course_module FROM {badge_issued} bi JOIN {badge} b ON bi.`badgeid` = b.id JOIN {badge_criteria} bc ON bc.`badgeid` = b.id JOIN {badge_criteria_param} bcp ON bcp.`critid` = bc.`id` WHERE bcp.name LIKE \'module_%\' AND bi.userid = :userid and b.id = :badgeid';

   $modules = $DB->get_records_sql($sql, $params);
   $midsOnly = array();
   foreach ($modules as $module) {
     $midsOnly[] = $module->course_module;
   }

   return $midsOnly;
 }


 function local_badgemaker_onlineTextForIssuedBadge($ibadge) {
   $badge = new badge($ibadge->badgeid);

   // Find the module
   // Find the online text submissions by this user
   var_dump($badge);die();
 }

 function local_badgemaker_recentCourseBadges($courseid = 0, $page = 0, $perpage = 0, $search = '', $onlypublic = true) {
     global $DB;

     $params = array(
         'courseid' => $courseid
     );
     $sql = 'SELECT
                 bi.uniquehash,
                 bi.dateissued,
                 bi.dateexpire,
                 bi.id as issuedid,
                 bi.visible,
                 u.email,
                 u.firstname,
                 u.lastname,
                 b.*
             FROM
                 {badge} b,
                 {badge_issued} bi,
                 {user} u
             WHERE b.id = bi.badgeid
                 AND u.id = bi.userid';
                 // -- AND bi.userid = :userid';

     if (!empty($search)) {
         $sql .= ' AND (' . $DB->sql_like('b.name', ':search', false) . ') ';
         $params['search'] = '%'.$DB->sql_like_escape($search).'%';
     }
     if ($onlypublic) {
         $sql .= ' AND (bi.visible = 1) ';
     }

     if ($courseid != 0) {
         $sql .= ' AND (b.courseid = :courseid) ';
     }
     $sql .= ' ORDER BY bi.dateissued DESC';

     $badges = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

     return $badges;
 }

function local_badgemaker_extend_settings_navigation($settingsnav, $context){
  global $CFG, $PAGE;
       if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
           $strfoo = "blah";//get_string('foo', 'local_myplugin');
           $url = new moodle_url('/local/badgemaker/foo.php', array('id' => $PAGE->course->id));
           $foonode = navigation_node::create(
               $strfoo,
               $url,
               navigation_node::NODETYPE_LEAF,
               'badgemaker',
               'badgemaker',
               new pix_icon('t/addcontact', $strfoo)
           );
           if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
               $foonode->make_active();
           }
           $settingnode->add_node($foonode);
       }
}

// Based on add_front_page_course_essentials from navigationlib.php
function local_badgemaker_extend_navigation(global_navigation $navigation)
{
    global $CFG;
    //$coursenode = $navigation->find('site', global_navigation::TYPE_ROOTNODE);

    //if ($navigation == false || $navigation->get('local_badge_library_frontpageloaded', navigation_node::TYPE_CUSTOM)) {
    //    return true;
    //}

    // Hidden node that we use to determine if the front page navigation is loaded.
    // This required as there are not other guaranteed nodes that may be loaded.
    //$navigation->add('local_badge_library_frontpageloaded', null, global_navigation::TYPE_CUSTOM, null, 'local_badge_library_frontpageloaded')->display = false;

    $sitecontext = context_system::instance();

    //debugging(var_export($navigation, true));
    //global $COURSE;
    //$coursenode = $navigation->find('site');

    // if badges are enabled and the user is allowed to view badges.
    if (!empty($CFG->enablebadges) && has_capability('moodle/badges:viewbadges', $sitecontext)) {
        $url = new moodle_url('/local/badgemaker/badge_library.php');
        $blnode = $navigation->add(get_string('badge_library', 'local_badgemaker'), $url, navigation_node::TYPE_CONTAINER);

        // disabled this since it's in nav bar.
        //$url = new moodle_url('/local/badgemaker/about_open_badges.php');
        //$blnode->add(get_string('about', 'local_badgemaker'), $url, navigation_node::TYPE_CUSTOM);
    }

}

// Copied from badges_get_badges and the following modifications
//  - type is now optional just like courseid is.
//  - if a user is supplied then it only returns issued badges.
//  - bi.visible added
//  - issued id added
function local_badgemaker_get_badges($type = 0, $courseid = 0, $sort = '', $dir = '', $page = 0, $perpage = BADGE_PERPAGE, $user = 0, $search = '') {
    global $DB;
    global $CFG; // MH

    $records = array();
    $params = array();
    $where = "b.status != :deleted ";// MH $where = "b.status != :deleted AND b.type = :type ";

    // MH
    if ($type != 0 ) {
        $where .= "AND b.type = :type ";
        $params['type'] = $type;
    }
    else if (!$CFG->badges_allowcoursebadges){
        $where .= "AND b.type = :type ";
        $params['type'] = BADGE_TYPE_SITE;
    }
    if (!empty($search) && $search != '') {
      $where .= "AND name LIKE '%$search%' ";
      // $params['search'] = $search;
    }

    $params['deleted'] = BADGE_STATUS_ARCHIVED;

    $userfields = array('b.id, b.name, b.status');
    $usersql = "";
    if ($user != 0) {
        $userfields[] = 'bi.dateissued';
        $userfields[] = 'bi.uniquehash';
        $usersql = " LEFT JOIN {badge_issued} bi ON b.id = bi.badgeid AND bi.userid = :userid ";
        $params['userid'] = $user;
        $where .= " AND bi.dateissued IS NOT NULL"; // MH issued added

        // MH
        $userfields[] = 'bi.visible';
        $userfields[] = 'bi.id AS issuedid';
    }

    // MH
    $usersql .= " LEFT JOIN {course} c on c.id = b.courseid";
    $userfields[] = 'c.fullname';



    $fields = implode(', ', $userfields);

    if ($courseid != 0 ) {
        $where .= "AND b.courseid = :courseid ";
        $params['courseid'] = $courseid;
    }

    $sorting = (($sort != '' && $dir != '') ? 'ORDER BY ' . $sort . ' ' . $dir : '');
    $params['type'] = $type;

    $sql = "SELECT $fields FROM {badge} b $usersql WHERE $where $sorting";
    $records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    $badges = array();
    foreach ($records as $r) {
        $badge = new badge($r->id);

        // MH
        $badge->courseFullname = $r->fullname;

        $badges[$r->id] = $badge;
        if ($user != 0) {
            $badges[$r->id]->dateissued = $r->dateissued;
            $badges[$r->id]->uniquehash = $r->uniquehash;

            // MH
            $badge->visible = $r->visible;
            $badge->issuedid = $r->issuedid;

        } else {
            $badges[$r->id]->awards = $DB->count_records_sql('SELECT COUNT(b.userid)
                                        FROM {badge_issued} b INNER JOIN {user} u ON b.userid = u.id
                                        WHERE b.badgeid = :badgeid AND u.deleted = 0', array('badgeid' => $badge->id));
            $badges[$r->id]->statstring = $badge->get_status_name();
        }
    }
    return $badges;
}

function local_badgemaker_render_issued_badge(issued_badge $ibadge) {
  global $USER, $CFG, $DB, $SITE;

  die('hey');

  $issued = $ibadge->issued;
  $userinfo = $ibadge->recipient;
  $badgeclass = $ibadge->badgeclass;
  $badge = new badge($ibadge->badgeid);
  $now = time();
  $expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;

  $output = '';
  $output .= html_writer::start_tag('div', array('id' => 'badge'));
  $output .= html_writer::start_tag('div', array('id' => 'badge-image'));
  $output .= html_writer::empty_tag('img', array('src' => $badgeclass['image'], 'alt' => $badge->name));
  if ($expiration < $now) {
    $output .= $this->output->pix_icon('i/expired',
    get_string('expireddate', 'badges', userdate($issued['expires'])), 'moodle', array('class' => 'expireimage'));
  }

  if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
      $output .= $this->output->single_button(
                  new moodle_url('/badges/badge.php', array('hash' => $issued['uid'], 'bake' => true)),
                  get_string('download'),
                  'POST');
      if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now) && badges_user_has_backpack($USER->id)) {
          $assertion = new moodle_url('/badges/assertion.php', array('b' => $issued['uid']));
          $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
          $attributes = array(
                  'type'  => 'button',
                  'id'    => 'addbutton',
                  'value' => get_string('addtobackpack', 'badges'));
          $tobackpack = html_writer::tag('input', '', $attributes);
          $this->output->add_action_handler($action, 'addbutton');
          $output .= $tobackpack;
      }
  }
  $output .= html_writer::end_tag('div');

  $output .= html_writer::start_tag('div', array('id' => 'badge-details'));
  // Recipient information.
  $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
  $dl = array();
  if ($userinfo->deleted) {
      $strdata = new stdClass();
      $strdata->user = fullname($userinfo);
      $strdata->site = format_string($SITE->fullname, true, array('context' => context_system::instance()));

      $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
  } else {
      $dl[get_string('name')] = fullname($userinfo);
  }
  $output .= $this->definition_list($dl);

  $output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
  $dl = array();
  $dl[get_string('issuername', 'badges')] = $badge->issuername;
  if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
      $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
  }
  $output .= $this->definition_list($dl);

  $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
  $dl = array();
  $dl[get_string('name')] = $badge->name;
  $dl[get_string('description', 'badges')] = $badge->description;

  if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
      $coursename = $DB->get_field('course', 'fullname', array('id' => $badge->courseid));
      $dl[get_string('course')] = $coursename;
  }
  $dl[get_string('bcriteria', 'badges')] = core_badges_renderer::print_badge_criteria($badge);
  $output .= $this->definition_list($dl);

  $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
  $dl = array();
  $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
  if (isset($issued['expires'])) {
      if ($issued['expires'] < $now) {
          $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']) . get_string('warnexpired', 'badges');

      } else {
          $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
      }
  }

  $output .= $this->definition_list($dl);

  if (isloggedin() || local_badgemaker_badge_has_public_setting($badge)) {
    // Print evidence.
    $agg = $badge->get_aggregation_methods();
    $evidence = $badge->get_criteria_completions($userinfo->id);
    $eids = array_map(create_function('$o', 'return $o->critid;'), $evidence);
    unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

    $items = array();
    foreach ($badge->criteria as $type => $c) {
      if (in_array($c->id, $eids)) {
        if (count($c->params) == 1) {
          $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
        } else {
          $items[] = get_string('criteria_descr_' . $type , 'badges',
          core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
        }
      }
    }

    require_once($CFG->dirroot . "/local/badgemaker/lib.php");

    $courseModules = local_badgemaker_criteriaModulesForIssuedBadge($ibadge);
    foreach ($courseModules as $courseModule) {
      // check files
      // $files = badgemaker_filesForCourseModule($courseModule, $ibadge->recipient->id);
      $files = local_badgemaker_filesAndAssignTitlesForCourseModule($courseModule, $ibadge->recipient->id);
      foreach ($files as $fileWithName) {
        $file = $fileWithName['file'];
        $fpath = '/'.$file->get_contextid();
        $fpath .= '/assignsubmission_file/submission_files/';
        $fpath .= $file->get_itemid();
        $fpath .= '/'.$file->get_filename();
        $furl = moodle_url::make_file_url('/pluginfile.php', $fpath, true);
        $name = $fileWithName['name'];
        $fs = "<b>$name</b><br><ul><li>".html_writer::link($furl, $file->get_filename()).'</li></ul>';
        array_push($items, $fs);
      }

      // check online text
      $ots = local_badgemaker_onlineTextsWithAssignmentNamesForCourseModule($courseModule, $ibadge->recipient->id);
      foreach ($ots as $not) {
        $ot = $not['text'];
        $name = $not['name'];
        $trimmed = trim($ot);
        $trimmed = strip_tags($trimmed);
        if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
          // When trimmed it's a link and nothing else so leave it trimmed
          $trimmed = "<b>$name</b><br><ul><li>".'<a href="'.$trimmed.'">'.$trimmed.'</a></li></ul>';
          array_push($items, $trimmed);
        } else {
          // It's more than just a link so autolink it and put the whole thing in.  However, we also need to strip tags to make the autolink work.
          $ot = strip_tags($ot);
          $ot = "<b>$name</b><br><ul><li>".local_badgemaker_autolink($ot).'</li></ul>';
          array_push($items, $ot);
        }
      }
    }

    $output .= $this->output->heading(get_string('evidence', 'badges'), 3);

    $evs = get_string('completioninfo', 'badges') . html_writer::alist($items, array(), 'ul');
    $output .= $evs;

    $output .= html_writer::end_tag('div');
  }
  return $output;
}
