<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

/*
 * This simply redirects to the badge/badge.php page which allows the renderer's badge actions
 * on the library page that are using a relative URL to work unmodified.
 * The actions are disable access and delete.
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

parse_str($_SERVER['QUERY_STRING'], $params);
$url = new moodle_url('/badges/badge.php', $params);
header('Location: '.$url->out(false));