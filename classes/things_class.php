<?php

require_once('classes/links_class.php');

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
    if ($nua === null || $nua === '')
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
    textIs

    Return Things matching a given string, and anything of which such matches
    are an AKA.

    This is processing intensive so for the moment I'm leaving it turned off by
    returning early.
   */
  function textIs($phrase, $things, $links) {
    $rv = array();
    if ($phrase === mb_strtolower($this->text())) {
      $rv[] = $this;
    }
    return $rv;

    // Now check if this is an AKA of something else. If it is, count that
    // something else as a match too.
    $links_db = $links->filter($this->tag(), PREDICATE_AKA_OF);
    foreach($links_db as $link_tag) {
      $aka = $things->getThingFromTag($link_tag);

      if ($phrase === mb_strtolower($aka->text())) {
	$rv[] = $this;
      }
    }
    return $rv;
  }

  function textStrPos($phrase) {
    $pos = mb_strpos(mb_strtolower($this->text()), $phrase);
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
    $projname = mb_substr($projname, 1, mb_strlen($projname) - 2);
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

  function getTagFor($text, $lower = false) {
    if ($lower === true) {
      foreach($this->db as $thing) {
	if (mb_strtolower($thing->text()) === $text) {
	  return $thing->tag();
	}
      }

    } else {
      foreach($this->db as $thing) {
	if ($thing->text() === $text) {
	  return $thing->tag();
	}
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
    $s = str_replace('"', '', $s);
    $s = str_replace("'", '', $s);
    if (mb_strpos($s, 'https://www.') === 0) {
      $s = mb_substr($s, 12, 3);
    } elseif (mb_strpos($s, 'https://') === 0) {
      $s = mb_substr($s, 8, 3);
    } else {
      $index = -1;
      do {
	$index++;
      } while ($index < mb_strlen($s)
	       &&
	       ($s[$index] === "'" || $s[$index] === '"')
	       );
      $s = mb_substr($s, $index, 3);
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

  function add($new) {
    $this->db[] = $new;
    return true;
  }
}

?>
