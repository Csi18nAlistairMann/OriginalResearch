<?php

require_once('classes/akas_class.php');

class thing
{
  private $nuance = null;
  private $tag = null;
  private $text = null;
  private $timestamp = null;
  private $type = null;
  private $user = null;

  function nuance($val = null) {
    if ($val === null)
      return $this->nuance;
    else
      $this->nuance = $val;
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

  function getTextAndNuance() {
    $txt = $this->text();
    $nua = $this->nuance();
    if ($nua === null)
      return $txt;
    else
      return $txt . ' (NB:' . $nua . ')' ;
  }

  function timestamp($val = null) {
    if ($val === null)
      return $this->timestamp;
    else
      $this->timestamp = $val;
  }

  function type($val = null) {
    if ($val === null)
      return $this->type;
    else
      $this->type = $val;
  }

  function user($val = null) {
    if ($val === null)
      return $this->user;
    else
      $this->user = $val;
  }

  /*
    The nature of the AKA is that 'Del' is identical to 'Derek'. This is
    accomplished here by seeing if a phrase being searched for matches this
    item's text, or any item's text where that second item is connected to this
    one in akas.serialised.
   */
  function textIs($projname, $phrase) {
    $rv = array();
    if ($phrase === strtolower($this->text())) {
      $rv[] = $this;
    }
    return $rv;
    /* // now check if any AKA of this Thing matches. That is, while */
    /* // we are searching on Del, and this Thing is Derek */
    /* $akas = new akas($projname); */
    /* $akas->load(); */
    /* $akas_db = $akas->filter($this->tag()); */
    /* $things = new things($projname); */
    /* $things->load(); */

    /* foreach($akas_db as list($aka_from, $aka_to)) { */
    /*   if ($aka_from === $this->tag()) */
    /*	$aka_tag = $aka_to; */
    /*   else */
    /*	$aka_tag = $aka_from; */

    /*   $aka = $things->getThingFromTag($aka_tag); */

    /*   if ($phrase === strtolower($aka->text())) { */
    /*	$rv[] = $this; */
    /*   } */
    /* } */
    /* return $rv; */
  }

  function textStrPos($phrase) {
    $pos = strpos(strtolower($this->text()), $phrase);
    if ($pos !== false) {
      return array($this);
    }
    return array();
  }
}

class things
{
  private $db_file;
  public $db;

  function things($projname) {
    $this->db_file = './data/' . $projname . '/db/things.serialised';
  }

  function load() {
    if (!file_exists($this->db_file)) {
      // If the database doesn't exist we nevertheless want to have a top level
      // that can be used. Make it, and save it
      $t = new thing;
      $t->nuance('');
      $t->tag('?');
      $t->text('Top Level');
      $t->timestamp(date(TIMESTAMP_FORMAT));
      $t->type(TYPE_TEST_THING);
      $t->user(STANDARD_USER);
      $this->db = array($t);
      $this->save();
    }
    $this->db = unserialize(file_get_contents($this->db_file));
  }

  function save() {
    return file_put_contents($this->db_file, serialize($this->db));
  }

  function getUniqueTags() {
    $tag_arr = array();
    foreach($this->db as $thing) {
      $tag_arr[] = $thing->tag();
    }
    return array_unique($tag_arr);
  }

  function getTagFor($text) {
    foreach($this->db as $thing) {
      if ($thing->text() === $text) {
	return $thing->tag();
      }
    }
    return false;
  }

  function getThingFromTag($tag) {
    foreach($this->db as $thing) {
      if ($thing->tag() === $tag) {
	return $thing;
      }
    }
    return null;
  }

  // tag is first three non-whitespace chars of name if available, and number
  // of items already in db.
  // Also exclude website prefixes
  function getNewTag($text) {
    $s = str_replace(' ', '', $text);
    if (strpos($s, 'https://www.') === 0) {
      $s = substr($s, 12, 3);
    } elseif (strpos($s, 'https://') === 0) {
      $s = substr($s, 8, 3);
    } else {
      $s = substr($s, 0, 3);
    }
    $s .= dechex(sizeof($this->db) + 1);
    return $s;
  }

  function addIfNotDuplicate($new) {
    $found = false;
    foreach($this->db as $old) {
      if ($old->type() === $new->type() &&
	  $old->text() === $new->text()) {
	$found = true;
      }
    }
    if ($found === false) {
      $this->db[] = $new;
      return true;
    }
    return false;
  }
}

?>
