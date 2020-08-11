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
// Note that we don't take a literal reading of what comes in. For instance, a
// URL https://example.com/INDEX.html would be compared as
// https    example.com index.html; and "Mr. Smith" as "mr  smith". This could
// mean "https:!!!examPLE.com@index.HTML" and "MR- SMITH" would be considered
// matches. This compromise is necessary to be able to handle how spacing
// works in real use, where a great many ASCII codes (and at least one Unicode
// codepoint) end words.
//
// Example usage:
// cat /tmp/email | php remove.php -g uk-us-english-2011 --or-words \
// "/tmp/or-words" --personal-words my-words

mb_internal_encoding("UTF-8");

define("DELIMITER", '@@@');

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

// With a source we've never seen before we are looking to establish where the
// boundary is between words. That's not always clear: spaces, newline, line
// feeds, punctuation marks all count. But not always! A full stop doesn't
// count in a fully qualified domain name, for instance.
function cleanText($source1) {
  $source1 = str_replace("\n", DELIMITER, $source1);
  // Clean the source of word separators
  $source1 = trim(mb_strtolower($source1));
  // Add a final space to guarantee we finish with it
  $source1 .= ' ';
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
  return $source2;
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

// Draw just the personal dictionary in
$personal_skiplist = file_get_contents($personal_paf) ;
$personal_skiplist_arr = explode(DELIMITER, cleanText($personal_skiplist));
usort($personal_skiplist_arr, 'sortByLength');

// Merge the three skip lists into one, and clean
// The OR list is treated differently because it starts with and includes
// delimiters
$found = false;
if ($or_paf !== '') {
  $or_skiplist = file_get_contents($or_paf);
  if (mb_strlen($or_skiplist) > 0) {
    $found = true;
    // the first line of the OR skiplist defines the delimiter used throughout
    $or_skiplist_delim = trim(mb_substr($or_skiplist, 0,
					mb_strpos($or_skiplist, "\n")));
    $or_skiplist = str_replace("\n", '', $or_skiplist);
  }
}
if ($found === false) {
  $or_skiplist_arr = array();
}
// The other two are one word per line
$general_skiplist = file_get_contents($general_paf);
$personal_skiplist = file_get_contents($personal_paf) ;
// The two kinds may have different delimiters
$or_skiplist_arr = explode($or_skiplist_delim, cleanText($or_skiplist));
$skiplist_arr = explode(DELIMITER, cleanText($general_skiplist) . DELIMITER .
			cleanText($personal_skiplist));
// Merge the two, sort them, resolve duplicates and reindex them
$skiplist_arr = array_merge($skiplist_arr, $or_skiplist_arr);
usort($skiplist_arr, 'sortByLength');
$skiplist_arr = array_values(array_unique($skiplist_arr));

// Bookending with space here and below makes string replacement easier
$source2 = ' ' . cleanText($source1) . ' ';
$source2 = str_replace(DELIMITER, ' ', $source2);

// Should we find a match in the source for anything in the skiplist, replace
// it with a space
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
}

//
// Now convert what remains of the source into an array, one 'word' per
// element. We do so by replacing whitespace (space, newline etc) with a single
// unlikely string, then extract whatever's between occurences of that string.
$source3 = mb_ereg_replace("\s", DELIMITER, $source2);
$source_arr3 = explode(DELIMITER, $source3);

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
$output_arr2 = array_unique($output_arr);
sort($output_arr2);
foreach($output_arr2 as $word) {
  print_r($word . "\n");
}

?>
