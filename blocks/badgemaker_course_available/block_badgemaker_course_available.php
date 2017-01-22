<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->libdir . "/badgeslib.php");
require_once($CFG->dirroot . "/local/badgemaker/lib.php");
require_once($CFG->dirroot . "/local/badgemaker/renderer.php");

class block_badgemaker_course_available extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_course_available');
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
            $this->title = get_string('title', 'block_badgemaker_course_available');
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

        // var_dump($PAGE);die();
        // $course = $this->page->course;
        // var_dump($course);die();
        $course = $this->page->course;
        $badges = badges_get_badges(BADGE_TYPE_COURSE, $course->id, 'timecreated', 'DESC', 0);

        if (count($badges) > 0){
          $output = new badgemaker_renderer($this->page, 'badges');
          $this->content->text = $output->print_meta_badges_list($badges);
        } else {
          return null;
            // $this->content->text .= get_string('nothingtodisplay', 'block_badgemaker_course_available');
        }

        return $this->content;
	}
}
