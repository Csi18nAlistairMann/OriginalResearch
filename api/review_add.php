<?php

/*
  review_add

  Entry point for CLI calls to add a simple Review
 */

mb_internal_encoding("UTF-8");

require_once('classes/effort02_class.php');
require_once('classes/reviews_class.php');

$effort = new effort02;

// What we're searching for and what we'll link it to
$rv = 1;
if ($argc !== 6) {
  $effort->err(__FILE__, "expected five arguments");

} else {
  $projname = escapeshellarg($argv[1]);
  $ts = $argv[2];
  $user = $argv[3];
  $text = $argv[4];
  $tag = $argv[5];

  $reviews = new reviews($projname);
  $reviews->load();
  $review = new review;
  $review->tag($tag);
  $review->text($text);
  $review->timestamp($ts);
  $review->user($user);
  $reviews->db[] = $review;
  $rv =  $reviews->save();
}

return $rv;

?>
