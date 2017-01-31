<?php

/**
 * @package    BadgeMaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->libdir . "/badgeslib.php");
require_once($CFG->dirroot . "/local/badgemaker/lib.php");
require_once($CFG->dirroot . "/local/badgemaker/renderer.php");

class block_badgemaker_recent_course_awards extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_recent_course_awards');
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
                'course-view' => true,
                'mod' => false,
                'my' => false
        );
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('title', 'block_badgemaker_recent_course_awards');
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

        if (!isset($this->config->numberofbadges)) {
            $this->config->numberofbadges = 5;
        }

        // Create empty content.
        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'block_badgemaker_recent_course');
            return $this->content;
        }

        $courseid = $this->page->course->id;
        if ($courseid == SITEID) {
            $courseid = null;
        }

        $badges = local_badgemaker_recentCourseBadges($courseid, 0, $this->config->numberofbadges);

        if (count($badges) > 0) {
          // we use our own custom renderer here...
          $output = new badgemaker_renderer($this->page, 'badges');
            // $output = $this->page->get_renderer('core', 'badges');
            $this->content->text = $output->recent_course_badges_list($badges, 0, null, 'center');
            $this->content->text .= $output->print_badgemaker_linked_logo();
        } else {
          return null;
            // $this->content->text .= get_string('nothingtodisplay', 'block_badgemaker_recent_course_awards');
        }



        return $this->content;
    }
}
