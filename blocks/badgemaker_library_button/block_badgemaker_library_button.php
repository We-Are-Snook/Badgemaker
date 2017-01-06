<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->dirroot . '/local/badgemaker/lib.php');

class block_badgemaker_library_button extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_library_button');
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function has_config() {
        return true;
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
            $this->title = get_string('title', 'block_badgemaker_library_button');
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

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';

        // before button
        if (!empty($CFG->block_badgemaker_library_button_before)) {
          if (strlen($CFG->block_badgemaker_library_button_before) > 0) {
            $this->content->text .= $CFG->block_badgemaker_library_button_before.'<br>';
          }
        }

        if (!empty($CFG->block_badgemaker_library_button_showlogo)) {
          if ($CFG->block_badgemaker_library_button_showlogo) {
            $ls = local_badgemaker_logo_source();
            $img = html_writer::empty_tag('img', array('src' => $ls, 'width' => '14%', 'align' => 'left'));
            $this->content->text .= $img;
          }
        }

        // button
        $buttonUrl = local_badgemaker_libraryPageURL();
        $buttonTitle = get_string('button_text', 'block_badgemaker_library_button');
        $this->content->text .= $OUTPUT->single_button($buttonUrl, $buttonTitle);

        // $this->content->text .= html_writer::end_tag("whatever");

        // after button
        if (!empty($CFG->block_badgemaker_library_button_after)) {
          if (strlen($CFG->block_badgemaker_library_button_after) > 0) {
            $this->content->text .= $CFG->block_badgemaker_library_button_after;
          }
        }

        return $this->content;
    }
}
