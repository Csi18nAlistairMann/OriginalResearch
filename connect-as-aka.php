<?php

/*
  connect-as-aka

  Given the $object Thing as provided by ::wereLookingAt(), and the $subject
  Thing, cause $subject to become an AKA of $object.
*/

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/links_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog_search = new dialog;
$dialog_found = new dialog;

// What we're searching for and what we'll aka it to
if ($argc === 3) {
  $thing_to_add_to = 0;

} else {
  $projname = escapeshellarg($argv[1]);
  $subject = escapeshellarg($argv[2]);
  $object = $argv[3];
  $esc_object = escapeshellarg($object);
}

// AKA it in
$links = new links($projname);
$links->load();
$found = false;
$predicate = escapeshellarg(PREDICATE_AKA_OF);
foreach($links->db as $item) {
  if (($item->subject() === $object && $item->object() === $subject)
      ||
      ($item->object() === $object && $item->subject() === $subject)) {
    shell_exec("php api/link_edit.php $projname $subject $predicate " .
	       "$esc_object");
    $found = true;
    break;
  }
}
if ($found === false) {
  shell_exec("php api/link_add.php $projname $subject $predicate " .
	     "$esc_object");
}

// Next show the object of the AKA
$effort->whatToShowNext($object);
?>
