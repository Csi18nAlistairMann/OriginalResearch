<?php

/*
  reviewadd

  The user wants to add a review with description
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/reviews_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog = new dialog;

// What tag are we after?
if ($argc !== 4) {
  $effort->file(__FILE__, "expected three arguments");

} else {
  $projname = escapeshellarg($argv[1]);
  $tempfile = $argv[2];
  $record_to_show = $argv[3];
  $esc_record_to_show = escapeshellarg($record_to_show);
}

$uploader = escapeshellarg(STANDARD_USER);
$review_ts = escapeshellarg(strval(date(TIMESTAMP_FORMAT)));

// Get the review from the user
$dialog->sizes_change(MENU_SZ_SHORT);
$dialog->edit = $tempfile;
$review_text = escapeshellarg(trim($dialog->show()));

if ($review_text !== '') {
  shell_exec("php api/review_add.php $projname $review_ts " .
	     "$uploader $review_text $esc_record_to_show");
}
$effort->whatToShowNext($record_to_show);
?>
