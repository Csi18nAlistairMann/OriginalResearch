<?php

/*
  search

  Obtain from the user the name of something he wants to search for and return
  to him the results of it
*/

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog_question = new dialog;
$dialog_found = new dialog;

// Do a search on that name
// Let him choose to link a discovered entry to this thing
// Or create a new thing with it and link that to this thing

// What we're searching for and what we'll link it to
if ($argc === 2) {
  $thing_to_search_for = 0;

} else {
  $projname = $argv[1];
  $thing_to_search_for = $argv[2];
}

// obtain the name to be searched for/added
$dialog_question->sizes_change(MENU_SZ_SHORT);
$dialog_question->input = 'Search on what name?';
$search_phrase = $dialog_question->show();

if ($search_phrase === '') {
  // cancelled
  $thing_tag = $effort->wereLookingAt();
  $effort->whatToShowNext($thing_tag);

} else {
  // search on that phrase
  $things = new things($projname);
  $things->load();
  $found_arr = array();
  $exact_arr = array();
  $exact_tag_arr = array();
  $lsearch_phrase = strtolower($search_phrase);
  foreach($things->db as $item) {
    $exact_arr2 = $item->textIs($projname, $lsearch_phrase);
    $found_arr2 = $item->textStrPos($lsearch_phrase);

    if (sizeof($exact_arr2) > 0) {
      $exact_arr = array_merge($exact_arr, $exact_arr2);

    } elseif (sizeof($found_arr2) > 0) {
      $found_arr = array_merge($found_arr, $found_arr2);
    }
    $tag = strtolower($item->tag());
    if ($tag === $lsearch_phrase)
      $exact_tag_arr[] = $item;
  }

  // $ASNEW = 0;
  $ALREADYPRESENT = 1;
  $LINKTOOLD = 2;
  if (sizeof($exact_arr) == 0 && sizeof($found_arr) == 0 &&
      sizeof($exact_tag_arr) == 0) {
    // search came up empty.
    $crib = array();
    $tag = 'S';
    $notfound = '-- No matches found. Search again? --';
    $dialog_found->choice_add($tag, $notfound);

    $crib[] = array(null, $notfound, $tag, $LINKTOOLD);

  } else {
    // search found something
    $n = 1;
    $crib = array();

    if (sizeof($exact_tag_arr)) {
      foreach($exact_tag_arr as $item) {
	$dialog_found->choice_add($n, $item->getTextAndNuance() . ' (' .
				  $item->tag() . ') tag match');
	$crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
	$n++;
      }
    }

    if (sizeof($exact_arr)) {
      foreach($exact_arr as $item) {
	$dialog_found->choice_add($n, $item->getTextAndNuance() . ' (' .
				  $item->tag() . ') exact match');
	$crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
	$n++;
      }
    }

    if (sizeof($found_arr)) {
      foreach($found_arr as $item) {
	$dialog_found->choice_add($n, $item->getTextAndNuance() . ' (' .
				  $item->tag() . ') close match');
	$crib[] = array($n, $item->text(), $item->tag(), $LINKTOOLD);
	$n++;
      }
    }
  }

  // now show the dialog
  $thing_type = 'thing type';
  $thing_id = -1;
  $dialog_found->title = $thing_type . ' #' . $thing_id;
  $dialog_found->menu = 'thing text';
  $dialog_found->sizes_change(MENU_SZ_LONG);
  $output = $dialog_found->show();

  if ($output === '') {
    $thing_tag = $effort->wereLookingAt();
    $effort->whatToShowNext($thing_tag);

  } else {
    // what got chosen?
    $chosen = array();
    foreach($crib as $item) {
      if ($item[0] == $output) {
	$chosen[] = $item;
	break;
      }
    }

    if (sizeof($chosen) === 0) {
      // get here on cancel

    } elseif (sizeof($chosen) !== 1) {
      $effort->err(__FILE__, "expected at least one chosen item");

    } else {
      if ($chosen[0][3] !== $ALREADYPRESENT && $chosen[0][3] !== $LINKTOOLD) {
	$effort->err(__FILE__, "chosen item doesn't appear acceptable");
	exit;

      } else {
	$thing_tag = $chosen[0][2];
	// and now link the thing back to the original thing
	$effort->whatToShowNext($thing_tag);
      }
    }
  }
}

?>
