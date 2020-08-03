<?php

/*
  edit-thing

  Given thing N, allow user to edit its name
*/

require_once('defines.php');
require_once('classes/dialog_common.php');
require_once('classes/things_class.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$dialog = new dialog;

// What tag will we delete?
if ($argc !== 3) {
  $effort->err(__FILE__, "expected three arguments");

} else {
  $projname = $argv[1];
  $record_to_delete = $argv[2];
}

// work through the things we have looking for the tag or the
// first thing with a tag
$things = new things($projname);
$things->load();
$n = 0;
foreach($things->db as $item) {
  if (strval($item->tag()) === "$record_to_delete") {
    $record_idx = $n;
  }
  $n++;
}

// Retrieve all the data about our particular thing
$thing_type = $things->db[$record_idx]->type();
$thing_id = $things->db[$record_idx]->tag();
$thing_ts = $things->db[$record_idx]->timestamp();
$thing_user = $things->db[$record_idx]->user();
$thing_name = $things->db[$record_idx]->text();
$thing_nuance = $things->db[$record_idx]->nuance();
$thing_text = $things->db[$record_idx]->getTextAndNuance();
$thing_text .= " (User:$thing_user @:$thing_ts)";

// now show the dialog
$dialog->sizes_change(MENU_SZ_SHORT);
$dialog->yesno = 'Are you sure? Delete:' . "\n$thing_text";
$dialog->defaultno = true;
$thing_name = $dialog->show();

if ($thing_name !== 1) {
  // Remove the record, and save it
  array_splice($things->db, $record_idx, 1);
  $things->save();
}
$effort->whatToShowNext('?');

?>
