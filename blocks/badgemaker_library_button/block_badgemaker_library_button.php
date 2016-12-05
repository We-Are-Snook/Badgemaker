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
        $bbc = get_string('maincontent_before_button', 'block_badgemaker_library_button');
        if (strlen($bbc) > 0) {
          $this->content->text .= $bbc;
        }

        // button
        $buttonUrl = local_badgemaker_libraryPageURL();
        $buttonTitle = get_string('button_text', 'block_badgemaker_library_button');
        $this->content->text .= $OUTPUT->single_button($buttonUrl, $buttonTitle);

        // after button
        $abc = get_string('maincontent_after_button', 'block_badgemaker_library_button');
        if (strlen($abc) > 0) {
          $this->content->text .= $abc;
        }

        // footer
        $foot = get_string('footer', 'block_badgemaker_library_button');
        if (strlen($foot) > 0) {
          $this->content->footer = $foot;
        }

        return $this->content;
    }
}
