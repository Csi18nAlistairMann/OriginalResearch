<?php

/*
  dialog_cmd

  dialog(1) uses the command line to receive parameters: in dialog_cmd we look
  to store those parameters in an array of mixstrings.

  Creating an object this way supports the use of paging menu items, and help
  manage overly long menu items by shortening some with regard to the length of
  others.
*/

require_once("classes/mixstring_class.php");

class dialog_cmd
{
  private $mixstrings_arr;

  function __construct() {
    $this->mixstrings_arr = array();
  }

  // Notice assumption that the mixstring holds just one editable item
  function addSimplePair($tag, $text) {
    $ms = new mixstring;
    $ms->add("'" . $tag . "' '", UNEDITABLE);
    $ms->add($text, EDITABLE);
    $ms->add("'", UNEDITABLE);
    $this->mixstrings_arr[] = $ms;
  }

  // Return the escaped length because it's the longer of the two
  function getOverallLen() {
    $overall = 0;
    for($a = $this->getNumItems() - 1; $a >= 0; $a--) {
      $ms = $this->getItemN($a);
      $overall += strlen($ms->getEscaped());
    }
    return $overall;
  }

  function getSubset($start, $len) {
    $dcmd = new dialog_cmd;
    for($a = $start; $a < $start + $len; $a++) {
      $dcmd->appendItem($this->getItemN($a));
    }
    return $dcmd;
  }

  function getItemN($n) {
    if (sizeof($this->mixstrings_arr) < $n) {
      var_dump("Error: attempt to get an item that doesn't exist?");
      sleep(120);
      return;
    }
    // clone necessary as otherwise return references same in-memory object
    return clone ($this->mixstrings_arr[$n]);
  }

  function appendItem($item) {
    $this->mixstrings_arr[] = $item;
  }

  function getNumItems() {
    return sizeof($this->mixstrings_arr);
  }

  // Go through each of the menu items and request it be shortened. This will
  // only affect runs of text marked editable
  function shorten($overhead, $hard_max) {
    $lastoverall = -1;
    $overall = $this->getOverallLen();

    // It's here that we check if the cmd line is too long.
    while ($overall + $overhead > $hard_max) {
      // Discover longest and second longest lengths of escaped editable text
      $longest = 0;
      $nextlongest = 0;
      foreach($this->mixstrings_arr as $ms) {
	$len = $ms->strlen(ESCEDITABLE);
	if ($len > $longest) {
	  $nextlongest = $longest;
	  $longest = $len;
	}
      }

      // If there's no second longest, then force REALLY short before use
      if ($nextlongest === 0 && $longest > 20)
	$nextlongest = $longest - 10;

      // Error if still can't shorten
      if ($nextlongest === 0) {
	var_dump("Error - can't even force REALLY short");
	sleep(120);
	return;
      }

      // Try to shorten all longest to length of second longest, and exit on
      // first sufficient to meet total length requirements
      $lastoverall = $overall;
      foreach($this->mixstrings_arr as $ms) {
	$ms->shorten($nextlongest);
	$overall = $this->getOverallLen();

	if ($overall + $overhead <= $hard_max)
	  return;
      }

      // A new overall length shouldn't be longer than previously seen
      if ($overall > $lastoverall) {
	var_dump("Error - shortening didn't shorten");
	sleep(120);
      }
    }
  }
}
?>
