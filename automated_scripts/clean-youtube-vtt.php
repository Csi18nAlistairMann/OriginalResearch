<?php

/*
  clean-youtube-vtt.php

  This script reads a youtube-style vtt transcription in from STDIN, cleans it,
  and sends whats's left to STDOUT*/

mb_internal_encoding("UTF-8");
require_once('defines.php');

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 1) {
  $msg = "Expected no arguments\n";
  $result = 1;
  goto completed;
}

// Obtain the data on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  $source1 = trim(stream_get_contents(STDIN));
}

// Get us to an array of lines
$source2_arr = explode("\n", $source1);

if ($source2_arr[0] !== 'WEBVTT') {
  echo "Unknown file format - aborting\n";
  goto completed;
} else {
  $source2_arr[0] = ''; // WEBVTT
  $source2_arr[1] = ''; // Kind: captions
  $source2_arr[2] = ''; // Language: en
  $source2_arr[3] = ''; // <empty>
}

// Remove alignment lines
$source3_arr = array();
foreach($source2_arr as $line) {
  $a = strpos($line, 'align:start position');
  if ($a === false) {
    $source3_arr[] = $line;
  }
}

// Remove empty lines
$source4_arr = array();
foreach($source3_arr as $line) {
  $line2 = trim($line);
  if (strlen($line2) > 0) {
    $source4_arr[] = $line;
  }
}

// Strip tags from each line
foreach($source4_arr as $line) {
  do {
    $b = 0;
    $a = strpos($line, '<');
    if ($a !== false) {
      $b = strpos($line, '>', $a);
      $line = substr($line, 0, $a) . substr($line, $b + 1);
    }
  } while ($b !== 0);
  $source5_arr[] = $line;
}

// Now dump lines that match the previous line
$source6_arr = array();
$prev = '';
foreach($source5_arr as $line) {
  if ($line !== $prev)
    $source6_arr[] = $line;
  $prev = $line;
}

// Combine successive lines if the resulting line is < 80 chars
$source7_arr = array();
$combo = '';
foreach($source6_arr as $line) {
  if (strlen($combo) + strlen($line) <= 80) {
    $combo .= " " . $line;
  } else {
    $source7_arr[] = trim($combo);
    $combo = $line;
    $line = '';
  }
}
$source7_arr[] = $combo;

// Get what we've got back to STDOUT
foreach($source7_arr as $line) {
  echo $line . "\n";
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);

?>
