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

class block_badgemaker_course_badges extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_course_badges');
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
                'admin' => false,
                'site-index' => false,
                'course' => true,
                'mod' => false,
                'my' => false
        );
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('title', 'block_badgemaker_course_badges');
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

        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'badges');
            return $this->content;
        }

        $course = $this->page->course;

        $earnable = local_badgemaker_earnable_badges($course->id, $USER->id);
        $earned = badges_get_badges(BADGE_TYPE_COURSE, $course->id, '', '', 0, BADGE_PERPAGE, $USER->id);
        $earned = local_badgemaker_filter_nonissued($earned);
        // die("x: ".count($earned));
        $earnedCount = count($earned);
        $earnableCount = count($earnable);
        $totalBadgeCount = $earnableCount + $earnedCount;

        if ($totalBadgeCount == 0) {
          return null;
        }

        // require_once($CFG->dirroot . "/badgemaker/badgemaker_renderer.php");
        // $output = $this->page->get_renderer('core', 'badges');
        $output = new badgemaker_renderer($this->page, 'badges');

        // $this->content->text .= $output->heading("Badges: $earnedCount/$totalBadgeCount", 6);

        $this->content->text .= html_writer::start_div('course_badges_title', array('align' => 'center'));
        $this->content->text .= html_writer::start_span('lead');
        $yhe = get_string('earned', 'block_badgemaker_course_badges');
        $badgesString = get_string('badges', 'block_badgemaker_course_badges');
        $ofString = get_string('of', 'block_badgemaker_course_badges');
        $this->content->text .= "$yhe $earnedCount $ofString $totalBadgeCount $badgesString";
        $this->content->text .= html_writer::end_span();
        $this->content->text .= html_writer::end_div('course_badges_title');

        $this->content->text .= $output->print_combined_overview_list($earned, $earnable, 100, 'center', null, null, false);

        $this->content->text .= $output->print_badgemaker_linked_logo();

        return $this->content;
	}
}
