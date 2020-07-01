<?php

/*
  link_add

  Entry point for CLI requests to add a simple link
 */

require_once('classes/effort02_class.php');
require_once('classes/links_class.php');

$effort = new effort02;

// What we're searching for and what we'll link it to
$rv = 1;
if ($argc !== 4) {
  $effort->err(__FILE__, "expected three arguments");

} else {
  $projname = $argv[1];
  $from = $argv[2];
  $tag = $argv[3];

  $links = new links($projname);
  $links->load();

  $found = false;
  foreach($links->db as $link) {
    if (($link->from() === $from && $link->to() === $tag)
	||
	($link->to() === $from && $link->from() === $tag)) {
      $found = true;
    }
  }
  if ($found === false) {
    $link = new link;
    $link->from($from);
    $link->to($tag);
    $links->db[] = $link;
    $rv =  $links->save();
  }
}

return $rv;

?>
