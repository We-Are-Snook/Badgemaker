<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->libdir . "/badgeslib.php");
require_once($CFG->dirroot . '/local/badgemaker/lib.php');
require_once($CFG->dirroot . "/local/badgemaker/renderer.php");

class block_badgemaker_awarded_in_managed_courses extends block_base {

  function get_user_managed_course_ids($userid) {
    global $DB;

    $params = array(
      'userid' => $userid,
      'contextlevel' => CONTEXT_COURSE
    );

    $sql = 'SELECT DISTINCT(c.id) as courseid FROM {role_assignments} ra INNER JOIN {context} ctx ON ra.contextid = ctx.id INNER JOIN {course} c ON ctx.instanceid = c.id WHERE ra.userid = :userid AND ctx.contextlevel = :contextlevel AND ra.roleid < 5';

    $courseRecords = $DB->get_records_sql($sql, $params);

    $idsOnly = array();
    foreach($courseRecords as $cr) {
      $idsOnly[] = $cr->courseid;
    }
    return $idsOnly;
  }

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_awarded_in_managed_courses');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return true;
    }

    public function applicable_formats() {
        return array(
                'admin' => true,
                'site-index' => true,
                'course-view' => true,
                'mod' => true,
                'my' => true
        );
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('title', 'block_badgemaker_awarded_in_managed_courses');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $USER, $PAGE, $CFG;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        // Number of badges to display.
        if (!isset($this->config->numberofbadges)) {
            $this->config->numberofbadges = 10;
        }

        // Create empty content.
        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'badges');
            return $this->content;
        }

		$output = new badgemaker_renderer($this->page);

    $courses = $this->get_user_managed_course_ids($USER->id);

		$allBadges = array();

		foreach($courses as $course){
			// get x most recent badges per course
			// inefficient but using array of courseIDs in an sql query might open up an sql injection vulnerability.
			$badges = local_badgemaker_recentCourseBadges($course, 0, $this->config->numberofbadges);
			if(!$badges){
				continue;
			}
			$allBadges = array_merge($allBadges, $badges);
		}

    if (count($allBadges) == 0) {
      return null;
    }

		// sort by date issues
	    function block_bm_award_created_compare_dateissued($a, $b)
	    {
	      return $a->dateissued < $b->dateissued;
	    }
	    usort($allBadges, 'block_bm_award_created_compare_dateissued');

	    // since we might have 10 x number of courses limit to only 10 overall.
		$allBadges = array_slice($allBadges, 0, $this->config->numberofbadges);
		$this->content->text .= $output->recent_course_badges_list($allBadges);

        return $this->content;
	}
}
