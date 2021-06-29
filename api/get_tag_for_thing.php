<?php

/*
  get_tag_for_thing.php

  Obtain from the user the name of something he wants to search for and return
  to him the results of it. We restrict this endpoint to single matches: more
  or fewer count as errors.
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/effort02_class.php');

$PWD=getcwd();
chdir(INSTALL_DIR);

$effort = new effort02;

// What we're searching for in which database
if ($argc !== 4) {
  $out = print_r($argv, true);
  file_put_contents('/tmp/fedde', $out);
  $msg = ERR_BAD_ARGS_T;
  $rv = 1;
  goto completed;
}

$projname = escapeshellarg($argv[1]);
$search_what = $argv[2];
$search_phrase = $argv[3];

// obtain the name to be searched for
if ($search_phrase === '') {
  // cancelled
  $msg = 'No search phrase';
  $rv = 1;
  goto completed;
}

// Search on that phrase.
// Note we don't filter just yet
$things = new things($projname);
$things->load();
$links = new links($projname);
$links->load();
$found_arr = array();
$exact_arr = array();
$exact_tag_arr = array();
$lsearch_phrase = mb_strtolower($search_phrase);
foreach($things->db as $item) {
  if ($item->deleted() === true)
    continue;
  $exact_arr2 = $item->textIs($lsearch_phrase, $things, $links);
  $found_arr2 = $item->textStrPos($lsearch_phrase);

  if (sizeof($exact_arr2) > 0) {
    // Handle if the entirety of the Thing matches the search phrase
    $exact_arr = array_merge($exact_arr, $exact_arr2);

  } elseif (sizeof($found_arr2) > 0) {
    // Handle if the search phrase merely appears within the Thing
    $found_arr = array_merge($found_arr, $found_arr2);
  }
  $tag = mb_strtolower($item->tag());
  if ($tag === $lsearch_phrase)
    // Handle if the search phrase appears as an whole tag
    $exact_tag_arr[] = $item;
}

// Now we do the filtering
$ALREADYPRESENT = 1;
$LINKTOOLD = 2;
$n = 1;
$crib = array();
if (!(sizeof($exact_arr) == 0 && sizeof($found_arr) == 0 &&
      sizeof($exact_tag_arr) == 0)) {
  // search found something
  if ($search_what === 'ALL' || $search_what === 'THINGONLY') {
    if (sizeof($exact_arr)) {
      foreach($exact_arr as $item) {
	$crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
	$n++;
      }
    }
  }

  if ($search_what === 'ALL' || $search_what === 'TAGONLY') {
    foreach($exact_tag_arr as $item) {
      $crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
      $n++;
    }
  }

  if ($search_what === 'ALL') {
    if (sizeof($found_arr)) {
      foreach($found_arr as $item) {
	$crib[] = array($n, $item->text(), $item->tag(), $LINKTOOLD);
	$n++;
      }
    }
  }
}

if ($n - 1 < 1) {
  $msg = "Too few matches\n";
  $rv = 1;

} elseif ($n - 1 > 1) {
  $msg = "Too many matches\n";
  $rv = 1;

} else {
  $msg = $crib[0][2];
  $rv = 0;
}

completed:
chdir($PWD);
echo $msg;
exit ($rv);
?>
