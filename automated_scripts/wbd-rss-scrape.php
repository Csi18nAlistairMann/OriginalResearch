<?php

/*
  wbd-rss-scrape.php

  A script to extract episodes from the What Bitcoin Did RSS feed
*/

mb_internal_encoding("UTF-8");
require_once('defines.php');

$msg = '';
$result = 0;
$work = array();
define('WBD_THING', 'What Bitcoin Did');

// Fetch and check arguments
if ($argc !== 2) {
  $msg = "Expected two arguments\n";
  $result = 1;
  goto completed;

} else {
  $projname = $argv[1];
}

// Obtain the data on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  // Read in the index page
  $source1 = trim(stream_get_contents(STDIN));
}

// Obtain the tag for What Bitcoin Did, put it in if we haven't already got it
$rv = array();
exec("php " . INSTALL_DIR . "api/get_tag_for_thing.php '$projname' THINGONLY " .
     "'" . WBD_THING . "'", $rv);
$wbd_tag = $rv[0];
if ($wbd_tag === ERR_BAD_ARGS_T) {
  echo __FILE__ . ':' . __LINE__ . " Bad arguments detected among:\n";
  echo ("php " . INSTALL_DIR . "api/get_tag_for_thing.php '$projname' " .
	"THINGONLY '" . WBD_THING . "'");
  goto completed;

} else if ($wbd_tag === 'Too few matches') {
  $rv = array();
  exec("php " . INSTALL_DIR . "automated_scripts/add-thing-nuance-tags.php " .
       "'$projname' '" . WBD_THING . "' '' '?'");
  exec("php api/get_tag_for_thing.php '$projname' THINGONLY " .
       "'" . WBD_THING . "'", $rv);
  $wbd_tag = $rv[0];
  if ($wbd_tag === ERR_BAD_ARGS_T) {
    echo __FILE__ . ':' . __LINE__ . ": Bad arguments detected among:\n";
    echo ("php api/get_tag_for_thing.php '$projname' THINGONLY '" . WBD_THING .
	  "'");
    goto completed;
  }
}

// The RSS is XML containing a series of structures that describe each episode
// each of which we want to get into the database
$a = $b = 0;
do {
  $found = false;
  $a = strpos($source1, '<item>', $a);
  if ($a !== false) {
    $title = '';
    $media = '';

    // Each transcript is contained in an <item> pair
    $found = true;
    $a += strlen('<item>');
    $b = strpos($source1, '</item>', $a);
    $item = substr($source1, $a, $b - $a);

    // Extract the link to the show itself
    $c = strpos($item, '<link>');
    $c += strlen('<link>');
    $d = strpos($item, '</link>', $c);
    $link = trim(substr($item, $c, $d - $c));

    // Extract the title of the transcript
    $c = strpos($item, '<title>');
    $c += strlen('<title>');
    $d = strpos($item, '</title>', $c);
    $title = trim(substr($item, $c, $d - $c));

    // Extract the URL of the mp3
    $e = strpos($item, '<enclosure url="');
    $e += strlen('<enclosure url="');
    $f = strpos($item, '"', $e);
    $media = trim(substr($item, $e, $f - $e));

    // Extract the episode name
    $g = $h = strpos($media, '.mp3');
    while($media[--$g] !== '/');
    $g++;
    $epname = trim(substr($media, $g, $h - $g));

    // Note work for later, don't do just yet as we want to do earliest first.
    $work[] = array($media, $projname, $epname, $title, $wbd_tag, $link);
  }
} while ($found === true);

// We have in $work a series of work orders that first establish a Thing
// followed by the tags to which that new Thing should connect. We also then
// allow for additional Things which should be added themselves linked to the
// Thing first entered here. As we can't know what tag that first thing will
// received, we use a placeholder to reference it.
//
// The rss keeps its links in reverse order, so use array_pop to get it back to
// normal order. This gives each later thing a later tag
while(sizeof($work)) {
  $run = array_pop($work);

  // Add Thing to OR then make a local note of the mp3's URL.
  $mp3_url = $run[0];
  $database = escapeshellarg($run[1]);
  $epname_unesc = $run[2];
  $epname_esc = escapeshellarg($epname_unesc);
  $title_unesc = $run[3];
  $titledir = $title_unesc;
  $title = escapeshellarg($title_unesc);
  $wbd_tag = escapeshellarg($run[4]);
  $link = escapeshellarg($run[5]);

  // Dump data in local files so it can be later done manually
  $titledir = str_replace('/', '&#47;', $titledir);
  if (!is_dir(WBD_DIR . "$epname_unesc") && !is_link(WBD_DIR . "$titledir")) {
    // If we have neither, mkdir EP and ln TITLE
    mkdir(WBD_DIR . "$epname_unesc");
    symlink(WBD_DIR . "$epname_unesc", WBD_DIR . "$titledir");

  } else if (is_dir(WBD_DIR . "$epname_unesc") && !is_link(WBD_DIR . "$titledir")) {
    // If yes EP and no TITLE, ln TITLE
    symlink(WBD_DIR . "$epname_unesc", WBD_DIR . "$titledir");

  } else if (!is_dir(WBD_DIR . "$epname_unesc") && is_dir(WBD_DIR . "$titledir")) {
    // If no EP but yes TITLE, ln EP
    symlink(WBD_DIR . "$titledir", WBD_DIR . "$epname_unesc");
  }

  // Populate directory
  exec("echo '$link' >'" . WBD_DIR . "$epname_unesc/episode.url'");
  exec("echo '$mp3_url' >'" . WBD_DIR . "$epname_unesc/mp3-from-rss.url'");
  exec("echo $title >'" . WBD_DIR . "$epname_unesc/original.title'");

  // Add in a Thing for the episode, such as WBD013 (per website)
  $rv = array();
  exec("php automated_scripts/add-thing-nuance-tags.php $database " .
       "$epname_esc $title $wbd_tag");
  exec("php api/get_tag_for_thing.php $database THINGONLY '$epname_esc'", $rv);
  $ep_tag = $rv[0];
  if ($ep_tag === ERR_BAD_ARGS_T) {
    echo __FILE__ . ':' . __LINE__ . ": Bad arguments detected among:\n";
    echo ("php api/get_tag_for_thing.php $database THINGONLY '$epname_esc'");
    goto completed;
  }

  // Add in a Thing for the title (per youtube) linking both to channel and
  // episode
  $rv = array();
  exec("php automated_scripts/add-thing-nuance-tags.php $database " .
       "$title '' $wbd_tag $ep_tag");
  exec("php api/get_tag_for_thing.php $database THINGONLY $title", $rv);
  $title_tag = $rv[0];
  if ($title_tag === ERR_BAD_ARGS_T) {
    echo __FILE__ . ':' . __LINE__ . ": Bad arguments detected among:\n";
    echo ("php api/get_tag_for_thing.php $database THINGONLY $title");
    goto completed;
  }

  // Now add the mp3 and show URL to the episode Thing
  exec("php automated_scripts/add-thing-nuance-tags.php $database " .
       "$link $title $ep_tag");
  exec("php automated_scripts/add-thing-nuance-tags.php $database " .
	    "$mp3_url $title $ep_tag");
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);
?>
