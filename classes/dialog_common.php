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
  public $choices = '';
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

  function escape($val) {
    $escaped = str_replace("'", "'\''", $val);
    return $escaped;
  }

  function show(){
    $return_status = false;
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
	$this->escape($this->sizes) . " " . $this->choices;

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

    return $this->show_dialog($cmd, $return_status);
  }

  function choice_add($tag, $text) {
    $this->choices .= "'" . $this->escape($tag) . "' '" . $this->escape($text) . "' ";
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
    if ($return_status === false)
      return $result;
    else
      return $status;
  }
}
?>
