<?php

/*
  show-thing

  Call up a Thing and display it, along with such menus as required
 */

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog = new dialog;

// What tag will we show? 0 for 'first available'
if ($argc !== 3) {
  $effort->err(__FILE__, "Bad args? [project name] [record to show]");
  $record_to_show = 0;

} else {
  $projname = $argv[1];
  $record_to_show = $argv[2];
}

// What were we looking at?
$were_looking_at_tag = $effort->wereLookingAt();

// Work through the Things we have looking for the tag or the
// first thing with a tag
$things = new things($projname);
$things->load();
if ($record_to_show === 0) {
    $record_idx = 0;

} else {
  $n = 0;
  $record_idx = null;
  foreach($things->db as $item) {
    if (strval($item->tag()) === "$were_looking_at_tag") {
      $were_looking_at_name = $item->text();
    }
    if (strval($item->tag()) === "$record_to_show") {
      $record_idx = $n;
    }
    $n++;
  }
  if ($record_idx === null) {
    // We ended up here because we haven't already got the
    // record on file
    $effort->err(__FILE__, "Did not find a tag match on '$record_to_show'");
    $effort->err(__FILE__, "Were looking for: $were_looking_at_tag");
  }
}

// Retrieve all the data about our particular thing
$thing_type = $things->db[$record_idx]->type();
$thing_id = $things->db[$record_idx]->tag();
$thing_ts = $things->db[$record_idx]->timestamp();
$thing_user = $things->db[$record_idx]->user();
$thing_name = $things->db[$record_idx]->text();
$thing_nuance = $things->db[$record_idx]->nuance();
$thing_text = $things->db[$record_idx]->getTextAndNuance();
$thing_text .= " (User:$thing_user @:$thing_ts)";

$effort->wereLookingAt($thing_id);

// What does $thing_id connect to? What connects to it?
$links = new links($projname);
$links->load();
$connections = array();
$connections = array_merge($links->filter($thing_id), $connections);

// prepare the links for showing, with a default for no links
$output_choices = '';
$akas_text = '';
if (sizeof($connections) !== 0) {
  // for each thing we have
  foreach($things->db as $item) {
    foreach($connections as $connection) {
      // if that thing appears in the connection list
      if ($item->tag() === $connection) {
	if ($item->tag() !== '?') {
	  // then include it in the menu
	  $l = $links->getLinkFromTags($thing_id, $connection);
	  if ($l !== null) {
	    if ($l->predicate() === PREDICATE_LINKS) {
	      $pred_text = '- ';
	    } elseif ($l->predicate() === PREDICATE_AKA_OF) {
	      $pred_text = '* ';
	      $akas_text .= $connection . ', ';
	    } else {
	      // Shouldn't get here
	      $pred_text = '!!';
	    }
	    $dialog->choice_add($connection, $pred_text .
				$item->getTextAndNuance());
	  }
	}
      }
    }
    $n++;
  }
  if (strlen($akas_text) > 0) {
    $akas_text = ' aka:[' . substr($akas_text, 0, -2) . '] ';
  }
  $dialog->choice_add('?', 'Top Level');
} else {
  // If we don't link to anything? Mock it up for now
  $dialog->choice_add('1', 'cat');
  $dialog->choice_add('2', 'dog');
}
// Add menu items common to all Things
$dialog->choice_add('/', 'Add thing');
$dialog->choice_add('{', 'Add AKA');
$dialog->choice_add('.', 'Edit thing');
$dialog->choice_add('RV', 'View reviews');
$dialog->choice_add('RA', 'Add review');
$dialog->choice_add('RM', 'Mark reviewed');
$dialog->choice_add('S', 'Search');
$dialog->choice_add('[', 'Edit nuance');
$last = $things->getThingFromTag($were_looking_at_tag);
if ($last !== null) {
  $dialog->choice_add(']', 'Make this an AKA of "' .
		      $last->getTextAndNuance() . '" (' .
		      $were_looking_at_tag . ')');
  $dialog->choice_add('>', 'Break link to (' . $were_looking_at_tag . ') ' .
		      $were_looking_at_name);
}
$dialog->choice_add('-', 'Delete this thing');

// now show the dialog
$dialog->title = $thing_type . ' #' . $thing_id;
$dialog->menu = $thing_text . $akas_text;
$dialog->show_cancel = false;
$output = $dialog->show();

// and now link the thing back to the original thing
if ($output !== '') {
  $effort->whatToShowNext($output);
}
?>
