<?php

/*
  export-to-gexf

  Dump the database in a form gephi will accept
*/

require_once('defines.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

function debug($a, $txt) {
  if (0) {
    print_r($a . "----\n");
    var_dump($txt);
  }
}

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

// Remove Links without subject or object
debug('1', 'Remove empty links');
for($l = 0; $l < sizeof($links->db); $l++) {
  if ($links->db[$l]->subject() === '' || $links->db[$l]->object() === '') {
    debug('1', $links->db[$l]);
    array_splice($links->db, $l, 1);
    $l--;
  }
}

// Roll up AKAs: make whatever points at an AKA instead point at the object
// of that AKA. So: apple -> Bob --aka--> Robert to apple -> Robert
debug('2', 'Roll up AKAs');
for($l1 = 0; $l1 < sizeof($links->db); $l1++) {
  if ($links->db[$l1]->predicate() === PREDICATE_AKA_OF) {
    $subj_aka = $links->db[$l1]->subject();
    $obj_aka = $links->db[$l1]->object();
    for($l2 = 0; $l2 < sizeof($links->db); $l2++) {
      if ($l2 !== $l1) {
	if ($links->db[$l2]->subject() === $subj_aka) {
	  // AKA points at Link at l1
	  debug('2', 'Change ' . print_r($links->db[$l2], true) .
		' subject to ' . $obj_aka);
	  $links->db[$l2]->subject($obj_aka);
	}
	if ($links->db[$l2]->object() === $subj_aka) {
	  // Link at l1 points at AKA
	  debug('2', 'Change ' . print_r($links->db[$l2], true) .
		' object to ' . $obj_aka);
	  $links->db[$l2]->object($obj_aka);
	}
      }
    }
  }
}

// Remove any Things which have zero links
debug('3', 'Remove orphan Things');
$min_links = 0;
for($t = 0; $t < sizeof($things->db); $t++) {
  $tag = $things->db[$t]->tag();
  $num_links = 0;
  for($l = 0; $l < sizeof($links->db); $l++) {
    if ($links->db[$l]->subject() === $tag) {
      $num_links++;
    }
    if ($links->db[$l]->object() === $tag) {
      $num_links++;
    }
    if ($num_links > $min_links) {
      break;
    }
  }
  if ($num_links === $min_links) {
    debug('3', 'Discard ' . print_r($things->db[$t], true));
    array_splice($things->db, $t, 1);
    $t--;
  }
}

// Remove orphan Links: their subject or object don't have a match in Things
debug('4', 'Remove orphan Links');
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
    if ($foundSF === true && $foundOF === true) {
      break;
    }
  }
  if ($foundSF === false || $foundOF === false) {
    debug('4', 'Discard link ' . print_r($links->db[$l], true));
    array_splice($links->db, $l, 1);
    $l--;
  }
}

// Now act on exclusions
debug('5', 'Remove exclusions');
for($t = 0; $t < sizeof($things->db); $t++) {
  if (substr($things->db[$t]->text(), 0, strlen(TWITTER_ROOT)) ===
      TWITTER_ROOT) {
    $identifier = $things->db[$t]->tag();

    debug('5', 'Discard thing ' . print_r($things->db[$t], true));
    array_splice($things->db, $t, 1);
    $t--;

    for($l = 0; $l < sizeof($links->db); $l++) {
      if ($links->db[$l]->subject() === $identifier) {
	debug('5', 'Subject match: discard ' .
	      print_r($links->db[$l], true));
	array_splice($links->db, $l, 1);
	$l--;
      } elseif ($links->db[$l]->object() === $identifier) {
	debug('5', 'Object match: discard ' .
	      print_r($links->db[$l], true));
	array_splice($links->db, $l, 1);
	$l--;
      }
    }
  }
}

// Now cleaning up
// AKAs: if a link indicates an AKA remove the subject from Things, and
// exclude from Links
debug('6', 'Remove AKAs');
for($l = 0; $l < sizeof($links->db); $l++) {
  if ($links->db[$l]->predicate() === PREDICATE_AKA_OF) {
    // The head is always the object
    $subject_tag = $links->db[$l]->subject();
    for($t = 0; $t < sizeof($things->db); $t++) {
      if ($things->db[$t]->tag() === $subject_tag) {
	debug('6', 'Discard ' .
	      print_r($things->db[$t], true));
	array_splice($things->db, $t, 1);
	$t--;
	debug('6', ' and discard ' . print_r($links->db[$l], true));
	array_splice($links->db, $l, 1);
	$l--;
      }
    }
  }
}

// Deal with parallel links as might occur
debug('7', 'Remove parallel links');
for($l1 = 0; $l1 < sizeof($links->db); $l1++) {
  for($l2 = $l1; $l2 < sizeof($links->db); $l2++) {
    if ($l1 !== $l2) {
      if ($links->db[$l1] == $links->db[$l2]) {
	debug('7', 'Removing parallel ' . print_r($links->db[$l2], true));
	array_splice($links->db, $l2, 1);
	$l2--;
      }
    }
  }
}

// I suspect that OR tags may contain characters disallowed by the identifiers
// in XML. Replace each Thing tag with a successive integer, and having done
// so, replace occurences of that tag in Links with that integer
$identifier = 0;
for($t = 0; $t < sizeof($things->db); $t++) {
  $tag = $things->db[$t]->tag();
  for($l = 0; $l < sizeof($links->db); $l++) {
    if ($links->db[$l]->subject() === $tag) {
      $links->db[$l]->subject($identifier);
    }
    if ($links->db[$l]->object() === $tag) {
      $links->db[$l]->object($identifier);
    }
  }
  $things->db[$t]->tag($identifier);
  $identifier++;
}

// Output the gexf XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<gexf xmlns="http://www.gephi.org/gexf" ' .
'xmlns:viz="http://www.gephi.org/gexf/viz">' . "\n";
echo '<graph type="static">' . "\n";

echo '<nodes>' . "\n";
foreach($things->db as $thing) {
  echo '<node id="' . $thing->tag() . '" label="' .
    esc($thing->getTextAndNuance()) . '" />' . "\n";
}
echo '</nodes>' . "\n";

echo '<edges>' . "\n";
$id = 0;
foreach($links->db as $link) {
  echo '<edge id="' . $id++ . '" source="' . $link->subject() . '" target="' .
    $link->object() . '" />' . "\n";
}
echo '</edges>' . "\n";

echo '</graph>' . "\n";
echo '</gexf>' . "\n";
?>
