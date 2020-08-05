<?php

/*
  automated_cribs

  Used to hint the last time that a particular automation was
  applied against a particular tag: if the timestamp of the thing
  with that tag has not been changed there's no need to rerun
  the automation.

  27jun2020 database is currently empty
 */

class automated_crib
{
  private $tag = null;
  private $timestamp = null;

  function tag($val = null) {
    if ($val === null)
      return $this->tag;
    else
      $this->tag = $val;
  }

  function timestamp($val = null) {
    if ($val === null)
      return $this->timestamp;
    else
      $this->timestamp = $val;
  }
}

class automated_cribs
{
  private $db_file;
  public $db;

  function automated_cribs($projname) {
    $projname = mb_substr($projname, 1, mb_strlen($projname) - 2);
    $this->db_file = './data/' . $projname . '/db/automated_cribs.serialised';
  }

  function load() {
    if (!file_exists($this->db_file)) {
      $this->db = array();
      $this->save();
    }
    $this->db = unserialize(file_get_contents($this->db_file));
  }

  function save() {
    return file_put_contents($this->db_file, serialize($this->db));
  }

  function getTagLastChecked($tag) {
    foreach($this->db as $crib) {
      if ($crib->tag() === $tag) {
	return $crib->timestamp();
      }
    }
    return null;
  }

  function setTagLastChecked($tag, $timestamp) {
    $idx = 0;
    for($idx = 0; $idx < sizeof($this->db); $idx++) {
      if ($this->db[$idx]->tag() === $tag) {
	$this->db[$idx]->timestamp($timestamp);
      }
    }
    $this->save();
  }
}

?>
