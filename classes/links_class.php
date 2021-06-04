<?php

require_once('defines.php');

class link
{
  private $object = null;
  private $predicate = null;
  private $subject = null;
  private $timestamp = null;

  function __construct() {
    $this->timestamp(date(TIMESTAMP_FORMAT));
  }

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

  function timestamp($val = null) {
    if ($val === null)
      return $this->timestamp;
    else
      $this->timestamp = $val;
  }
}

class links
{
  private $db_file;
  public $db;

  function __construct($projname) {
    $projname = mb_substr($projname, 1, mb_strlen($projname) - 2);
    $this->db_file = DATA_DIR . $projname . '/db/links.serialised';
  }

  function load() {
    if (!file_exists($this->db_file)) {
      $l = new link;
      $this->db = array($l);
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
