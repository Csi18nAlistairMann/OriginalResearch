<?php

/*
  akas_class

  There's a sense by which a single entity might be known by more
  than one name. For instance "Del Boy" is the same entity as
  Derek Trotter. Names subsequent to the first might therefore be
  known as AKAs - also known as'.

  This class maintains those associations.
 */

class aka
{
  private $from = null;
  private $to = null;

  function from($val = null) {
    if ($val === null)
      return $this->from;
    else
      $this->from = $val;
  }

  function to($val = null) {
    if ($val === null)
      return $this->to;
    else
      $this->to = $val;
  }
}

class akas
{
  private $db_file;
  public $db;

  function akas($projname) {
    $this->db_file = './data/' . $projname . '/db/akas.serialised';
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

  function filter($tag) {
    $db2 = array();
    foreach($this->db as $item) {
      if ($item->from() === $tag) {
	$db2[] = $item->to();
      } elseif ($item->to() === $tag) {
	$db2[] = $item->from();
      }
    }
    return $db2;
  }

  function akaTags($tag1, $tag2) {
    $rv = 1;
    $found = false;
    foreach($this->db as $l) {
      if (($l->from() === $tag1 && $l->to() === $tag2)
	||
	  ($l->from() === $tag2 && $l->to() === $tag1)) {
	$found = true;
	break;
      }
    }
    if ($found === false) {
      $aka = new aka;
      $aka->from($tag1);
      $aka->to($tag2);
      $this->db[] = $aka;
      $this->save();
      $rv = 0;
    }
    return $rv;
  }
}

?>
