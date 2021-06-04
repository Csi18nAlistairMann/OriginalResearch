<?php

/*
  wbd-transcriptions-scrape.php <index.html>

  A script to extract what transcriptions are available from the What Bitcoin
  Did transcriptions page

  For each transcription noted on the site, script dumps the URL in a suitably
  named directory for later processing.
*/

mb_internal_encoding("UTF-8");
require_once('defines.php');

// checkFor
//
// Try to match on URLs described by the arguments
//
// $source - the text being checked for matches
// $root - What to prepend to make relative URLs absolute
// $match_on - href=" and whatever follows to make this a match
// $match_ignore - That part of the $match_on that isn't part of the URL
//
// Return: array('WBD001' => ('https://path/to/transcription.wbd001',
//                            'This is the first transcription'),
//               ...
//              )
function checkFor($source, $root, $match_on, $match_ignore) {
  $rv = [];
  $a = $b = 0;

  // Repeatedly scan source until we find mo more matches
  do {
    $found = false;

    // Locate next candidate for a match
    $a = strpos($source, $match_on, $a);
    if ($a !== false) {
      $found = true;

      // Note what matched. Ignore it if excluded otherwise get the absolute
      // URL
      $a += strlen($match_ignore);
      $b = strpos($source, '"', $a);
      if (substr($source, $a, $b - $a) === '/wbd-specials')
	continue;
      $url = $root . substr($source, $a, $b - $a);

      // Look beyond the match for the title
      $b = $a = $b + strlen("><strong>") + 1;
      while ($source[++$b] !== '<');
      $title = substr($source, $a, $b - $a);

      // From the title get the episode number. Usually delimited by space or
      // colon
      $c = 0;
      while($c < strlen($title) - 1 && $title[$c] != ':' && $title[$c] != ' ')
	$c++;
      if ($c === strlen($title)) {
	$episode = '???';
      } else {
	$episode = substr($title, 0, $c);
      }
      // Create a directory for this episode
      $dir = WBD_DIR . $episode;
      if (is_dir($dir)) {
	// We probably have already done this one
	echo "[$dir] $title\n";

      } else {
	mkdir($dir);
	echo "[$dir] $title\n";

	// In each directory we want to dump the URL where the episode can be
	// found
	file_put_contents($dir . '/transcription.url', $url);

	// Store for later passing back
	$rv[$episode] = array($url, $title);
      }
    }
    $a = $b;
  } while ($found === true);

  // Return whatever was found
  return $rv;
}

//
// Start point
//

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

// Try to match house style
$arr = array();
$arr = array_merge($arr, checkFor($source1, '',
				  'https://www.whatbitcoindid.com/wbd', ''));
$arr = array_merge($arr, checkFor($source1, 'https://www.whatbitcoindid.com',
				  'href="/wbd', 'href="'));
$arr = array_merge($arr, checkFor($source1, 'https://www.whatbitcoindid.com/',
				  'href="wbd', 'href="'));
$arr = array_merge($arr, checkFor($source1, 'https://www.whatbitcoindid.com',
				  'href="/transcription-', 'href="'));
// Three transcripts do not use house style
$arr = array_merge($arr, checkFor($source1, '',
				  'https://www.whatbitcoindid.com/podcast/casas-jeremy-welch-and-alena-vranova-on-crypto-custody',
				  ''));
$arr = array_merge($arr, checkFor($source1, '',
				  'https://www.whatbitcoindid.com/podcast/reviewing-the-bitmain-ipo-with-samson-mow-and-katherine-wu',
				  ''));
$arr = array_merge($arr, checkFor($source1,
				  'https://www.whatbitcoindid.com',
				  'href="/mike-dudas-interview', 'href="'));

// Sort them latest to earliest, report what we have to user
ksort($arr, SORT_NATURAL);
foreach($arr as $pair) {
  list($url, $title) = $pair;
  echo $url . ' <-> ' . $title . "\n";
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);
?>
