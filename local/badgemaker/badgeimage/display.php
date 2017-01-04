<?php

/**
 * @package    Badgemaker
 * @copyright  2017 We Are Snook <code@wearesnook.com>
 * @license    https://www.gnu.org/licenses/gpl-3.0.en.html GNU GPLv3
 */

require_once(dirname(dirname(dirname(dirname($_SERVER["SCRIPT_FILENAME"])))) . '/config.php'); // allows going up through a symlink
require_once($CFG->libdir . '/badgeslib.php');
require_once(dirname(dirname(__FILE__)).'/renderer.php');
require_once(dirname(dirname(__FILE__)).'/lib.php');

$fs = get_file_storage();
$badgecontextid = optional_param('bci', 0, PARAM_INT); // 272;
$badgeid = optional_param('bid', 0, PARAM_INT); // 17
if ($file = $fs->get_file($badgecontextid, 'badges', 'badgeimage', $badgeid, '/', 'f3.png')) {
  header('Content-Type: image/png');
  $contents = $file->get_content();
  echo $contents;
  die();
}
