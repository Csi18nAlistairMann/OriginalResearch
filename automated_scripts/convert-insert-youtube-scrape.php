<?php

/*
  convert-insert-youtube-scrape.php <database>

  This script reads a datafile from STDIN that contains space-seperated tags
  on its first line, and then one or more URLs and titles on successive lines
  with "> being used to seperate each URL and title.

  Script calls a helper that will insert each URL as a Thing with the its
  title as the Nuance. Having done so, that Thing will be linked to each of
  the tags mentioned on the first line.
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 4) {
  $msg = "Expected three arguments\n";
  $result = 1;
  goto completed;

} else {
  $projname = escapeshellarg($argv[1]);
  $subdir = $argv[2];
  $eptag_unesc = $argv[3];
  $eptag_esc = escapeshellarg($eptag_unesc);
}

// Obtain the data on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  $source = trim(stream_get_contents(STDIN));
}

// First line contains tags to be used
$arr = explode("\n", $source, 2);
$args = escapeshellarg($arr[0]);
$source = $arr[1];

// Successive lines contain youtube URLs, followed by ">, and then the title
$vids_arr = explode("\n", $source);
foreach($vids_arr as $line) {
  $line = trim($line);
  if ($line !== '') {
    $pair = explode('">', $line);
    if (sizeof($pair) !== 2) {
      $msg = "Line '$line' appears invalid\n";
      $result = 1;
      goto completed;
    }

    $url = $pair[0];
    $title_unesc = $pair[1];

    // Extract any #NNN
    $a = strpos($title_unesc, '#');
    if ($a !== false) {
      $c = $b = ++$a;
      while($b < strlen($title_unesc) && $title_unesc[$b] !== ' ') {
	$b++;
      }
      while($c < strlen($title_unesc) && $title_unesc[$c] !== ':') {
	$c++;
      }
      $c++;
      $b = ($b <= $c) ? $b : $c;
      $epno = substr($title_unesc, $a, --$b - $a);
      $ep = $eptag_unesc . $epno;

    } else {
      // With no #NNN we may have a date instead
      $a = preg_match("/.*(20[0-9]{2}.[0-9]{2}.[0-9]{2}).*/", $title_unesc, $matches);
      if ($a) {
	$ep = $eptag_unesc . $matches[1];

      } else {
	// With no episode number format the title in its place
	$ep = $title_unesc;
	$ep = str_replace('/', '&#47;', $ep);
	$title_unesc = $ep;
	echo "Using '$title_unesc' instead of epno\n";
      }
    }

    // Create a list of work to be done
    $work_arr[$ep] = array($title_unesc, $url, $subdir);
  }
}

// By sorting based on the Episode we assist in earlier episodes being inserted
// before later episodes. This looks a bit better in OR which defaults to
// showing items in the order in which they were added
ksort($work_arr, SORT_NATURAL);

// Process the work to be done arranging for the archive and URL to be stored
// then arranging for the items to be added to the database
foreach($work_arr as $ep_unesc => list($title_unesc, $url, $subdir)) {
  if (! is_dir(ARCHIVE_DIR . "$subdir/$ep_unesc/")) {
    // Youtube doesn't necessarily have the episode number so we don't attempt
    // to softlink to title/episodeno here
    mkdir(ARCHIVE_DIR . "$subdir/$ep_unesc/");
  }
  file_put_contents(ARCHIVE_DIR . "$subdir/$ep_unesc/media.url", $url);
  file_put_contents(ARCHIVE_DIR . "$subdir/$ep_unesc/title.cleaned", $title_unesc);

  // Now make sure the episode is available as a tag
  $rv_tag = array();
  $title_esc = escapeshellarg($title_unesc);
  $ep_esc = escapeshellarg($ep_unesc);

  exec("php api/get_tag_for_thing.php $projname THINGONLY $ep_esc", $rv_tag);

  if ($rv_tag[0] === ERR_BAD_ARGS_T) {
    echo __FILE__ . ':' . __LINE__ . ": Bad arguments found among:\n";
    echo ("php api/get_tag_for_thing.php $projname THINGONLY $ep_esc\n");
    goto completed;

  } else if ($rv_tag[0] === 'Too few matches') {
    exec("php automated_scripts/add-thing-nuance-tags.php $projname " .
	 "$ep_esc $title_esc $args", $output, $rv);
    $rv_tag = array();
    exec("php api/get_tag_for_thing.php $projname THINGONLY $ep_esc", $rv_tag);
    if ($rv_tag[0] === ERR_BAD_ARGS_T) {
      echo __FILE__ . ':' . __LINE__ . ": Bad arguments found among:\n";
      echo ("php api/get_tag_for_thing.php $projname THINGONLY $ep_esc\n");
      goto completed;
    }
  }

  // And make sure the URL gets added in, with its title as nuance
  $url_esc = escapeshellarg($url);
  $rv = '';
  $output = '';

  exec("php automated_scripts/add-thing-nuance-tags.php $projname " .
       "$url_esc $title_esc '" . $rv_tag[0] . "'", $output, $rv);

  if ($title_esc !== "''")
    echo "$title_esc\n";
  else
    echo "$ep_esc\n";

  if ($rv !== 0) {
    print_r($output);
    exit($rv);
  }
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);
?>
