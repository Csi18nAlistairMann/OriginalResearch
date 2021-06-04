<?php

/*
  anchor-aurioUrl.php

  A script to extract the audioUrl from an anchor.fm page
*/

mb_internal_encoding("UTF-8");
require_once('defines.php');
require_once('common-scrape.php');

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 1) {
  $msg = "Expected one argument\n";
  $result = 1;
  goto completed;
}

// Obtain the data on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  // Read in the index page
  $source1 = trim(stream_get_contents(STDIN));
}

// Find audio URL and return it
$a = strpos($source1, '"audioUrl":"');
if ($a === false) {
  // Not found
  $result = 1;
  $msg = "No sign of audioUrl crib, aborting\n";
  goto completed;

} else {
  // Found
  $a += strlen('"audioUrl":"');
  $b = strpos($source1, '"', $a);
  $msg = substr($source1, $a, $b - $a);
  $msg = str_replace('\u002F', '/', $msg);
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);

?>
