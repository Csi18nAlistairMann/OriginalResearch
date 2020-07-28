<?php

require_once('defines.php');

class link
{
  private $subject = null;
  private $predicate = null;
  private $object = null;

  function subject($val = null) {
    if ($val === null)
      return $this->subject;
    else
      $this->subject = $val;
  }

  function predicate($val = null) {
    if ($val === null)
      return $this->predicate;
    else
      $this->predicate = intval($val);
  }

  function object($val = null) {
    if ($val === null)
      return $this->object;
    else
      $this->object = $val;
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

  function filter($tag, $predicate = -1) {
    $db = array();
    foreach($this->db as $item) {
      if ($predicate !== -1 && $item->predicate() !== $predicate) {
	continue;
      }
      if ($item->subject() === $tag) {
	$db[] = $item->object();
      } elseif ($item->object() === $tag) {
	$db[] = $item->subject();
      }
    }
    return $db;
  }

  function getLinkFromTags($tags, $tago) {
    foreach($this->db as $link) {
      if (($link->subject() === $tags && $link->object() === $tago)
	  ||
	  ($link->object() === $tags && $link->subject() === $tago)) {
	return $link;
      }
    }
    return null;
  }

  function linkTags($subject, $predicate, $object) {
    $rv = 1;
    $found = false;
    foreach($this->db as $l) {
      if (($l->subject() === $subject && $l->object() === $object)
	||
	  ($l->subject() === $object && $l->object() === $subject)) {
	$found = true;
	break;
      }
    }
    if ($found === false) {
      $link = new link;
      $link->subject($subject);
      $link->predicate($predicate);
      $link->object($object);
      $this->db[] = $link;
      $this->save();
      $rv = 0;
    }
    return $rv;
  }
}

?>
