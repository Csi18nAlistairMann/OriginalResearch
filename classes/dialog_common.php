<?php

/*
  dialog

  Effort02's UI is provided through dialog(1) calls - this class gives effect
  to them.

  Dialog(1) provides a nice balance between keyboard-only control with whole
  screen menus
*/

require_once("mixstring_class.php");
require_once("dialog_cmd_class.php");

class dialog
{
  public $cmd;
  private $common_choices = ''; /* A common choice is offered on every page */
  private $common_choices_size;
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
    $this->cmd = new dialog_cmd;
    $this->common_choices_size = 0;
  }

  // With the object populated, show() constructs the command dialog(1) needs
  // to give effect to what's required. "menu" may be so long that internal
  // errors occur, so paging is made available here with common items shown on
  // each page.
  function show() {
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
	$cmd .= "--title ' " . cli::escape($this->title) . " ' ";

      // Different types of dialog
      if ($this->input !== '') {
	$cmd .= "--inputbox '" . cli::escape($this->input) . "' " .
	  cli::escape($this->sizes) . " ";
	if ($this->input_init !== '')
	  $cmd .= "'" . cli::escape($this->input_init) . "' ";

      } elseif ($this->menu !== '') {
	if ($this->show_cancel === false)
	  $cmd .= '--nocancel ';
	$cmd .= "--menu '" . cli::escape($this->menu) . "' " .
	  cli::escape($this->sizes) . " ";
	$cmd_end = " " . $this->common_choices;
	$overhead = strlen($cmd) + strlen($cmd_end);
	$cmd .= $this->get_choices($overhead, $menu_start,
				   MAX_MENU_ITEMS -
				   $this->get_number_common_choices()) .
				   $cmd_end;

      } elseif ($this->edit !== '') {
	// no quotes for edit as it's a filepath (?)
	$cmd .= "--editbox " . cli::escape($this->edit) . " " .
	  cli::escape($this->sizes);

      } elseif ($this->msg !== '') {
	$cmd .= "--msgbox '" . cli::escape($this->msg) . "' " .
	  cli::escape($this->sizes);

      } elseif ($this->yesno !== '') {
	$return_status = true;
	$cmd .= "--yesno '" . cli::escape($this->yesno) . "' " .
	  cli::escape($this->sizes);
      }

      if ($this->debug === true) {
	var_dump($cmd);
	sleep(3);
	exit;
      }

      $rv = $this->show_dialog($cmd, $return_status);

      // Handle paging
      if ($rv === KEY_BACK_A_PAGE)
	$menu_start -= MAX_MENU_ITEMS - $this->get_number_common_choices();
      elseif ($rv === KEY_FORWARD_A_PAGE)
	$menu_start += MAX_MENU_ITEMS - $this->get_number_common_choices();
      else
	break;
    }
    return $rv;
  }

  // get_choices() returns all or a subset of the menu items available,
  // including the paging tag if required
  function get_choices($overhead, $start, $len = -1) {
    $actsize = $this->cmd->getNumItems();
    if ($len === -1 || $start + $len > $actsize)
      $len = $actsize - $start;
    if ($len > MAX_MENU_ITEMS)
      $len = MAX_MENU_ITEMS;

    // Back a page *only* if we're not looking at first menu item
    if ($start !== 0) {
      $rv = "'" . cli::escape('(') . "' '" . cli::escape('Back a page') .
	"' ";
      $overhead += strlen($rv);

    } else {
      $rv = '';
    }

    // If we have one menu item left unshown, show it, otherwise if we have
    // more than one left unshown, add Forward a page. Calculate it now so we
    // can determine if we need to add fewer menu items
    $end_rv = '';
    $numitems = $this->cmd->getNumItems();
    if ($start + $len === $numitems - 1)
      $end_rv =  $this->cmd->getItemN($start + $len) . " ";

    elseif ($start + $len < $numitems)
      $end_rv = "'" . cli::escape(')') . "' '" .
      cli::escape('Forward a page') . "' ";
    $overhead += strlen($end_rv);

    // And then we iterate over it shortening longest entries down to the next
    // longest until we can fit the lot into cmd without breaking the upper
    // bound on length
    $subset = $this->cmd->getSubset($start, $len);
    $numsubsetitems = $subset->getNumItems();
    $overhead += $numsubsetitems; /* Account for whitespace added below */
    $subset->shorten($overhead, MAX_CMD_LEN);

    // Build remaining menu items
    for($a = 0; $a < $numsubsetitems; $a++) {
      $ms = $subset->getItemN($a);
      $rv .= $ms->getEscaped() . " ";
    }

    // And complete by adding any items after the menu
    return $rv . $end_rv;
  }

  // common_choice_add doesn't use mixstrings as common menu items cannot be
  // paged
  function common_choice_add($tag, $text) {
    $this->common_choices_size++;
    $this->common_choices .= "'" . cli::escape($tag) . "' '" .
      cli::escape($text) . "' ";
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

  // sizes describes how size in characters of some dialogs
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
