<?php

/**
 * @package    BadgeMaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->dirroot . '/local/badgemaker/lib.php');
require_once($CFG->dirroot . '/local/badgemaker/renderer.php');

class block_badgemaker_toolbox extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_toolbox');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return false;
    }

    public function instance_allow_config() {
        return false;
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
            $this->title = get_string('title', 'block_badgemaker_toolbox');
        } else {
            $this->title = $this->config->title;
        }
    }

    function hide_header() {
  //Default, false--> the header is shown
  return false;
}

    public function get_content() {
        global $USER, $PAGE, $CFG, $OUTPUT;

        // global $COURSE;
        // var_dump($COURSE);
        // die($COURSE);

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        $this->content->text .= html_writer::start_div('tool-buttons', array('align' => 'center'));


        // button
        $buttonUrl = local_badgemaker_libraryPageURL();
        $buttonTitle = get_string('button_text', 'block_badgemaker_toolbox');
        $this->content->text .= $OUTPUT->single_button($buttonUrl, $buttonTitle);

        $course = $this->page->course;
        // $context = get_context_instance(CONTEXT_COURSE, $course->id);
        $context = context_course::instance($course->id);
        if (!has_capability('moodle/course:manageactivities', $context)) {
            return null;
        }

        // var_dump($course);
        // die();
        // button
        $badgeType = $course->category > 0 ? BADGE_TYPE_COURSE : BADGE_TYPE_SITE;
        $urlParams = array('type' => $badgeType);
        if ($badgeType == BADGE_TYPE_COURSE) {
          $urlParams['id'] = $course->id;
        }
        $url = new moodle_url('/badges/newbadge.php', $urlParams);
        // die($url->out());

        $buttonTitle = get_string($badgeType == BADGE_TYPE_SITE ? 'site_button_text' : 'course_button_text', 'block_badgemaker_toolbox');
        $buttonUrl = $url->out();
        $this->content->text .= $OUTPUT->single_button($buttonUrl, $buttonTitle, 'get');

        $bmOutput = new badgemaker_renderer($this->page, 'badges');
        $this->content->text .= $bmOutput->print_badgemaker_linked_logo();

        $this->content->text .= html_writer::end_div('tool-buttons');




        return $this->content;
    }
}
