<?php

/*
   common-scrape.php

   Code common to cleaning scrapes of:
   - Stephan Livera Podcast
   - What Bitcoin Did
*/

// Used for <a ... <p ... and <span ... tags to remove them and their contents
// whatever those might be, upto the following >
function clean_tag($source, $match) {
  do {
    $found = false;
    $a = stripos($source, $match);
    if ($a !== false) {
      $found = true;
      $b = stripos($source, '>', $a);
      $source = substr($source, 0, $a) . substr($source, $b + 1);
    }
  } while ($found === true);
  return $source;
}

// Clean transcript of html, variety of whitespace, and others
function clean_text($source) {
  // Process text
  // Handle spans
  $source = clean_tag($source, '<span ');
  $source = str_replace('<span>', '', $source);
  $source = str_replace('</span>', '', $source);
  // Handle breaks
  $source = str_replace('<br>', "\n", $source);
  // Remove titles
  $source = str_replace('<title>', '', $source);
  // Remove paragraph tags
  $source = clean_tag($source, '<p ');
  $source = str_replace('<p>', '', $source);
  $source = str_replace('</p>', '', $source);
  // Some paragraph tags are used for newlines (WBD)
  // Remove strong tags
  $source = str_replace('<strong>', '', $source);
  $source = str_replace('</strong>', '', $source);
  // Remove list tags
  $source = str_replace('<ul>', '', $source);
  $source = str_replace('</ul>', '', $source);
  $source = str_replace('<ol>', '', $source);
  $source = str_replace('</ol>', '', $source);
  $source = str_replace('<li>', '', $source);
  $source = str_replace('</li>', '', $source);
  // Non-breaking space to normal space
  $source = str_replace('&nbsp;', ' ', $source);
  // Handle html-ised characters
  $source = html_entity_decode($source);
  // Fold up newlines to at most two
  $count = 0;
  do {
    $source = str_replace("\n\n\n", "\n\n", $source, $count);
  } while($count);
  // Fold up newlines after a colon to at most one
  do {
    $source = str_replace(":\n\n", ":\n", $source, $count);
  } while($count);
  // Handle tabs
  $source = str_replace("\t", ' ', $source);
  // Fold up spaces to at most one
  do {
    $source = str_replace("   ", "  ", $source, $count);
  } while($count);
  // Handle showy quotes
  $source = str_replace("’", "'", $source);
  $source = str_replace("“", '"', $source);
  $source = str_replace("”", '"', $source);
  // Handle A tags
  $source = clean_tag($source, '<a ');
  $source = str_replace('</a>', '', $source);
  // Handle others
  $source = str_replace("…", '...', $source);
  // Handle leads in and out
  $source = trim($source);

  return $source;
}

?>
