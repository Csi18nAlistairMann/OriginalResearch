<?php

/*
  add-aka

  obtain from the user the name of something he wants to add
  Do a search on that name
  Let him choose to aka a discovered entry to this thing
  Or create a new thing with it and aka that to this thing
*/

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/akas_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog_search = new dialog;
$dialog_found = new dialog;

// What we're searching for and what we'll aka it to
if ($argc === 2) {
  $thing_to_add_to = 0;

} else {
  $projname = $argv[1];
  $thing_to_add_to = $argv[2];
}

// obtain the name to be searched for/added
$dialog_search->sizes_change(MENU_SZ_SHORT);
$dialog_search->input = 'Which tag should be an AKA?';
$search_phrase = $dialog_search->show();

$things = new things($projname);
$things->load();
$found_arr = array();
$exact_arr = array();
$lsearch_phrase = strtolower($search_phrase);
foreach($things->db as $item) {
  // search on what we want to aka to this tag
  $litem_name = strtolower($item->tag());
  if ($litem_name === $lsearch_phrase) {
    $exact_arr[] = $item;
  }
}

// we have a list of things it COULD be
$n = 1;
$output_choices = '';
$crib = array();
$ALREADYPRESENT = 1;
$AKATOOLD = 2;
if (sizeof($exact_arr)) {
  foreach($exact_arr as $item) {
    $dialog_found->choice_add($n, $item->text() . ' (' . $item->tag() . ') already present');
    $crib[] = array($n, $item->text(), $item->tag(), $ALREADYPRESENT);
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
  $effort->err(__FILE__, "Cancelled");
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
  $effort->err(__FILE__, "was expected one chosen item");
  exit;

} else {
  if ($chosen[0][3] !== $ALREADYPRESENT && $chosen[0][3] !== $AKATOOLD) {
    $effort->err(__FILE__, "unexpected chosen data");
    exit;

  } else {
    $thing_tag = $chosen[0][2];
  }

  // aka it in
  $akas = new akas($projname);
  $akas->load();
  $found = false;
  foreach($akas->db as $item) {
    if (($item->from() === $thing_to_add_to && $item->to() === $thing_tag)
	||
	($item->to() === $thing_to_add_to && $item->from() === $thing_tag)) {
      $found = true;
    }
  }
  if ($found === false) {
    shell_exec("php api/aka_add.php \"$projname\" \"$thing_to_add_to\" \"$thing_tag\"");
    $akas->load();
  }
}

// and now aka the thing back to the original thing
$effort->whatToShowNext($thing_tag);
?>
