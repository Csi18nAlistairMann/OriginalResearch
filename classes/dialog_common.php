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
  public $edit = '';
  public $input = '';
  public $input_init = '';
  public $menu = '';
  public $msg = '';
  public $sizes = MENU_SZ_LONG;
  public $title = '';

  function escape($val) {
    $escaped = str_replace("'", "'\''", $val);
    return $escaped;
  }

  function show(){
    $cmd = '';
    if ($this->crwrap !== '')
      $cmd .= "--cr-wrap ";

    if ($this->title !== '')
      $cmd .= "--title ' " . $this->escape($this->title) . " ' ";

    if ($this->input !== '') {
      $cmd .= "--inputbox '" . $this->escape($this->input) . "' " .
	$this->escape($this->sizes) . " ";
      if ($this->input_init !== '')
	$cmd .= "'" . $this->escape($this->input_init) . "' ";

    } elseif ($this->menu !== '') {
      $cmd .= "--menu '" . $this->escape($this->menu) . "' " .
	$this->escape($this->sizes) . " " . $this->choices;

    } elseif ($this->edit !== '') {
      // no quotes for edit as it's a filepath (?)
      $cmd .= "--editbox " . $this->escape($this->edit) . " " .
	$this->escape($this->sizes);

    } elseif ($this->msg !== '') {
      $cmd .= "--msgbox '" . $this->escape($this->msg) . "' " .
	$this->escape($this->sizes);
    }

    if ($this->debug === true) {
      var_dump($cmd);
      sleep(3);
      exit;
    }

    return $this->show_dialog($cmd);
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
  // https://invisible-island.net/dialog/manpage/dialog.txthttps://invisible-island.net/dialog/manpage/dialog.txt
  function show_dialog ($args) {
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
    proc_close ($p);
    // Return result
    return $result;
  }
}
?>
