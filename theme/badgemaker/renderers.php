<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/badges/renderer.php');

class theme_badgemaker_core_badges_renderer extends core_badges_renderer {

  protected function render_issued_badge(issued_badge $ibadge) {
    global $USER, $CFG, $DB, $SITE;

    $issued = $ibadge->issued;
    $userinfo = $ibadge->recipient;
    $badgeclass = $ibadge->badgeclass;
    $badge = new badge($ibadge->badgeid);
    $now = time();
    $expiration = isset($issued['expires']) ? $issued['expires'] : $now + 86400;

    $output = '';
    $output .= html_writer::start_tag('div', array('id' => 'badge'));
    $output .= html_writer::start_tag('div', array('id' => 'badge-image'));
    $output .= html_writer::empty_tag('img', array('src' => $badgeclass['image'], 'alt' => $badge->name));
    // var_dump($badgeclass['image']);die();

    // This is how you get a handle to the raw file with the larger image in it...
    // $fs = get_file_storage();
    // if ($file = $fs->get_file($badge->get_context()->id, 'badges', 'badgeimage', $badge->id, '/', 'f3.png')) {
    //     var_dump($file);die();
    // }

    if ($expiration < $now) {
      $output .= $this->output->pix_icon('i/expired',
      get_string('expireddate', 'badges', userdate($issued['expires'])), 'moodle', array('class' => 'expireimage'));
    }

    if ($USER->id == $userinfo->id && !empty($CFG->enablebadges)) {
        $output .= $this->output->single_button(
                    new moodle_url('/badges/badge.php', array('hash' => $issued['uid'], 'bake' => true)),
                    get_string('download'),
                    'POST');
        if (!empty($CFG->badges_allowexternalbackpack) && ($expiration > $now) && badges_user_has_backpack($USER->id)) {
            $assertion = new moodle_url('/badges/assertion.php', array('b' => $issued['uid']));
            $action = new component_action('click', 'addtobackpack', array('assertion' => $assertion->out(false)));
            $attributes = array(
                    'type'  => 'button',
                    'id'    => 'addbutton',
                    'value' => get_string('addtobackpack', 'badges'));
            $tobackpack = html_writer::tag('input', '', $attributes);
            $this->output->add_action_handler($action, 'addbutton');
            $output .= $tobackpack;
        }
    }
    $output .= html_writer::end_tag('div');

    $output .= html_writer::start_tag('div', array('id' => 'badge-details'));
    // Recipient information.
    $output .= $this->output->heading(get_string('recipientdetails', 'badges'), 3);
    $dl = array();
    if ($userinfo->deleted) {
        $strdata = new stdClass();
        $strdata->user = fullname($userinfo);
        $strdata->site = format_string($SITE->fullname, true, array('context' => context_system::instance()));

        $dl[get_string('name')] = get_string('error:userdeleted', 'badges', $strdata);
    } else {
        $dl[get_string('name')] = fullname($userinfo);
    }
    $output .= $this->definition_list($dl);

    $output .= $this->output->heading(get_string('issuerdetails', 'badges'), 3);
    $dl = array();
    $dl[get_string('issuername', 'badges')] = $badge->issuername;
    if (isset($badge->issuercontact) && !empty($badge->issuercontact)) {
        $dl[get_string('contact', 'badges')] = obfuscate_mailto($badge->issuercontact);
    }
    $output .= $this->definition_list($dl);

    $output .= $this->output->heading(get_string('badgedetails', 'badges'), 3);
    $dl = array();
    $dl[get_string('name')] = $badge->name;
    $dl[get_string('description', 'badges')] = $badge->description;

    if ($badge->type == BADGE_TYPE_COURSE && isset($badge->courseid)) {
        $coursename = $DB->get_field('course', 'fullname', array('id' => $badge->courseid));
        $dl[get_string('course')] = $coursename;
    }
    $dl[get_string('bcriteria', 'badges')] = self::print_badge_criteria($badge);
    $output .= $this->definition_list($dl);

    $output .= $this->output->heading(get_string('issuancedetails', 'badges'), 3);
    $dl = array();
    $dl[get_string('dateawarded', 'badges')] = userdate($issued['issuedOn']);
    if (isset($issued['expires'])) {
        if ($issued['expires'] < $now) {
            $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']) . get_string('warnexpired', 'badges');

        } else {
            $dl[get_string('expirydate', 'badges')] = userdate($issued['expires']);
        }
    }

    $output .= $this->definition_list($dl);

    if (isloggedin() || local_badgemaker_badge_has_public_setting($badge)) {
      // Print evidence.

      $agg = $badge->get_aggregation_methods();
      $evidence = $badge->get_criteria_completions($userinfo->id);
      $eids = array_map(create_function('$o', 'return $o->critid;'), $evidence);
      unset($badge->criteria[BADGE_CRITERIA_TYPE_OVERALL]);

      $items = array();
      foreach ($badge->criteria as $type => $c) {
        if (in_array($c->id, $eids)) {
          if (count($c->params) == 1) {
            $items[] = get_string('criteria_descr_single_' . $type , 'badges') . $c->get_details();
          } else {
            $items[] = get_string('criteria_descr_' . $type , 'badges',
            core_text::strtoupper($agg[$badge->get_aggregation_method($type)])) . $c->get_details();
          }
        }
      }

      require_once($CFG->dirroot . "/local/badgemaker/lib.php");

      $courseModules = local_badgemaker_criteriaModulesForIssuedBadge($ibadge);
      foreach ($courseModules as $courseModule) {
        // check files
        // $missingFile = false;
        $files = local_badgemaker_filesAndAssignTitlesForCourseModule($courseModule, $ibadge->recipient->id);
        foreach ($files as $fileWithName) {
          $file = $fileWithName['file'];
          $file->sync_external_file();
          try {
            $handle = $file->get_content_file_handle();
          } catch (Exception $e) {
            continue;
            // $missingFile = true;
          }
          // die("status: $status<br>handle: $handle");
          $fpath = '/'.$file->get_contextid();
          $fpath .= '/assignsubmission_file/submission_files/';
          $fpath .= $file->get_itemid();
          $fpath .= '/'.$file->get_filename();
          $name = $fileWithName['name'];
          // if ($missingFile) {
          //   $fs = "<b>$name</b><br>";
          // } else {
            $furl = moodle_url::make_file_url('/pluginfile.php', $fpath, true);
            $fs = "<b>$name</b><br><ul><li>".html_writer::link($furl, $file->get_filename()).'</li></ul>';
          // }

          array_push($items, $fs);
        }

        // check online text
        $ots = local_badgemaker_onlineTextsWithAssignmentNamesForCourseModule($courseModule, $ibadge->recipient->id);
        foreach ($ots as $not) {
          $ot = $not['text'];
          $name = $not['name'];
          $trimmed = trim($ot);
          $trimmed = strip_tags($trimmed);
          if (filter_var($trimmed, FILTER_VALIDATE_URL)) {
            // When trimmed it's a link and nothing else so leave it trimmed
            $trimmed = "<b>$name</b><br><ul><li>".'<a href="'.$trimmed.'">'.$trimmed.'</a></li></ul>';
            array_push($items, $trimmed);
          } else {
            // It's more than just a link so autolink it and put the whole thing in.  However, we also need to strip tags to make the autolink work.
            $ot = strip_tags($ot);
            $ot = "<b>$name</b><br><ul><li>".local_badgemaker_autolink($ot).'</li></ul>';
            array_push($items, $ot);
          }
        }
      }

      $output .= $this->output->heading(get_string('evidence', 'badges'), 3);

      $evs = get_string('completioninfo', 'badges') . html_writer::alist($items, array(), 'ul');
      $output .= $evs;

      $output .= html_writer::end_tag('div');
    }
    return $output;
  }
}
