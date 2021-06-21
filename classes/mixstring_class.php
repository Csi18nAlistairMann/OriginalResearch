<?php

/*
  mixstring

  dialog(1) uses the command line to accept parameters, and so creates a
  character limit. Should the items to be shown overun that limit dialog(1)
  crashes silently so we'd like some method of shortening them.

  While a simple text string could be shortened, we would run into difficulty
  with escaping quotes: it becomes tricky to track whether we are inside or
  outside which quote: 'tag' 'this is a '\''text'\'' string'

  Enter the mixstring. Rather than create meaning around the obvious single
  quotes this new structure defines runs of characters that may or may not be
  editable.
  While the mixstring should be agnostic, for OriginalResearch in practice each
  object:
  - refers to one tag and menu pair for dialog(1) menu items
  - refers to successive pairs of uneditable then editable characters. That is,
  flows as data[UNEDITABLE][0], data[EDITABLE][0], data[UNEDITABLE][1],
  data[EDITABLE][1] ... data[UNEDITABLE][n], data[EDITABLE][n].
  - must start with an uneditable length, using "" if required

  If the menu item would be 'abc123' 'this is the string\'s contents' then we
  would stored "'abc123' '" as an uneditable length, "this is the string\'s
  contents" as an editable length, and "'" as a final uneditable length:
  data[UNEDITABLE] = array("'abc123' '", "'")
  date[EDITABLE] = array("this is the string\'s contents")

  In this manner the editable lengths could be sacrificed in whole or in part
  to get the overall length down.
 */

require_once('classes/cli_class.php');

class mixstring
{
  private $data;
  private $lens;

  function __construct() {
    $this->data = array(UNEDITABLE => array(),
			ESCUNEDITABLE => array(),
			EDITABLE => array(),
			ESCEDITABLE => array());
    $this->lens = array(UNEDITABLE => 0,
			ESCUNEDITABLE => 0,
			EDITABLE => 0,
			ESCEDITABLE => 0);
  }

  // Shorten the first escaped editable run to no more than max
  function shorten($max) {
    if ($this->lens[ESCEDITABLE] <= $max)
      return;

    if (sizeof($this->data[ESCEDITABLE]) > 1) {
      var_dump(__CLASS__ . '::' . __FUNCTION__ . '() notices more than one ' .
	       'escaped editable run. You may like to code up support');
      sleep(10);
    }

    // Get the current string and lengths
    $orig = $this->data[EDITABLE][0];
    $origsz = strlen($orig);
    $eorigsz = strlen($this->data[ESCEDITABLE][0]);
    $diff = $eorigsz - $origsz;

    // Make allowance for escaping
    $max -= $diff;
    if ($max < 10)
      $max = 10;

    // Form the new unescaped string and calc its length
    $edit = substr($orig, 0, $max - 3);
    $edit .= "...";
    $editsz = strlen($edit);

    // Form the new escaped string, and calc its length
    $escedit = cli::escape($edit);
    $esceditsz = strlen($escedit);

    // Update the existing entries to suit
    $this->data[EDITABLE][0] = $edit;
    $this->lens[EDITABLE] = $this->lens[EDITABLE] - $origsz + $editsz;
    $this->data[ESCEDITABLE][0] = $escedit;
    $this->lens[ESCEDITABLE] = $this->lens[ESCEDITABLE] - $eorigsz + $esceditsz;
  }

  // Add a new run of text of a particular type, and if it's editable also
  // record what the escaped version of that text should be. Update our record
  // of what the overall length will be
  function add($text, $type) {
    $this->data[$type][] = $text;
    $this->lens[$type] += strlen($text);
    if ($type === EDITABLE) {
      $esctext = cli::escape($text);
      $this->data[ESCEDITABLE][] = $esctext;
      $this->lens[ESCEDITABLE] += strlen($esctext);

    } elseif ($type === UNEDITABLE) {
      // note assumption that uneditable lengths do not need escaping. For
      // OriginalResearch that means tags
      $this->data[ESCUNEDITABLE][] = $text;
      $this->lens[ESCUNEDITABLE] += strlen($text);
    }
  }

  // Return the various runs concatenated together, starting with the first
  // uneditable run, and alternating with the escaped uneditable run
  // thereafter.
  function getUnescaped() {
    $rv = '';
    $un_sz = sizeof($this->data[UNEDITABLE]);
    $ed_sz = sizeof($this->data[EDITABLE]);
    $sz = ($un_sz > $ed_sz) ? $un_sz : $ed_sz;
    for ($a = 0; $a < $sz; $a++) {
      if ($a < $un_sz)
	$rv .= $this->data[UNEDITABLE][$a];
      if ($a < $ed_sz)
	$rv .= $this->data[EDITABLE][$a];
    }
    return $rv;
  }

  function getEscaped() {
    $rv = '';
    $un_sz = sizeof($this->data[ESCUNEDITABLE]);
    $ed_sz = sizeof($this->data[ESCEDITABLE]);
    $sz = ($un_sz > $ed_sz) ? $un_sz : $ed_sz;
    for ($a = 0; $a < $sz; $a++) {
      if ($a < $un_sz)
	$rv .= $this->data[ESCUNEDITABLE][$a];
      if ($a < $ed_sz)
	$rv .= $this->data[ESCEDITABLE][$a];
    }
    return $rv;
  }

  // We separately track what the mixstring's lengths for each type are. Return
  // that internal record here
  function strlen($which) {
    return $this->lens[$which];
  }
}
?>
