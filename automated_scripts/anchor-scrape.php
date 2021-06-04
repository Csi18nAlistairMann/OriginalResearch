<?php

/*
  anchor-scrape.php

  Given the Tales From The Crypt anchor.fm channel page create and populate
  such dirs as reflects episodes available
*/

mb_internal_encoding("UTF-8");
require_once('defines.php');

$msg = '';
$result = 0;

// Fetch and check arguments
if ($argc !== 1) {
  $msg = "Expected one argument\n";
  $result = 1;
  goto completed;
}

// Receive the html on STDIN
if (posix_isatty(STDIN)){
  $msg = "No source data provided?\n";
  $result = 1;
  goto completed;

} else {
  $source = trim(stream_get_contents(STDIN));
}

// Main loop
$episodes_found = 0;
$a = $b = 0;
do {
  // We crib on '<a class="css-1x85xji"' to following </a>
  $a = strpos($source, 'class="css-1x85xji"', $b);
  if ($a !== false) {
    // Extract the data for just this episode
    $episodes_found++;

    // Find the closing </a> tag and extract just that text
    $b = strpos($source, '</a>', $a);
    $item = substr($source, $a, $b - $a);

    // Extract the anchor.fm URL for this episode
    $href_a = strpos($item, 'href="');
    if ($href_a === false) {
      echo "Item doesn't include an href? Aborting\n";
      echo ">>$item<<";
      goto completed;
    }
    $href_a += strlen('href="');
    $href_b = strpos($item, '"', $href_a);
    $href = 'https://anchor.fm';
    $href .= substr($item, $href_a, $href_b - $href_a);

    // Extract the title for this episode
    $titlea = strpos($item, '<div>');
    if ($titlea === false) {
      echo "Item doesn't include a div? Aborting\n";
      echo ">>$item<<";
      goto completed;
    }
    $titlea += strlen('<div>');
    $titleb = strpos($item, '</div>', $titlea);
    $title = substr($item, $titlea, $titleb - $titlea);

    // The title SHOULD match up with the local dirs. Check
    // This matches tftc-youtube-scrape.sh and would be better done
    // using an API to abstract access
    $localdir = '';
    if (strpos($title, 'Rabbit') !== false)
      $localdir = 'tftc/rhr';
    elseif (strpos($title, 'RHR Week') !== false)
      $localdir = 'tftc/rhr';
    elseif (strpos($title, 'Guide') !== false)
      $localdir = 'tftc/guides';
    elseif (strpos($title, 'Tales from the') !== false)
      $localdir = 'tftc/episodes';
    elseif (preg_match('/.*#[0-9]+:.*/', $title) !== 0) {
      $localdir = 'tftc/episodes';
    } elseif (preg_match('/.*Ep[0-9]+.*/', $title) !== 0)
      $localdir = 'tftc/episodes';
    elseif (strpos($href,
		   "Bent Straight - Marty's Bent Issue #730: Mining Basics")
	    !== false) {
      // Youtube 3NQ_d3kr6u0
      // Doesn't appear at anchor.fm
      $localdir = 'tftc/other';
    } elseif (strpos($href, 'Mahmudov-e4js8k') !== false)  {
      // Youtube 6ZmYx3_-Frc
      $localdir = 'tftc/other';
    } elseif (strpos($href, 'Hasufly-e29un1') !== false) {
      // Youtube hISvHhQI_ck
      $localdir = 'tftc/other';
    }

    // Handle and clean up the local directory to use
    if ($localdir === '') {
      // If we can't work our where it should go, inform user. You probably
      // want to update the elseif cascade above
      echo "***** $title not matched: please resolve and rerung\n";
      continue;
    }
    $localdir .= '/';

    // Extract Episode number.
    $eptag = 'TFTC';
    $ep_a = strpos($title, '#');
    if ($ep_a !== false) {
      // Episode number comes from '#' in the title. Terminate at the earlier
      // of ':' and ' '
      $ep_c = $ep_b = ++$ep_a;
      while($ep_b < strlen($title) && $title[$ep_b] !== ' ') {
	$ep_b++;
      }
      while($ep_c < strlen($title) && $title[$ep_c] !== ':') {
	$ep_c++;
      }
      $ep_c++;
      $ep_b = ($ep_b <= $ep_c) ? $ep_b : $ep_c;
      $epno = substr($title, $ep_a, --$ep_b - $ep_a);
      $ep = $eptag . $epno;

    } else {
      // With no #NNN we may have a date instead
      $ep_a = preg_match("/.*(20[0-9]{2}.[0-9]{2}.[0-9]{2}).*/", $title, $matches);
      if ($ep_a) {
	$ep = $eptag . $matches[1];

      } else {
	// With no episode number format the title in its place
	$ep = $title;
	$ep = str_replace('/', '&#47;', $ep);
	$title = $ep;
      }
    }

    // Detect if we already have this as a dir
    if (!is_dir(ARCHIVE_DIR . $localdir . $ep)) {
      // Don't have it - so make it
      mkdir(ARCHIVE_DIR . $localdir . $ep);
    }

    // Populate directory with anchor.title and anchor.url
    file_put_contents(ARCHIVE_DIR . $localdir . $ep . '/anchor.url', $href);
    file_put_contents(ARCHIVE_DIR . $localdir . $ep . '/anchor.title', $title);
  }
} while ($a !== false);

// Notify user if crib vanishes
if ($episodes_found === 0) {
  echo "Unable to locate 'css-1x85xji' crib. Aborting\n";
  goto completed;
}

completed:
if ($msg !== '')
  echo($msg);
exit($result);

?>
