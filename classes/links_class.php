<?php

class link
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

class links
{
  private $db_file;
  public $db;

  function links($projname) {
    $this->db_file = './data/' . $projname . '/db/links.serialised';
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
    $db = array();
    foreach($this->db as $item) {
      if ($item->from() === $tag) {
	$db[] = $item->to();
      } elseif ($item->to() === $tag) {
	$db[] = $item->from();
      }
    }
    return $db;
  }

  function linkTags($tag1, $tag2) {
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
      $link = new link;
      $link->from($tag1);
      $link->to($tag2);
      $this->db[] = $link;
      $this->save();
      $rv = 0;
    }
    return $rv;
  }
}

?>
