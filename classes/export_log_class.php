<?php

/*
  export_log
  export_log_item

  The user examines the log for the moment of a recent change whose timestamp
  is used to engage the actual export action elsewhere.

  The log itself is thus an array of items ordered by the timestamp of each.

  An item itself tags on the timestamp which allows dialog(1) to return the
  timestamp chosen, as well as a text entry which alludes to what happened at
  that point.
 */

class export_log_item
{
  private $data;
  private $tag;
  private $text;
  private $timestamp;

  function __construct() {
    $this->data = array();
  }

  function tag($val = null) {
    if ($val === null)
      return $this->tag;
    else
      $this->tag = $val;
  }

  function text($val = null) {
    if ($val === null)
      return $this->text;
    else
      $this->text = $val;
  }

  function timestamp($val = null) {
    if ($val === null) {
      return $this->timestamp;

    } else {
      if (strlen($val) !== 14)
	$val = substr($val . "00000000000000", 0, 14);
      $this->timestamp = $val;
    }
  }

  function getData() {
    $rv = '';
    foreach($this->data as list($entry, $type))
      $rv .= $entry;
    return $rv;
  }
}

class export_log
{
  public $log = array();

  // Splice in an item such that more recent timestamps appear first
  function addItem($item) {
    for($index = 0; $index < sizeof($this->log); $index++) {
       if ($this->log[$index]->timestamp() < $item->timestamp()) {
	break;
      }
    }
    array_splice($this->log, $index, 0, array($item));
  }
}
?>
