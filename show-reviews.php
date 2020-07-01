<?php

/*
  show-review

  Given a particular tag, display all the reviews associated
  with it.
*/

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/reviews_class.php');
require_once('classes/effort02_class.php');
require_once('classes/akas_class.php');

$effort = new effort02;
$dialog = new dialog;

// What tag are we after?
if ($argc !== 3) {
  $effort->err(__FILE__, "expected two arguments");

} else {
  $projname = $argv[1];
  $record_to_show = $argv[2];
}

// We don't just want reviews for $record_to_show, we also want
// reviews for anything it's AKA
$akas = new akas($projname);
$akas->load();
$akas_db1 = $akas->filter($record_to_show);
$akas_db2 = array_merge(array($record_to_show), $akas_db1);

// work through the things we have looking for the tag or the
// first thing with a tag
$reviews = new reviews($projname);
$reviews->load();
$show = array();
foreach($reviews->db as $item) { // for each review
  foreach($akas_db2 as $aka_tag) { // consider each tag in the akas
    if ($aka_tag === $item->tag())
      $show[] = $item;
  }
}

$review_text = '';
if (sizeof($show)) {
  foreach($show as $review) {
    $review_desc = $review->text();
    if ($review_desc === '') {
      $review_desc = ' (marked as reviewed by ' . $review->user() . ')';
    } else {
      $review_desc .= ' (By: ' . $review->user() . ')';
    }
    $review_text .= '@' . $review->timestamp() . ' ' . $review_desc . '\n';
  }
} else {
  $review_text = '-- No reviews found --';
}

// now show the dialog
$dialog->title = $thing_type . ' #' . $thing_id;
$dialog->msg = $review_text;
$dialog->crwrap(true);
$dialog->sizes_change(MENU_SZ_SHORT);
$output = $dialog->show();

$effort->whatToShowNext($record_to_show);

?>
