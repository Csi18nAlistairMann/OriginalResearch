<?php

/*
   convert-insert-youtube-scrape.php <database>

   This script reads a datafile from STDIN that contains space-seperated tags
   on its first line, and then one or more URLs and titles on successive lines
   with "> being used to seperate each URL and title.

   Script calls a helper that will insert each URL as a Thing with the its
   title as the Nuance. Having done so, that Thing will be linked to each of
   the tags mentioned on the first line.
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 2) {
  $msg = "Expected one argument\n";
  $result = 1;
  goto completed;

} else {
  $projname = escapeshellarg($argv[1]);
}

// Obtain the data on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  $source = trim(stream_get_contents(STDIN));
}

// First line contains tags to be used
$arr = explode("\n", $source, 2);
$args = $arr[0];
$source = $arr[1];

// Successive lines contain youtube URLs, followed by ">, and then the title
$vids_arr = explode("\n", $source);
foreach($vids_arr as $line) {
  $line = trim($line);
  if ($line !== '') {
    $pair = explode('">', $line);
    if (sizeof($pair) !== 2) {
      $msg = "Line '$line' appears invalid\n";
      $result = 1;
      goto completed;
    }

    $url = escapeshellarg($pair[0]);
    $title = escapeshellarg($pair[1]);

    exec("php automated_scripts/add-thing-nuance-tags.php $projname " .
	 "$url $title $args", $output, $rv);
    if ($rv !== 0) {
      print_r($output);
      exit($rv);
    }
  }
}

completed:
if ($msg !== '')
  printf($msg);
exit($result);
?>
