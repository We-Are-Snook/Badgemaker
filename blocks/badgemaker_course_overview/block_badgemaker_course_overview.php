<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->dirroot . '/local/badgemaker/lib.php');
require_once($CFG->dirroot . '/blocks/course_overview/block_course_overview.php');

class block_badgemaker_course_overview extends block_course_overview {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_course_overview');
    }

    public function specialization() {
        if (empty($this->config->title)) {
            $this->title = get_string('title', 'block_badgemaker_course_overview');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $USER, $CFG, $DB;
        require_once($CFG->dirroot.'/user/profile/lib.php');

        if($this->content !== NULL) {
            return $this->content;
        }

        $config = get_config('block_course_overview');

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $content = array();

        $updatemynumber = optional_param('mynumber', -1, PARAM_INT);
        if ($updatemynumber >= 0) {
            block_course_overview_update_mynumber($updatemynumber);
        }

        profile_load_custom_fields($USER);

        $showallcourses = ($updatemynumber === self::SHOW_ALL_COURSES);
        list($sortedcourses, $sitecourses, $totalcourses) = block_course_overview_get_sorted_courses($showallcourses);
        $overviews = block_course_overview_get_overviews($sitecourses);

        $renderer = $this->page->get_renderer('block_badgemaker_course_overview');
        if (!empty($config->showwelcomearea)) {
            require_once($CFG->dirroot.'/message/lib.php');
            $msgcount = message_count_unread_messages();
            $this->content->text = $renderer->welcome_area($msgcount);
        }

        // Number of sites to display.
        if ($this->page->user_is_editing() && empty($config->forcedefaultmaxcourses)) {
            $this->content->text .= $renderer->editing_bar_head($totalcourses);
        }

        if (empty($sortedcourses)) {
            $this->content->text .= get_string('nocourses','my');
        } else {
            // For each course, build category cache.
            $this->content->text .= $renderer->course_overview($sortedcourses, $overviews);
            $this->content->text .= $renderer->hidden_courses($totalcourses - count($sortedcourses));
        }

        return $this->content;
    }
}
