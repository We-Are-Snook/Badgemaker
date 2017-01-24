<?php

/**
 * @package    Badgemaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

global $CFG;

require_once($CFG->libdir . "/badgeslib.php");
require_once($CFG->dirroot . '/local/badgemaker/lib.php');

class block_badgemaker_user_overview extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_badgemaker_user_overview');
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
            $this->title = get_string('title', 'block_badgemaker_user_overview');
        } else {
            $this->title = $this->config->title;
        }
    }

    public function get_content() {
        global $USER, $PAGE, $CFG, $SITE;

        $badgesAwarded = badges_get_user_badges($USER->id);
        $badgesAwardedCount = count($badgesAwarded);

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        // Create empty content.
        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->text .= html_writer::start_div('overview-content', array('align' => 'center'));
        $this->content->text .= html_writer::start_span('lead');

        if (empty($CFG->enablebadges)) {
            $this->content->text .= get_string('badgesdisabled', 'badges');
            return $this->content;
        }

        $siteBadges = badges_get_badges(BADGE_TYPE_SITE);
        $courseBadges = badges_get_badges(BADGE_TYPE_COURSE);
        $totalBadges = count($siteBadges) + count($courseBadges);
        $issuedCount = local_badgemaker_issuedBadgesCount();

        $myliburl = local_badgemaker_libraryPageURL();

        $this->content->text .= get_string('you_have_earned', 'block_badgemaker_user_overview') . ' <b>' . html_writer::link($myliburl, ''.$badgesAwardedCount) . "</b> " . get_string('badges', 'block_badgemaker_user_overview');

        $this->content->text .= "<hr><b>";
        $blurl = local_badgemaker_libraryAllPageURL();
        $this->content->text .= html_writer::link($blurl, ''.$issuedCount);
        $this->content->text .= "</b>" . get_string('awarded', 'block_badgemaker_user_overview');
        $this->content->text .= '<br>' . "<b>";
        $this->content->text .= html_writer::link($blurl, ''.$totalBadges);
        $this->content->text .= '</b>' . get_string('all_badges', 'block_badgemaker_user_overview');
        $this->content->text .= html_writer::end_span();
        $this->content->text .= html_writer::start_div('org-name', array('style' => 'line-height: 0.9; color: grey'));
        $this->content->text .= '<br>' . get_string('at', 'block_badgemaker_user_overview') . ' ' . $SITE->fullname;
        $this->content->text .= html_writer::end_div('org-name');
        $this->content->text .= html_writer::end_div('overview-content');

        // $foot = get_string('footer', 'block_badgemaker_totals_sitewide');
        // if (strlen($foot) > 0) {
        //   $this->content->footer = html_writer::link($blurl, $foot);
        // }

        $ls = local_badgemaker_logo_source();
        $img = html_writer::empty_tag('img', array('src' => $ls, 'width' => '15%'));
        $this->content->footer = '<div align="center">'.html_writer::link($blurl, $img).'</div>';

        return $this->content;
    }
}
