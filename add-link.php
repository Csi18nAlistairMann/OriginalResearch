<?php

/*
  add-link

  Obtain from the user the name of something he wants to add
  Do a search on that name
  Let user choose to link a discovered entry to this Thing
  Or create a new thing with it and link that to this Thing
*/

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog_search = new dialog;
$dialog_found = new dialog;

// What we'll link to
if ($argc === 2) {
  $thing_to_add_to = 0;

} else {
  $projname = $argv[1];
  $thing_to_add_to = $argv[2];
}

// obtain the name to be searched for/added
$dialog_search->sizes_change(MENU_SZ_SHORT);
$dialog_search->input = 'Search/Add on what name?';
$search_phrase = $dialog_search->show();

// Establish what might match the user's input
$things = new things($projname);
$things->load();
$found_arr = array();
$exact_arr = array();
$lsearch_phrase = strtolower($search_phrase);
foreach($things->db as $item) {
  // search on what we want to link to this tag
  $litem_name = strtolower($item->text());
  if ($litem_name === $lsearch_phrase) {
    $exact_arr[] = $item;

  } elseif (strpos($litem_name, $lsearch_phrase) !== false) {
    $found_arr[] = $item;
  }
}

// Construct a dialog menu of matches
$n = 1;
$output_choices = '';
$crib = array();
$dialog_found->choice_add($n, $search_phrase . ' add as new');
$ASNEW = 0;
$ALREADYPRESENT = 1;
$LINKTOOLD = 2;
$crib[] = array($n, $search_phrase, 0, $ASNEW);
$n++;
if (sizeof($exact_arr)) {
  foreach($exact_arr as $item) {
    $dialog_found->choice_add($n, $item->text() . ' (' . $item->tag() .
			      ') already present');
    $crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
    $n++;
  }
}

if (sizeof($found_arr)) {
  foreach($found_arr as $item) {
    $dialog_found->choice_add($n, $item->text() . ' (' . $item->tag() .
			      ') link to old');
    $crib[] = array($n, $item->text(), $item->tag(), $LINKTOOLD);
    $n++;
  }
}

// now show the dialog
$thing_type = 'thing type';
$thing_id = -1;
$dialog_found->title = $thing_type . ' #' . $thing_id;
$thing_text = 'thing text';
$dialog_found->menu = $thing_text;
$output = $dialog_found->show();

if ($output === '') {
  print_r("Cancelled");
  exit;
}

// what got chosen?
$chosen = array();
foreach($crib as $item) {
  if ($item[0] == $output) {
    $chosen[] = $item;
    break;
  }
}

if (sizeof($chosen) !== 1) {
  $effort->err(__FILE__, "expected one chosen item");
  exit;

} else {
  if ($chosen[0][2] === $ASNEW) {
    // if we're adding a new thing do that first
    $thing_type = TYPE_TEST_THING;
    $thing_ts = date(TIMESTAMP_FORMAT);
    $thing_uploader = STANDARD_USER;
    $thing_name = $chosen[0][1];
    $thing_nuance = '';
    $thing_tag = $things->getNewTag($thing_name);
    shell_exec("php api/thing_add.php \"$projname\" \"$thing_type\" " .
	       "\"$thing_tag\" \"$thing_ts\" \"$thing_uploader\" " .
	       "\"$thing_name\" \"$thing_nuance\"");
    shell_exec("php automated_scripts/mandatory-connect-urls-to-things.php " .
	       "\"$projname\" \"$thing_tag\"");
    $things->load();

  } elseif ($chosen[0][3] !== $ALREADYPRESENT &&
	    $chosen[0][3] !== $LINKTOOLD) {
    $effort->err(__FILE__, "unexpected chosen data");
    exit;

  } else {
    $thing_tag = $chosen[0][2];
  }

  // link it in
  $links = new links($projname);
  $links->load();
  $found = false;
  foreach($links->db as $item) {
    if (($item->from() === $thing_to_add_to && $item->to() === $thing_tag)
	||
	($item->to() === $thing_to_add_to && $item->from() === $thing_tag)) {
      $found = true;
    }
  }
  if ($found === false) {
    shell_exec("php api/link_add.php \"$projname\" \"$thing_to_add_to\" " .
	       "\"$thing_tag\"");
    $links->load();
  }
}

// and now link the thing back to the original thing
$effort->whatToShowNext($thing_tag);
?>
