<?php

/*
  add-thing-nuance-tags.php <db> <Thing> <Nuance|''> [<tag1> ... <tagN>]

  Receive exactly 1 Thing, with exactly 1 Nuance, and zero, one or more tags
  Add Thing and Nuance to the db given
  Connect the new Thing just added to each tag provided
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');

$result = 0;
$msg = '';

// Fetch and check arguments
if ($argc < 4) {
  $msg = "Expected at least three arguments\n";
  $result = 1;
  goto completed;

} else {
  $projname = escapeshellarg($argv[1]);
  $name = $argv[2];
  $nuance = $argv[3];
  $tags_arr = [];
  if ($argc > 4) {
    $count = 4;
    do {
      $tags_arr[] = $argv[$count];
    } while ($count++ < $argc - 1);
  }
}

$things = new things($projname);
$things->load();

// Make sure the tags actually exist. If not, abort and warn.
$anymissing = array();
foreach($tags_arr as $tag) {
  $existing_record = $things->getThingFromTag($tag);
  if ($existing_record === NULL) {
    $anymissing[] = $tag;
  }
}
if (sizeof($anymissing) > 0) {
  $msg = 'The following tag(s) are missing from the database:';
  foreach($anymissing as $missing) {
    $msg .= ' "' . $missing . '"';
  }
  $msg .= "\nand will need to be added before script can complete\n";
  $result = 1;
  goto completed;
}

// Add Thing to database. If we already have it, this will update the database
// with the nuance should it be different. Keep ahold the tag used.
$existing_tag = $things->getTagFor($name);
$nuance_is_same = null;
if ($existing_tag !== false) {
  // Determine if we have this Thing already, and if so, what its nuance is
  $record = $things->getThingFromTag($existing_tag);
  $nuance_is_same = ($record->nuance() === $nuance) ? true : false;
  $thing_tag = $existing_tag;

} else {
  // If we've not got this Thing already, add it
  $thing_type = escapeshellarg(TYPE_TEST_THING);
  $thing_ts = escapeshellarg(date(TIMESTAMP_FORMAT));
  $thing_uploader = escapeshellarg(STANDARD_USER);
  $thing_name = escapeshellarg($name);
  $thing_nuance = escapeshellarg($nuance);
  $thing_tag = escapeshellarg($things->getNewTag($thing_name));
  $predicate = escapeshellarg(DUPES_NOT_OK);

  shell_exec("php api/thing_add.php $projname $thing_type " .
	     "$thing_tag $thing_ts $thing_uploader " .
	     "$thing_name $thing_nuance $predicate");
  $things->load();
  shell_exec("php automated_scripts/mandatory-connect-urls-to-things.php " .
	     "$projname $thing_tag");
  $existing_tag = $thing_tag;
}
if ($nuance_is_same === false) {
  // If we do have it with a different nuance, edit the new one in
  $record->nuance($nuance);
  $things->save();
  $existing_tag = $record->tag();
}

// Now link the Thing to each additional Tag provided in the arguments.
foreach($tags_arr as $tag) {
  $existing_record = $things->getThingFromTag($tag);
  if ($existing_record === NULL) {
    // Tag not found. Shouldn't get here as error checked above
    $msg = "Tag '$tag' not found - incorrect?\n";
    $result = 1;
    goto completed;

  } else {
    // Tag found.
    $predicate = escapeshellarg(PREDICATE_LINKS);
    shell_exec("php api/link_add.php $projname $existing_tag " .
	       "$predicate $tag");
  }
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);
?>
