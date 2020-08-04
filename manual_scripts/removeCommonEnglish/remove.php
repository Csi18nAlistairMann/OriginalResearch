<?php

// remove.php
//
// This is a script to show uncommon words in a source, on the basis they may
// refer to a Thing I don't already follow.
//

//
// Each file referenced by an argument can be considered a dictionary, a dict.
//
// The general-words dict I use is 'uk-us-english-2011'.
// "uk-us-english-2011" merges the 2011 dictionary of British and American
// English words supplied for a Linux distro. I've not looked for anything more
// recent on the grounds that it might contain words I do want to track. This
// file is one word per line.
//
// The personal-words dict I use is 'my-words'.
// This dict contains words that I'm comfortable excluding for whatever reason,
//  including:
//  - contraction ("haven't", "that'll")
//  - variant ("implement / implements / implemented / implementations"
//  - well understood ("api", "ai")
//  - miswritten ("are.and", "now.and")
// This file is also one word per line.
//
// The or-words dict is created from the Original Research database.
// It contains a list of words and phrases of things which we are already
// tracking via Original Research. It differs by containing strings and tracts
// that we're already following, separated by a delimiter which is specified on
// the first line. This allows us to include newlines in entries. Obtain using
// $ php manual_scripts/display-or-words.php <project name> >/tmp/or-words
//
// Example usage:
// cat /tmp/email | php remove.php -g uk-us-english-2011 --or-words \
// "/tmp/or-words" --personal-words my-words

// https://stackoverflow.com/questions/838227/php-sort-an-array-by-the-length-of-its-values
function sortByLength($a, $b){
  return strlen($b) - strlen($a);
}

// Given a head (such as 'https://' copy all text following it into an array.
// If the character before is one of the quote marks, stop copying at the next
// appearance of that quote mark; otherwise at first char(0) to
// char(32) (space)
function findURLs($source, $protocol_head) {
  $urls_arr = array();
  $pos = 0;
  while($pos !== false) {
    $pos = strpos($source, $protocol_head, $pos);
    if ($pos !== false) {
      $bookend_char = substr($source, $pos - 1, 1);
      if ($bookend_char !== '’' && $bookend_char !== '"' &&
	  $bookend_char !== "'") {
	$bookend_char = ' ';
      }
      $endpos = strpos($source, $bookend_char, $pos);
      if ($endpos === false)
	$endpos = strlen($source) - $pos;
      $candidate = substr($source, $pos, $endpos - $pos);
      $endpos = 0;
      for($endpos = 0; $endpos < strlen($candidate); $endpos++) {
	if (ord($candidate[$endpos]) <= 32) {
	  break;
	}
      }
      if ($endpos !== strlen($candidate))
	$candidate = substr($candidate, 0, $endpos);
      $urls_arr[] = $candidate;
      $found = true;
      $pos++;
    }
  }
  return $urls_arr;
}

// default paths and files
$general_paf = '';
$or_paf = '';
$personal_paf = '';
// if source_paf is not '' then it must refer to a file
// if source_paf is '' then STDIN is our source and must not be zerolen
$source_paf = '';

// retrieve command line options
$options = getopt('g:o:p:s::', array("general-words:", "or-words:",
				     "personal-words:", "source::"));
if (array_key_exists('g', $options))
  $general_paf = $options['g'];
if (array_key_exists('general-words', $options))
  $general_paf = $options['general-words'];
if (array_key_exists('o', $options))
  $or_paf = $options['o'];
if (array_key_exists('or-words', $options))
  $or_paf = $options['or-words'];
if (array_key_exists('p', $options))
  $personal_paf = $options['p'];
if (array_key_exists('personal-words', $options))
  $personal_paf = $options['personal-words'];
if (array_key_exists('s', $options))
  $source_paf = $options['s'];
if (array_key_exists('source', $options))
  $source_paf = $options['source'];

// Handle required arguments
if (!file_exists($general_paf)) {
  exit("Unable to open general words file\n");
}
if (!file_exists($personal_paf)) {
  exit("Unable to open personal words file\n");
}

// Handle optional arguments
// Specifically, draw the source in either way it's provided
if (!file_exists($or_paf)) {
  print_r("Unable to open OR words file -- assuming empty\n");
  $or_paf = '';
}
if (!file_exists($source_paf)) {
  $source1 = stream_get_contents(STDIN);
  if ($source1 === FALSE) {
    exit("Unable to get STDIN\n");
  }
  if ($source1 === '') {
    exit("STDIN unexpectedly empty\n");
  }

} else {
  $source1 = file_get_contents($source_paf);
}

// Extract URLs before processing source
$urls_arr1 = findURLs($source1, 'https://');
$source1 = str_replace($urls_arr1, '', $source1);
$urls_arr2 = findURLs($source1, 'http://');
$source1 = str_replace($urls_arr2, '', $source1);
$urls_arr = array_merge($urls_arr1, $urls_arr2);

// Clean the source
// Bookending with space here and below makes string replacement easier
$source1 = ' ' . trim(strtolower($source1)) . ' ';
$source1 = str_replace("’", "'", $source1);
$source2 = '';
for($a = 0; $a < strlen($source1); $a++) {
  if (
      // Allow a-z, A-Z and 0-9
      (ord($source1[$a]) >= 97 && ord($source1[$a]) <= 122)
      ||
      (ord($source1[$a]) >= 65 && ord($source1[$a]) <= 90)
      ||
      (ord($source1[$a]) >= 48 && ord($source1[$a]) <= 57)
      ||
      // Allow full stop if followed by text - eg, an FQDN
      ($source1[$a] === '.' && ord($source1[$a + 1]) >= 97 &&
       ord($source1[$a + 1]) <= 122)
      ||
      // Allow apostrophe only if NOT followed by full stop or space
      // So: allow if possessive, exclude if quote
      ($source1[$a] === "'" && $source1[$a + 1] !== '.' &&
       $source1[$a + 1] !== ' ')
      ||
      // Allow space, '@' for email addresses, and hyphens
      $source1[$a] === ' ' || $source1[$a] === '@' || $source1[$a] === '-'
      ) {
    $source2 .= $source1[$a];
  } else {
    $source2 .= ' ';
  }
}

// Merge the three skip lists into one, and clean
$found = false;
if ($or_paf !== '') {
  $or_skiplist = strtolower(file_get_contents($or_paf));
  if (strlen($or_skiplist) > 0) {
    $found = true;
    // the first line of the OR skiplist defines the delimiter used throughout
    $or_skiplist_delim = trim(substr($or_skiplist, 0, strpos($or_skiplist, "\n")));
    $or_skiplist = str_replace("’", "'", $or_skiplist);
    $or_skiplist_arr = explode($or_skiplist_delim, $or_skiplist);
  }
}
if ($found === false) {
  $or_skiplist_arr = array();
}
$general_skiplist = file_get_contents($general_paf) ;
$general_skiplist = str_replace("’", "'", $general_skiplist);
$personal_skiplist = file_get_contents($personal_paf) ;
$personal_skiplist = str_replace("’", "'", $personal_skiplist);
$skiplist_arr = explode("\n", $general_skiplist . "\n" . $personal_skiplist);
$skiplist_arr = array_merge($skiplist_arr, $or_skiplist_arr);
usort($skiplist_arr, 'sortByLength');
$skiplist_arr = array_values(array_unique($skiplist_arr));

// Make lower case anything not a url
for($s = 0; $s < sizeof($skiplist_arr); $s++) {
  if (substr($skiplist_arr[$s], 0, strlen('https://')) !== 'https://'
      &&
      substr($skiplist_arr[$s], 0, strlen('http://')) !== 'http://') {
    $skiplist_arr[$s] = strtolower($skiplist_arr[$s]);
  }
}

//
// Remove from source and urls anything that appears in the skiplist. That is
// already ordered by descending length such that we won't remove "super"
// before removing "superman"
foreach($skiplist_arr as $string) {
  $string = trim($string);
  if (strlen($string) > 0) {
    $found = 1;
    while ($found !== 0) {
      // Loop because PHP seems to leave one of a run of identical matches
      // behind
      $source2 = str_ireplace(" $string ", ' ', $source2, $found);
    }
  }
  for($u = 0; $u < sizeof($urls_arr); $u++) {
    if ($urls_arr[$u] === $string) {
      array_splice($urls_arr, $u, 1);
      $u--;
    }
  }
}

//
// Now convert what remains of the source into an array, one 'word' per
// element. We do so by replacing whitespace (space, newline etc) with a single
// unlikely string, then extract whatever's between occurences of that string.
$source3 = preg_replace("/\s/", "@@@", $source2);
$source_arr3 = explode("@@@", $source3);

//
// Handle each 'word' in turn, storing for futher processing any word in the
// content that does not appear in the skiplists
$output_arr = array();
$count = 0;
foreach($source_arr3 as $sword) {
  // Keep the original source word to make it easier to track down
  $osword = $sword;
  // Remove punctuation
  $sword = trim(strtolower($sword), "\t\n\r\0\x0B\.\,\;\:\!\?\'\(\)\/");
  // If what's left contains at least one letter a-z (so excludes, for example,
  // phone numbers)
  $found = false;
  for($a = 0; $a < strlen($sword); $a++) {
    if (ord($sword[$a]) >= 97 && ord($sword[$a]) <= 122) {
      $found = true;
      break;
    }
  }
  if ($found === true) {
    $output_arr[] = $osword;
  }
}

//
// Finally, remove duplicates and sort what's left before relaying it back to
// the user.
$output_arr2 = array_merge($urls_arr, $output_arr);
$output_arr2 = array_unique($output_arr2);
sort($output_arr2);
foreach($output_arr2 as $word) {
  print_r($word . "\n");
}

?>
