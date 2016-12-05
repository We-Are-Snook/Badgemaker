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

class block_badgemaker_newest_created_badges extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_newest_created_badges');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
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
          $this->title = get_string('newest_badges', 'block_badgemaker_newest_created_badges');
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

        if (!isset($this->config->numberofnewestcreatedbadges)) {
            $this->config->numberofnewestcreatedbadges = 10;
        }

        if (!isset($this->config->typeofnewestcreatedbadges)) {
            $this->config->typeofnewestcreatedbadges = 0;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'badges');
            return $this->content;
        }

        $badges = local_badgemaker_badges_get_badges($this->config->typeofnewestcreatedbadges, 0, 'timecreated', 'DESC', 0, $this->config->numberofnewestcreatedbadges);

        if (count($badges) > 0){
          // we use our own custom renderer here...
          $output = new badgemaker_renderer($this->page);
          $this->content->text = $output->print_meta_badges_list($badges);
        } else {
          return null;
          // $this->content->text .= get_string('nothingtodisplay', 'block_badgemaker_newest_course_badges');
        }

        return $this->content;
	}
}
