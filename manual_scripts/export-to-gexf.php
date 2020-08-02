<?php

/*
  export-to-gexf

  Dump the database in the gexf file format
*/

require_once('defines.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

// Not all characters legal in an XML label. This ahead of finding out which
function esc($v) {
  $v = str_replace('&', '', $v);
  $v = str_replace("'", '', $v);
  $v = str_replace('"', '', $v);
  return $v;
}

$effort = new effort02;

// Handle arguments from command line
if ($argc !== 2) {
  $effort->err(__FILE__, "Bad args? [project name]");
  exit;

} else {
  $projname = $argv[1];
}

// Get all the Things
$things = new things($projname);
$things->load();

// Get all the Links
$links = new links($projname);
$links->load();

// Remove any links without subject or object
for($l = 0; $l < sizeof($links->db); $l++) {
  if ($links->db[$l]->subject() === '' || $links->db[$l]->object() === '') {
    array_splice($links->db, $l, 1);
    $l--;
  }
}

// Remove any links whose subject or object don't have a match in Things
for($l = 0; $l < sizeof($links->db); $l++) {
  $subject = $links->db[$l]->subject();
  $object = $links->db[$l]->object();
  $foundSF = false;
  $foundOF = false;
  foreach($things->db as $thing) {
    if ($thing->tag() === $subject) {
      $foundSF = true;
    }
    if ($thing->tag() === $object) {
      $foundOF = true;
    }
    if ($foundSF === true || $foundOF === true) {
      break;
    }
  }
  if ($foundSF === false || $foundOF === false) {
    array_splice($links->db, $l, 1);
    $l--;
  }
}

// I suspect that OR tags may contain characters disallowed by the identifiers
// in XML. Replace each Thing tag with a successive integer, and having done
// so, replace occurences of that tag in Links with that integer
$identifier = 0;
for($a = 0; $a < sizeof($things->db); $a++) {
  $tag = $things->db[$a]->tag();
  for($b = 0; $b < sizeof($links->db); $b++) {
    if ($links->db[$b]->subject() === $tag) {
      $links->db[$b]->subject($identifier);
    }
    if ($links->db[$b]->object() === $tag) {
      $links->db[$b]->object($identifier);
    }
  }
  $things->db[$a]->tag($identifier);
  $identifier++;
}

// Now act on exclusions and create the nodes data
$nodes_arr = array();
for($a = 0; $a < sizeof($things->db); $a++) {
  if (substr($things->db[$a]->text(), 0, strlen(TWITTER_ROOT)) ===
      TWITTER_ROOT) {
    $identifier = $things->db[$a]->tag();

    array_splice($things->db, $a, 1);
    $a--;

    for($b = 0; $b < sizeof($links->db); $b++) {
      if ($links->db[$b]->subject() === $identifier) {
	array_splice($links->db, $b, 1);
	$b--;
      }
      if ($links->db[$b]->object() === $identifier) {
	array_splice($links->db, $b, 1);
	$b--;
      }
    }
  } else {
    $nodes_arr[] = array($things->db[$a]->tag(), $things->db[$a]->text());
  }
}

// Now cleaning up
// AKAs: if a link indicates an AKA remove the subject from Things, and
// exclude from the links going forward
$links3 = array();
foreach($links->db as $link) {
  if ($link->predicate() === PREDICATE_AKA_OF) {
    // The head is always the object
    $subject_tag = $link->subject();
    for($a = 0; $a < sizeof($nodes_arr); $a++) {
      if ($nodes_arr[$a][0] === $subject_tag) {
	array_splice($nodes_arr, $a, 1);
	$a--;
      }
    }
    continue;
  }
  $links3[] = $link;
}

// Create the edges data
$edges_arr = array();
$identifier = 0;
foreach($links3 as $link) {
  $edges_arr[] = array($identifier, $link->subject(), $link->object());
  $identifier++;
}

// Output the gexf XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<gexf xmlns="http://www.gephi.org/gexf" ' .
'xmlns:viz="http://www.gephi.org/gexf/viz">' . "\n";
echo '<graph type="static">' . "\n";

echo '<nodes>' . "\n";
foreach($nodes_arr as $node) {
  echo '<node id="' . $node[0] . '" label="' . esc($node[1]) . '" />' . "\n";
}
echo '</nodes>' . "\n";

echo '<edges>' . "\n";
foreach($edges_arr as $edge) {
  echo '<edge id="' . $edge[0] . '" source="' . $edge[1] . '" target="' .
    $edge[2] . '" />' . "\n";
}
echo '</edges>' . "\n";

echo '</graph>' . "\n";
echo '</gexf>' . "\n";
?>
