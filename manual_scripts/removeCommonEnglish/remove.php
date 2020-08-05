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

mb_internal_encoding("UTF-8");

// https://stackoverflow.com/questions/838227/php-sort-an-array-by-the-length-of-its-values
function sortByLength($a, $b){
  return mb_strlen($b) - mb_strlen($a);
}

// Fake use of mb_ord
// https://www.php.net/manual/en/function.ord.php
function ordutf8($string, &$offset) {
  $code = ord(substr($string, $offset,1));
  if ($code >= 128) {        //otherwise 0xxxxxxx
    if ($code < 224) $bytesnumber = 2;                //110xxxxx
    else if ($code < 240) $bytesnumber = 3;        //1110xxxx
    else if ($code < 248) $bytesnumber = 4;    //11110xxx
    $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
    for ($i = 2; $i <= $bytesnumber; $i++) {
      $offset ++;
      $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
      $codetemp = $codetemp*64 + $code2;
    }
    $code = $codetemp;
  }
  $offset += 1;
  if ($offset >= strlen($string)) $offset = -1;
  return $code;
}

// findURLs for when don't have mb_ord
function findURLs($source, $protocol_head) {
  $urls_arr = array();
  $pos = 0;
  while($pos !== false) {
    // Find the next occurence of https://
    $pos = mb_strpos($source, $protocol_head, $pos);
    if ($pos !== false) {
      // If found

      // Find the next occurence of the bookend character, usually a space
      $bookend_char = mb_substr($source, $pos - 1, 1);
      if ($bookend_char !== '’' && $bookend_char !== '"' &&
	  $bookend_char !== "'") {
	$bookend_char = ' ';
      }
      $endpos = mb_strpos($source, $bookend_char, $pos);

      // If no bookend character, assume URL continues to end
      if ($endpos === false)
	$endpos = mb_strlen($source) - $pos;

      // Retrieve what we think is the URL
      $candidate = mb_substr($source, $pos, $endpos - $pos);

      // If a codepoint below ASCII 32 (space) or below exists, that's the end
      for($endpos = 0; $endpos < mb_strlen($candidate);) {
	$prev_endpos = $endpos;
	$cp = ordutf8($candidate, $endpos);
	if ($cp <= 32) {
	  break;
	}
      }
      if ($endpos < strlen($candidate))
	$candidate = substr($candidate, 0, $prev_endpos);

      // And store that URL
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
$source1 = ' ' . trim(mb_strtolower($source1)) . ' ';
$source1 = str_replace("’", "'", $source1);
$source1 = str_replace('“', '"', $source1);
$source1 = str_replace('”', '"', $source1);
$source1 = str_replace('‘', '"', $source1);
$source2 = '';
for($a = 0; $a < strlen($source1);) {
  $prev_a = $a;
  $cp = ordutf8($source1, $a);
  if ($a === -1)
    break;
  $b = $a;
  $next_cp = ordutf8($source1, $b);
  if (
      ($cp > 238 && $cp !== 8212) // Allow unicode with exceptions
      ||
      ($cp >= 97 && $cp <= 122) // Allow ascii
      ||
      ($cp >= 65 && $cp <= 90)
      ||
      ($cp >= 48 && $cp <= 57)
      ||
      // Allow full stop if followed by text - eg, an FQDN
      ($source1[$prev_a] === '.' && $next_cp >= 97 && $next_cp <= 122)
      ||
      // Allow apostrophe only if NOT followed by full stop or space
      // So: allow if possessive, exclude if quote
      ($source1[$prev_a] === "'" && $source1[$a] !== '.' &&
       $source1[$a] !== ' ')
      ||
      // Allow space, '@' for email addresses, and hyphens
      $source1[$prev_a] === ' ' || $source1[$prev_a] === '@' ||
      $source1[$prev_a] === '-'
      ) {
    if ($a === $prev_a + 1) {
      $source2 .= $source1[$prev_a];
    } else {
      $source2 .= substr($source1, $prev_a, $a - $prev_a);
    }

  } else {
    $source2 .= ' ';
  }
}

// Merge the three skip lists into one, and clean
$found = false;
if ($or_paf !== '') {
  $or_skiplist = mb_strtolower(file_get_contents($or_paf));
  if (mb_strlen($or_skiplist) > 0) {
    $found = true;
    // the first line of the OR skiplist defines the delimiter used throughout
    $or_skiplist_delim = trim(mb_substr($or_skiplist, 0,
					mb_strpos($or_skiplist, "\n")));
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
  if (mb_substr($skiplist_arr[$s], 0, mb_strlen('https://')) !== 'https://'
      &&
      mb_substr($skiplist_arr[$s], 0, mb_strlen('http://')) !== 'http://') {
    $skiplist_arr[$s] = mb_strtolower($skiplist_arr[$s]);
  }
}

//
// Remove from source and urls anything that appears in the skiplist. That is
// already ordered by descending length such that we won't remove "super"
// before removing "superman"
foreach($skiplist_arr as $string) {
  $string = trim($string);
  if (mb_strlen($string) > 0) {
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
$source3 = mb_ereg_replace("\s", "@@@", $source2);
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
  $sword = trim(mb_strtolower($sword), "\t\n\r\0\x0B\.\,\;\:\!\?\'\(\)\/");
  if (mb_strlen($sword) > 0 && !is_numeric($sword)) {
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
