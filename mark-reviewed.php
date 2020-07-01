<?php

/*
  mark-reviewed

  The user wants to add a review withOUT a description
*/

require_once('defines.php');
require_once('classes/reviews_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;

// What tag are we after?
if ($argc !== 4) {
  $effort->err(__FILE__, "expected three arguments");

} else {
  $projname = $argv[1];
  $tempfile = $argv[2];
  $record_to_show = $argv[3];
}

$uploader = STANDARD_USER;
$review_ts = strval(date(TIMESTAMP_FORMAT));

// There is no review: we are only marking this as reviewed
$review_text = '';

shell_exec("php api/review_add.php \"$projname\" \"$review_ts\" \"$uploader\" \"$review_text\" \"$record_to_show\"");
$effort->whatToShowNext($record_to_show);
?>
