<?php

/**
 * @package    Badgemaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->libdir . "/badgeslib.php");
require_once($CFG->dirroot . "/local/badgemaker/lib.php");
require_once($CFG->dirroot . "/local/badgemaker/renderer.php");

class block_badgemaker_awarded_in_my_courses extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_awarded_in_my_courses');
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
            $this->title = get_string('title', 'block_badgemaker_awarded_in_my_courses');
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
            $this->config->numberofbadges = 3;
        }

        // Create empty content.
        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'badges');
            return $this->content;
        }

		$output = new badgemaker_renderer($this->page, 'badges');

		$courses = enrol_get_users_courses($USER->id);
		$allBadges = array();

		foreach($courses as $course){
			// get x most recent badges per course
			// inefficient but using array of courseIDs in an sql query might open up an sql injection vulnerability.
			$badges = local_badgemaker_recentCourseBadges($course->id, 0, $this->config->numberofbadges);
			if(!$badges){
				continue;
			}
			$allBadges = array_merge($allBadges, $badges);
			//$this->content->text .= count($badges)."<br>";
		}
		// sort by date issues
	    function compare_dateissued($a, $b)
	    {
	      return $a->dateissued < $b->dateissued;
	    }
	    usort($allBadges, 'compare_dateissued');

	    // since we might have 10 x number of courses limit to only 10 overall.
      $allBadges = array_slice($allBadges, 0, $this->config->numberofbadges);
      if (count($allBadges)) {
        $this->content->text .= $output->recent_course_badges_list($allBadges);
      } else {
        return null;
        // $this->content->text .= get_string('nothingtodisplay', 'block_badgemaker_awarded_in_my_courses');
      }

      $liblink = local_badgemaker_libraryPageURL();
      $logolink = html_writer::start_div('library-button', array('align' => 'center'));
      $logolink .= html_writer::tag('hr');
      $logolink .= html_writer::start_tag('a', array('href' => $liblink));
      $ls = local_badgemaker_logo_source();
      $img = html_writer::empty_tag('img', array('src' => $ls, 'width' => '14%'));
      $logolink .= $img;
      $logolink .= html_writer::end_tag('a');
      $logolink .= html_writer::end_div('library-button');
      $this->content->text .= $logolink;

      return $this->content;
	}

	/**
 	 * Get badges for a specific course. Modified version of badges_get_user_badges.
	 *
	 * @param int $courseid Badges earned in a course
	 * @param int $page The page or records to return
	 * @param int $perpage The number of records to return per page
	 * @param string $search A simple string to search for
	 * @param bool $onlypublic Return only public badges
	 * @return array of badges ordered by decreasing date of issue
	 */
	function badges_get_course_badges($courseid = 0, $page = 0, $perpage = 0, $search = '', $onlypublic = false) {
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
}
