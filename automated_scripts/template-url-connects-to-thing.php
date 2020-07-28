<?php

/*
  template-url-connects-to-thing

  Given a URL and a tag, any Thing which starts with that URL should
  mandatorily also link to that tag.

  For example, a Thing which starts "https://twitter.com/" must automatically
  be linked to tag "Twitter" whether or not the user has already done it.
  (Although if he has, this code will not repeat it.)

  This code will apply the rules to a single URL and tag and is called by
  mandatory-connect-urls-to-things.php
*/

// argv[1] will contain a pathandfile that contains the arguments to look at,
// or is "ALL".

require_once('defines.php');
require_once('classes/effort02_class.php');
require_once('classes/links_class.php');
require_once('classes/things_class.php');
require_once('classes/automated_cribs_class.php');

$effort = new effort02;

// What tag will we show? 0 for 'first available'
$rv = 0;
if ($argc !== 5) {
  $effort->err(__FILE__, "Bad arguments. \"projname\" [ALL|<newline " .
	       "delimited file>] <Thing name> <URL root>\n");
  $rv = 1;
}

$tag_arr = array();
$projname = $argv[1];
$paf = $argv[2];
$tagname = $argv[3]; // eg, Twitter
$urlroot = $argv[4]; // eg, https://twitter.com

$things = new things($projname);
$things->load();

if ($rv === 0) {
  if ($paf === "ALL") {
    $tag_arr = $things->getUniqueTags();

  } elseif (file_exists($paf)) {
    $contents = file_get_contents($paf);
    $split_contents = explode("\n", $contents);
    foreach($split_contents as $a) {
      $a = trim($a);
      $a = trim($a, '\'"');
      if ($a !== '') {
	$tag_arr[] = $a;
      }
    }

  } else {
    print_r("Arg not a path and file or 'ALL'");
    $rv = 1;
  }
}

if ($rv === 0) {
  $links = new links($projname);
  $links->load();
  $thingtag = $things->getTagFor($tagname);
  if ($thingtag === false) {
    // Can't find the tag? This is a new database so add it in
    $thing_type = TYPE_TEST_THING;
    $thing_ts = date(TIMESTAMP_FORMAT);
    $thing_uploader = STANDARD_USER;
    $thing_name = $tagname;
    $thing_nuance = '';
    $thing_tag = $things->getNewTag($thing_name);
    shell_exec("php api/thing_add.php \"$projname\" \"$thing_type\" " .
	       "\"$thing_tag\" \"$thing_ts\" \"$thing_uploader\" " .
	       "\"$thing_name\" \"$thing_nuance\"");
    $thingtag = $thing_tag;
  }

  $changes = 0;
  $automated_cribs = new automated_cribs($projname);
  $automated_cribs->load();
  $thing = new thing($projname);
  foreach($tag_arr as $tag) {
    $thing = $things->getThingFromTag($tag);
    if ($thing === null) {
      // Bodge to accomodate that the new thing was not added on the grounds it
      // would be a duplicate

    } else {
      $tag_last_checked = $automated_cribs->getTagLastChecked($tag);
      if ($tag_last_checked === null ||
	  $tag_last_checked <= $thing->timestamp()) {;
	$automated_cribs->setTagLastChecked($tag, $thing->timestamp());
	if (strpos($thing->text(), $urlroot) !== false) {
	  if ($links->linkTags($tag, PREDICATE_LINKS, $thingtag) == 0)
	    $changes++;
	}
      }
    }
  }
  print($changes . ' changes made');
}

?>
