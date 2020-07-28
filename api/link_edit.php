<?php

/*
  link_add

  Entry point for CLI requests to edit a simple link
 */

require_once('classes/effort02_class.php');
require_once('classes/links_class.php');

$effort = new effort02;

// What we're searching for and what we'll link it to
$rv = 1;
if ($argc !== 5) {
  $effort->err(__FILE__, "expected four arguments");

} else {
  $projname = $argv[1];
  $subject = $argv[2];
  $predicate = $argv[3];
  $object = $argv[4];

  $links = new links($projname);
  $links->load();

  $found = false;
  foreach($links->db as $link) {
    if (($link->subject() === $subject && $link->object() === $object)
	||
	($link->object() === $subject && $link->subject() === $object)) {
      $link->subject($subject);
      $link->predicate($predicate);
      $link->object($object);
      $links->save();
      break;
    }
  }
}

return $rv;

?>
