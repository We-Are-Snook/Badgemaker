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