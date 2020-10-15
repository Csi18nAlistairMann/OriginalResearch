<?php

require_once('defines.php');

/*
  review class

  The review class gives effect to notes against a particular
  tag that wouldn't itself count as a Thing. For instance, my
  opinion about how trolly someone might be
 */

class review
{
  private $tag = null;
  private $text = null;
  private $timestamp = null;
  private $user = null;

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
    if ($val === null)
      return $this->timestamp;
    else
      $this->timestamp = $val;
  }

  function user($val = null) {
    if ($val === null)
      return $this->user;
    else
      $this->user = $val;
  }

  function tagIs($val) {
    if (strval($this->tag()) === "$val") {
      return true;
    }
    return false;
  }
}

class reviews
{
  private $db_file;
  public $db;

  function reviews($projname) {
    $projname = mb_substr($projname, 1, mb_strlen($projname) - 2);
    $this->db_file = DATA_DIR . $projname . '/db/reviews.serialised';
  }

  function load() {
    if (!file_exists($this->db_file)) {
      $this->db = array();
      $this->save();
    }
    $this->db = unserialize(file_get_contents($this->db_file));
  }

  function save() {
    file_put_contents($this->db_file, serialize($this->db));
  }
}

?>
