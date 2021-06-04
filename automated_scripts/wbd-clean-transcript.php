<?php

/*
  wbd-clean-transcript.php <source>

  Given a What Bitcoin Did html page, extract and clean the good bits
*/

mb_internal_encoding("UTF-8");
require_once('common-scrape.php');

//
function getStart($source) {
  $vals = array();
  $vals[stripos($source, 'transcription</h3>')] = strlen('transcription</h3>');
  $vals[stripos($source, 'transcription</h2>')] = strlen('transcription</h2>');
  $vals[stripos($source, 'transcription<strong></strong></h3>')] = strlen('transcription<strong></strong></h3>');
  $vals[stripos($source, 'Transcription<strong>                                      </strong></h3>')] = strlen('Transcription<strong>                                      </strong></h3>');
  $key = array_keys($vals)[count($vals)-1];
  var_dump($key);
  var_dump($vals[$key]);
  var_dump('^^^^');
}

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 1) {
  $msg = "Expected one argument\n";
  $result = 1;
  goto completed;
}

// Receive the html on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  // Read in the index page
  $source1 = trim(stream_get_contents(STDIN));
}

// getStart($source1);

// Find transcript start and delete anything before it
$a = stripos($source1, '<strong>Peter McCormack:</strong>');
$l = strlen('<strong>Peter McCormack:</strong>');
if ($a === false) {
  $a = stripos($source1, '<strong>Peter McCormack</strong></a><strong>:');
  $l = strlen('<strong>Peter McCormack</strong></a><strong>:');
  if ($a === false) {
    $a = stripos($source1, '<strong>Peter McCormack</strong></a>:');
    $l = strlen('<strong>Peter McCormack</strong></a>:');
    if ($a === false) {
      $rv = 1;
      goto completed;
    }
  }
}
// $b = $a + $l;
//$source2 = substr($source1, $b);
$source2 = substr($source1, $a);

// Find transcript end and delete anything after it
$c = strpos($source2, '</div>');
if ($c > 1) {
  // If a transcript actually found clean it up
  $source3 = substr($source2, 0, $c);

  // Standard clean ups shared with other scraoers
  $source3 = clean_text($source3);
  $msg = $source3 . "\n";

} else {
  // Otherwise prepare to Do Nothing - resulting in a zero byte transcription
  // file. #39 is an example of "transcript:" matching accidentally.
  $msg = '';
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);
?>
