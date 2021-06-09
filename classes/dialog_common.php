<?php

/*
  dialog

  Effort02's UI is provided through dialog(1) calls - this class
  gives effect to them.

  Dialog(1) provides a nice balance between keyboard-only control
  with whole screen menus
 */

class dialog
{
  private $common_choices = ''; /* A common choice is offered on every menu */
  private $common_choices_size; /* page */
  private $choices_arr; /* Things appear in here */
  public $crwrap = '';
  public $debug = '';
  public $defaultno = '';
  public $edit = '';
  public $input = '';
  public $input_init = '';
  public $menu = '';
  public $msg = '';
  public $show_cancel = true;
  public $sizes = MENU_SZ_LONG;
  public $title = '';
  public $yesno = '';

  function __construct() {
    $this->common_choices_size = 0;
    $this->choices_arr = array();
  }

  function escape($val) {
    $escaped = str_replace("'", "'\''", $val);
    return $escaped;
  }

  // With the object populated, show constructs the command dialog(1) needs to
  // give effect to what's required. "menu" may be so long that internal errors
  // occur, so paging is made available here with common items shown on each
  // page.
  function show(){
    $menu_start = 0;
    $return_status = false;

    while(1) {
      $cmd = '';
      // Common options
      if ($this->crwrap !== '')
	$cmd .= "--cr-wrap ";

      if ($this->defaultno !== '')
	$cmd .= "--defaultno ";

      if ($this->title !== '')
	$cmd .= "--title ' " . $this->escape($this->title) . " ' ";

      // Different types of dialog
      if ($this->input !== '') {
	$cmd .= "--inputbox '" . $this->escape($this->input) . "' " .
	  $this->escape($this->sizes) . " ";
	if ($this->input_init !== '')
	  $cmd .= "'" . $this->escape($this->input_init) . "' ";

      } elseif ($this->menu !== '') {
	if ($this->show_cancel === false) {
	  $cmd .= '--nocancel ';
	}
	$cmd .= "--menu '" . $this->escape($this->menu) . "' " .
	  $this->escape($this->sizes) . " " .
	  $this->get_choices($menu_start,
			     MAX_MENU_ITEMS - $this->get_number_common_choices()) .
	  " " . $this->common_choices;

      } elseif ($this->edit !== '') {
	// no quotes for edit as it's a filepath (?)
	$cmd .= "--editbox " . $this->escape($this->edit) . " " .
	  $this->escape($this->sizes);

      } elseif ($this->msg !== '') {
	$cmd .= "--msgbox '" . $this->escape($this->msg) . "' " .
	  $this->escape($this->sizes);

      } elseif ($this->yesno !== '') {
	$return_status = true;
	$cmd .= "--yesno '" . $this->escape($this->yesno) . "' " .
	  $this->escape($this->sizes);
      }

      if ($this->debug === true) {
	var_dump($cmd);
	sleep(3);
	exit;
      }

      $rv = $this->show_dialog($cmd, $return_status);
      if ($rv === KEY_BACK_A_PAGE)
	$menu_start -= MAX_MENU_ITEMS - $this->get_number_common_choices();
      elseif ($rv === KEY_FORWARD_A_PAGE)
	$menu_start += MAX_MENU_ITEMS - $this->get_number_common_choices();
      else
	break;
    }

    return $rv;
  }

  // get_choices returns all or a subset of the menu items available, including
  // the paging tag if required
  function get_choices($start, $len = -1) {
    if ($len === -1)
      $len = sizeof($this->choices_arr);

    // Back a page *only* if we're not looking at first menu item
    if ($start !== 0)
      $rv = "'" . $this->escape('(') . "' '" . $this->escape('Back a page') .
	"' ";
    else
      $rv = '';

    // Remaining menu items
    for($a = $start; $a < $start + $len; $a++)
      $rv .= $this->choices_arr[$a] . " ";

    // If we have one menu item left unshown, show it, otherwise if we have
    // more than one left unshown, add Forward a page
    if ($start + $len === sizeof($this->choices_arr) - 1)
      $rv .=  $this->choices_arr[$start + $len] . " ";

    elseif ($start + $len < sizeof($this->choices_arr))
      $rv .= "'" . $this->escape(')') . "' '" . $this->escape('Forward a page') .
      "' ";

    return $rv;
  }

  function choice_add($tag, $text) {
    $this->choices_arr[] = "'" . $this->escape($tag) . "' '" .
      $this->escape($text) . "'";
  }

  // common_choice_add uses a simple string as common menu items cannot be
  // paged
  function common_choice_add($tag, $text) {
    $this->common_choices_size++;
    $this->common_choices .= "'" . $this->escape($tag) . "' '" .
      $this->escape($text) . "' ";
  }

  function get_number_common_choices() {
    return $this->common_choices_size;
  }

  function crwrap($val) {
    $this->crwrap = $val;
  }

  function debug($val) {
    $this->debug = $val;
  }

  function sizes_change($text) {
    $this->sizes = $text;
  }

  // https://stackoverflow.com/questions/4711904/using-linux-dialog-command-from-php
  // https://invisible-island.net/dialog/manpage/dialog.txt
  function show_dialog ($args, $return_status) {
    $pipes = array (NULL, NULL, NULL);
    // Allow user to interact with dialog
    $in = fopen ('php://stdin', 'r');
    $out = fopen ('php://stdout', 'w');
    // But tell PHP to redirect stderr so we can read it
    $p = proc_open ('dialog '.$args, array (
					    0 => $in,
					    1 => $out,
					    2 => array ('pipe', 'w')
					    ), $pipes);
    // Wait for and read result
    $result = stream_get_contents ($pipes[2]);
    // Close all handles
    fclose ($pipes[2]);
    fclose ($out);
    fclose ($in);
    $status = proc_close ($p);
    if ($status === 127) {
      echo "dialog(1) call failed with code 127. Args too long?";
      sleep(30);
    }
    if ($return_status === false)
      return $result;
    else
      return $status;
  }
}
?>
