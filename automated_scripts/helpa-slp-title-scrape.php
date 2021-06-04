<?php

/*
  slp-title-scrape.php <source>

  A script to extract a Stephan Livera Podcast title from a supplied index
  page.
*/

mb_internal_encoding("UTF-8");
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

// Find episode title
$a = stripos($source1, '<title>');
$a += strlen('<title>');
$b = stripos($source1, '</title>');
$title = substr($source1, $a, $b - $a);
$source2 = clean_text($title);

$msg = $source2;

completed:
if ($msg !== '')
  echo($msg);
exit($result);

?>
