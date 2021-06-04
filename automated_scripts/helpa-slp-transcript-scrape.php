<?php

/*
  slp-transcript-scrape.php <source>

  A script to extract a Stephan Livera Podcast transcript from a supplied index
  page.
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

// Find transcript start and delete anything before it
$a = stripos($source1, 'transcript:');
if ($a !== false) {
  // If 'transcript:' appears, start from first following newline
  $b = strpos($source1, "\n", $a);
  $source2 = substr($source1, $b + 1);

} else {
  // Otherwise look for first paragraph match ...
  $a = 0;
  while($a < strlen($source1) && substr($source1, $a++, 3) !== '<p>');

  $a = stripos($source1, 'Livera:', $a);
  if ($a !== false) {
    // If 'Livera:' follows a <p> then start from that <p>
    while($a > -1 && substr($source1, $a--, 3) !== '<p>');
    $source2 = substr($source1, $a);

  } else {
    $a = stripos($source1, 'welcome to the Stephan', $a);
    if ($a !== false) {
      // Otherwise if 'welcome to the Stephan' follows a <p>, start from that
      // <p>
      while($a > -1 && substr($source1, $a--, 3) !== '<p>');
      $source2 = substr($source1, $a);

    } else {
      // We don't appear to have a transcript at all
      $rv = 1;
      goto completed;
    }
  }
}

// Find transcript end and delete anything after it
$c = strpos($source2, '</div>');
while($c !== 1 && $source2[--$c] !== "\n");
if ($c > 1) {
  // If a transcript actually found clean it up
  $source3 = substr($source2, 0, $c);
  $source3 = clean_text($source3);

  if (strlen($source3) < MIN_TRANSCRIPT_LEN) {
    // Episode 38 matches the above, although there is no transcript. As we've
    // made it this far, check the 'transcript' length.
    $msg = '';

  } else {
    // Otherwise postpend a newline as there won't be one.
    $msg = $source3 . "\n";
  }

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
