<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
        'block/badgemaker_course_awarded:addinstance' => array(
                'captype'      => 'read',
                'contextlevel' => CONTEXT_BLOCK,
                'archetypes' => array(
                    'editingteacher' => CAP_ALLOW,
                    'manager' => CAP_ALLOW
                ),
                'clonepermissionsfrom' => 'moodle/site:manageblocks'
        ),
);
