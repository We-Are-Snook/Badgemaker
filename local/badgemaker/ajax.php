<?php

/**
 * @package    Badgemaker
 * @copyright  2016 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

/*
 * This prevents a 404 being written to the web console on the badge library page.
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

parse_str($_SERVER['QUERY_STRING'], $params);
$url = new moodle_url('/badges/ajax.php', $params);
header('Location: '.$url->out(false));