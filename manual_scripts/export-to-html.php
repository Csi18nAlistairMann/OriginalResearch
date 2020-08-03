<?php

/*
  export-to-html

  Dump the database to html
*/

require_once('defines.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;

// Handle arguments from command line
if ($argc !== 3) {
  $effort->err(__FILE__, "Bad args? [project name] [destination dir]");
  exit;

} else {
  $projname = $argv[1];
  $destdir = $argv[2];
}

// Get all the Things
$things = new things($projname);
$things->load();

// Get all the Links
$links = new links($projname);
$links->load();

// Construct an A element for subject or object
function getAHref($things, $ject) {
  $ject2 = str_replace('?', '%3F',  $ject);
  $rv = '<a href="./' . $ject2 . '.html">';
  foreach($things->db as $thing) {
    if ($thing->tag() === $ject) {
      $rv .= "($ject) " . $thing->getTextAndNuance();
    }
  }
  $rv .= '</a>';
  return $rv;
}

// One html page per Thing
foreach($things->db as $thing) {
  $html = '<html><head></head>';
  $html .= '<body>';
  $html .= '<div>' . $thing->getTextAndNuance() . '</div>';
  $html .= "\n";
  $html .= getAHRef($things, '?') . "<br>\n";
  foreach($links->db as $link) {
    $aka = '';
    if ($link->predicate() === PREDICATE_AKA_OF)
      $aka .= 'AKA ';
    if ($link->subject() === $thing->tag()) {
      $html .= $aka;
      $html .= getAHref($things, $link->object());
      $html .= "<br>\n";

    } elseif ($link->object() === $thing->tag()) {
      $html .= $aka;
      $html .= getAHref($things, $link->subject());
      $html .= "<br>\n";
    }
  }
  $html .= '</body></html>';
  file_put_contents($destdir . htmlentities($thing->tag()) . '.html', $html);
}
?>
