<?php

require_once(dirname(dirname(__FILE__)).'/lib.php');

// includes the title and the tab bar, which appear on both my and all pages.
function local_badgemaker_library_print_heading(moodle_url $currenturl, $title = 'Untitled', $helpidentifier = null, $component = 'local_badgemaker')
{
    // Combine image and title into a single heading...
    // $img = html_writer::empty_tag('img', array('src' => '../BM_icon.png', 'width' => '10%')); // align center does not work, right does though.
    // echo $OUTPUT->heading($img.$title);
    global $OUTPUT;

    $img = html_writer::empty_tag('img', array('src' => '../BM_icon.png', 'width' => '10%')); // align center does not work, right does though.

    if ($helpidentifier) {
        echo $OUTPUT->heading_with_help($img . $title, $helpidentifier, $component);
    } else {
        echo $OUTPUT->heading($img . $title);
    }

    $tabs = array();

    //$myurl = new moodle_url('/local/badgemaker/library/my.php', array('contextid' => $context->id));
    $myurl = local_badgemaker_libraryPageURL();
    $tabs[] = new tabobject('my', $myurl, get_string('my_badges', 'local_badgemaker'));
    $currenttab = 'my';

    //$allurl = new moodle_url('/local/badgemaker/library/all.php', array('contextid' => $context->id));
    $allurl = new moodle_url('/local/badgemaker/library/all.php');
    $tabs[] = new tabobject('all', $allurl, get_string('all_badges', 'local_badgemaker'));
    if ($currenturl->get_path() === $allurl->get_path()) {
        $currenttab = 'all';
    }

    $tabtree = new tabtree($tabs, $currenttab);
    echo $OUTPUT->render($tabtree);
}

// modified from $managementRenderer->view_mode_selector
function local_badgemaker_view_mode_menu(array $modes, $currentmode, moodle_url $url = null, $param = 'view') {
    if ($url === null) {
        global $PAGE;
        $url = $PAGE->url;
    }

    $menu = new action_menu;
    $menu->attributes['class'] .= ' view-mode-selector vms';

    $selected = null;
    foreach ($modes as $mode => $modestr) {
        $attributes = array(
            'class' => 'vms-mode',
            'data-mode' => $mode
        );
        if ($currentmode === $mode) {
            $attributes['class'] .= ' currentmode';
            $selected = $modestr;
        }
        if ($selected === null) {
            $selected = $modestr;
        }
        $modeurl = new moodle_url($url, array($param => $mode));
        if ($mode === 'default') {
            $modeurl->remove_params($param);
        }
        $menu->add(new action_menu_link_secondary($modeurl, null, $modestr, $attributes));
    }

    // outputs the current selection
    $menu->set_menu_trigger($selected);

    return $menu;
}

function local_badgemaker_sort_menu($currSortBy = null, $currSortHow = null)//array $sorts, $currentmode, moodle_url $url = null, $param = 'view')
{
    global $PAGE;
    // sort
    // from management.php course and cateogyr management page, right had menu that has sort courses in middle.
    // public function course_listing_actions(coursecat $category, course_in_list $course = null, $perpage = 20) {
    $params = $PAGE->url->params();
    //$params['action'] = 'resortcourses';
    //$params['sesskey'] = sesskey();
    //unset($params['dir']); // clear dir so we can have ASC by default.
    $baseurl = new moodle_url('my.php', $params);
    $dateissuedurl = new moodle_url($baseurl, array('sort' => 'dateissued'));
    $dateissueddescurl = new moodle_url($baseurl, array('sort' => 'dateissued', 'dir' => 'ASC'));
    $nameurl = new moodle_url($baseurl, array('sort' => 'name'));
    $nameurldesc = new moodle_url($baseurl, array('sort' => 'name', 'dir' => 'DESC'));
    $courseurl = new moodle_url($baseurl, array('sort' => 'course'));
    $courseurldesc = new moodle_url($baseurl, array('sort' => 'course', 'dir' => 'DESC'));

    $sorts = [];
    $sorts[] = ['url' => $dateissuedurl, 'title' => get_string('sort_dateissued_ascending', 'local_badgemaker')];
    $sorts[] = ['url' => $dateissueddescurl, 'title' => get_string('sort_dateissued_descending', 'local_badgemaker')];
    $sorts[] = ['url' => $nameurl, 'title' => get_string('sort_name_ascending', 'local_badgemaker')];
    $sorts[] = ['url' => $nameurldesc, 'title' => get_string('sort_name_descending', 'local_badgemaker')];
    $sorts[] = ['url' => $courseurl, 'title' => get_string('sort_course_ascending', 'local_badgemaker')];
    $sorts[] = ['url' => $courseurldesc, 'title' => get_string('sort_course_descending', 'local_badgemaker')];

    $triggerName = $sorts[0];
    switch ($currSortBy) {
      case 'name':
        $triggerName = $currSortHow === 'ASC' ? $sorts[2]['title'] : $sorts[3]['title'];
      break;
      case 'course':
        $triggerName = $currSortHow === 'ASC' ? $sorts[4]['title'] : $sorts[5]['title'];
      break;
      case 'dateissued':
        $triggerName = $currSortHow === 'ASC' ? $sorts[1]['title'] : $sorts[0]['title'];
      break;
      default:
        $triggerName = $sorts[1]['title'];
      break;
    }
    $actions = [];
    $firstSort = $sorts[0];
    $trigger = $triggerName;//$firstSort['title'];
    foreach ($sorts as $sort){
        $url = $sort['url'];
        $title = $sort['title'];
        echo 'a';
        if( array_key_exists('sort', $params) && $params['sort'] == $url->param['sort']){
            echo 'b';
            if( array_key_exists('dir', $params) && $params['dir'] == $url->param['dir']) {
                echo 'c';
                $trigger = $title;
            }
        }
        $actions[] =  new action_menu_link_secondary($url,
            null,
            $title);
    }

    $menu = new action_menu($actions);

    $menu->set_menu_trigger($trigger);
    // $sortdropdown = html_writer::div($this->render($menu), 'listing-actions course-listing-actions');
    //$sortdropdown = $this->render($menu);//html_writer::tag('div', $this->render($menu), array('class' => 'listing-actions course-listing-actions'));//, 'style' => 'float: left'));
    return $menu;
}
