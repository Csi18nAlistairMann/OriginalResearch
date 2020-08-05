<?php

/*
  interesting

  Given a source, display the list of terms used within it that appear in the
  OR database. If the term is an AKA, use the head term instead. This provides
  an at-a-glance indication of what we might find interesting in the source.

  Note that this obtains the OR terms from a dict, but checks the live database
  for the AKAs.

  Usage:
  interesting.php -d|--db <database> -o|--or-words <OR words file> \
  [-s|--source <source file>]

  The OR words file was previously generated using
  $ php manual_scripts/display-or-words.php test >/tmp/or-words

  Example usage:
  cat /tmp/youtube-transcript | sed -r "s/[[:digit:]]{2,3}:[[:digit:]]{2}/ /" \
  | tr "\n" ' ' | sed "s/   / /g" | php manual_scripts/interesting.php \
  -d test -o /tmp/or-words
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/things_class.php');
require_once('classes/effort02_class.php');
require_once('classes/links_class.php');

// Defaults
// If source_paf is not '' then it must refer to a file
// If source_paf is '' then STDIN is our source and must not be zerolen
$db = '';
$source_paf = '';

// Retrieve command line options
$options = getopt('d:o:s::', array('db:', 'or-words:', 'source::'));
if (array_key_exists('d', $options))
  $db = escapeshellarg($options['d']);
if (array_key_exists('db', $options))
  $db = escapeshellarg($options['db']);
if (array_key_exists('o', $options))
  $or_paf = $options['o'];
if (array_key_exists('or-words', $options))
  $or_paf = $options['or-words'];
if (array_key_exists('s', $options))
  $source_paf = $options['s'];
if (array_key_exists('source', $options))
  $source_paf = $options['source'];

// Handle required arguments
if ($db === '') {
  exit("No database named\n");
}
if (!file_exists($or_paf)) {
  exit("Unable to open OR words file\n");
}

// Handle optional arguments
// Specifically, draw the source in either way it's provided
if (!file_exists($source_paf)) {
  $source1 = trim(mb_strtolower(stream_get_contents(STDIN)));
  if ($source1 === FALSE) {
    exit("Unable to get STDIN\n");
  }
  if ($source1 === '') {
    exit("STDIN unexpectedly empty\n");
  }

} else {
  $source1 = trim(mb_strtolower(file_get_contents($source_paf)));
}
if (mb_strlen($source1) === 0) {
  exit("Source cannot be of zero length\n");
}

// Normalise apostrophes
$source2 = str_replace("’", "'", $source1);

// Draw in and clean the OR words which describe all the words and phrases we
// might be interested in
if ($or_paf !== '') {
  $or_skiplist = mb_strtolower(file_get_contents($or_paf));
  // The first line of the OR skiplist defines the delimiter used throughout
  $or_skiplist_delim = trim(mb_substr($or_skiplist, 0,
				      mb_strpos($or_skiplist, "\n")));
  $or_skiplist = str_replace("’", "'", $or_skiplist);
  $or_skiplist_arr1 = explode($or_skiplist_delim, $or_skiplist);

} else {
  $or_skiplist_arr1 = array();
}
$or_skiplist_arr2 = array();
foreach($or_skiplist_arr1 as $orword) {
  $v = trim($orword);
  if (mb_strlen($v) > 0) {
    $or_skiplist_arr2[] = $v;
  }
}

// Remove from the source any string that appears in the OR words file, and
// keep a note of which it was
$display_arr1 = array();
$links = new links($db);
$links->load();
foreach($or_skiplist_arr2 as $orword) {
  $source2 = str_replace($orword, '', $source2, $count);

  if ($count !== 0) {
    $display_arr1[] = $orword;
  }
}

// Detect for AKAs. If found, store its head not what appeared in the source.
$things = new things($db);
$things->load();
$display_arr2 = array();
foreach($display_arr1 as $candidate) {
  $display = '';
  $tag = $things->getTagFor($candidate, true);
  if ($tag === false) {
    // Text doesn't exist in db. Shouldn't get here, given we've arrived from
    // or-words?
    $display_arr2[] = "*** or-words entry doesnt appear in database? " .
      ">$candidate< ***";
    continue;

  } else {
    foreach($links->db as $link) {
      if ($link->predicate() === PREDICATE_AKA_OF &&
	  $link->subject() === $tag) {
	$objtag = $link->object();
	$thing = $things->getThingFromTag($objtag);
	$display = $thing->getTextAndNuance($objtag);
	break;
      }
    }
  }
  if ($display === '') {
    // Then this is not an AKA
    $display = $candidate;
  }
  $display_arr2[] = mb_strtolower($display);
}

// Roll up duplicates
$display_arr3 = array_unique($display_arr2);

// And display
foreach($display_arr3 as $text) {
  echo "$text\n";
}
?>
