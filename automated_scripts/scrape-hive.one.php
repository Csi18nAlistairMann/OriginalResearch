<?php

/*
  scrape-hive.one.php <database> [<file>]

  Scrape names/twitter links from manual copies of the hive.one website.

  Program assumes you are in the parent directory to this script where the
  scrapes are stored.

  1. Visit hive.one/bitcoin
  2. Full Screen
  3. Page down to bottom so we see 500, not 50
  4. Ctrl-a | Ctrl-C
  5. Place in source file ./scrapes/hive.one.input.ddmmmyyy.btc
  6. Remove items from start down to but excluding name 1 (Adam Back)
  7. Remove items from "HIVE" down (short copy) or "End of list" down (long
  copy)
  8. Run this script eg
  php ./automated_scripts/scrape-hive.one.php btc \
   ./scrapes/hive.one.input.01jan1971.btc
  9. Repeat above for https://hive.one/ethereum
  10. Repeat above for https://hive.one/crypto (1000, not 500)
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/effort02_class.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');

$effort = new effort02;
$sourcefile = '';

// Fetch DB being handled
if ($argc > 3) {
  $effort->err(__FILE__, "expected one or two arguments");
  var_dump($argc);
  exit;

} else {
  $projname = escapeshellarg($argv[1]);
  $sourcefile = $argv[2];
}

$source = file_get_contents($sourcefile);

$things = new things($projname);
$things->load();

while(strlen($source) > 0) {
  list($rank, $person, $handle, $score, $following, $followers, $change,
       $rest) =
    explode("\n", $source, 8);
  $name = trim($person);

  // Add twitter handle to database if we don't already have it. If we DO have
  // it, make sure it's connected to the Twitter thing etc
  $twitter_url = TWITTER_ROOT . trim(trim($handle), '@');
  $ttr_existing_tag = $things->getTagFor($twitter_url);
  if ($ttr_existing_tag === false) {
    $ttr_thing_type = escapeshellarg(TYPE_TEST_THING);
    $ttr_thing_ts = escapeshellarg(date(TIMESTAMP_FORMAT));
    $ttr_thing_uploader = escapeshellarg(STANDARD_USER);
    $ttr_thing_name = escapeshellarg($twitter_url);
    $ttr_thing_nuance = escapeshellarg('');
    $ttr_thing_tag = escapeshellarg($things->getNewTag($ttr_thing_name));
    $predicate = escapeshellarg(DUPES_NOT_OK);

    shell_exec("php api/thing_add.php $projname $ttr_thing_type " .
	       "$ttr_thing_tag $ttr_thing_ts $ttr_thing_uploader " .
	       "$ttr_thing_name $ttr_thing_nuance $predicate");
    $things->load();
    shell_exec("php automated_scripts/mandatory-connect-urls-to-things.php " .
	       "$projname $ttr_thing_tag");

  } else {
    $ttr_thing_tag = $ttr_existing_tag;
    shell_exec("php automated_scripts/mandatory-connect-urls-to-things.php " .
	       "$projname $ttr_thing_tag");
  }

  // Do we already have this guys proper name?
  $people_tag = escapeshellarg($things->getTagFor('People'));
  $existing_name_tag = escapeshellarg($things->getTagFor($name));
  if ($existing_name_tag === "''") {
    // Tag not found.
    print_r("DIY -- Don't have exact match for '$name' - DIY\n");
    // add a thing under this name, and link it in
    // 1. add in the name as thing we dont already have, get back its tag
    $newname_thing_type = escapeshellarg(TYPE_TEST_THING);
    $newname_thing_ts = escapeshellarg(date(TIMESTAMP_FORMAT));
    $newname_thing_uploader = escapeshellarg(STANDARD_USER);
    $newname_thing_name = escapeshellarg($name);
    $newname_thing_nuance = escapeshellarg('');
    $newname_thing_tag = escapeshellarg($things->getNewTag($newname_thing_name));
    $predicate = escapeshellarg(DUPES_NOT_OK);

    $output = shell_exec("php api/thing_add.php $projname " .
			 "$newname_thing_type $newname_thing_tag " .
			 "$newname_thing_ts $newname_thing_uploader " .
			 "$newname_thing_name $newname_thing_nuance " .
			 "$predicate");

    $things->load();
    $output = shell_exec("php automated_scripts/mandatory-connect-urls-to-things.php " .
			 "$projname $newname_thing_tag");
    $predicate = escapeshellarg(PREDICATE_LINKS);

    // 2. link that tag to the twitter link received above
    shell_exec("php api/link_add.php $projname $ttr_thing_tag $predicate  " .
	       "$newname_thing_tag");
    // And also link that new name to the People tag
    shell_exec("php api/link_add.php $projname $people_tag $predicate " .
	       "$newname_thing_tag");

    // 3. Does the twitter handle we have already itself link to someone who is
    //    linked to People? If so, this newname we just added is probably an
    //    AKA
    //
    // - take twitter tag
    // - retrieve all links with that tag
    // - for each links
    //   - retrieve a Thing
    //   - retrieve all links to that thing
    //   - if one of those links is to tag People
    //     - then this is an aka of it
    $links = new links($projname);
    $links->load();
    $links1db = $links->filter($ttr_thing_tag);
    foreach($links1db as $link1) {
      $links2db = $links->filter($link1);
      foreach($links2db as $link2) {
	if ($link2 === $people_tag) {
	  if ($link1 !== $newname_thing_tag) {
	    $esc_link1 = escapeshellarg($link1);
	    $predicate = escapeshellarg(PREDICATE_AKA_OF);
	    shell_exec("php api/link_add.php $projname $esc_link1 $predicate " .
		       "$newname_thing_tag");
	  }
	}
      }
    }

  } else {
    // Tag IS found.
    // As we already have the link, this call will ultimately return having
    // not added the link
    $predicate = escapeshellarg(PREDICATE_LINKS);
    shell_exec("php api/link_add.php $projname $existing_name_tag " .
	       "$predicate $people_tag");
    shell_exec("php api/link_add.php $projname $existing_name_tag " .
	       "$predicate $ttr_thing_tag");
  }
  print_r($rank . ' ' . $twitter_url . ' ' . $name . ' ' . "\n");

  $source = $rest;
}

?>
