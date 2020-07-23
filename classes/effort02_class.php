<?php

/*
  effort02

  The 'effort02' code is a mishmash of individual php files bound together with
  bash glue, with many functions made available through shell_exec().

  This class endeavours to maintain some kind of state between them all.
 */

require_once('defines.php');

class effort02
{
  function effort02() {
    if (!file_exists(TEMP_DIR))
      mkdir(TEMP_DIR);
  }

  function wereLookingAt($val = null) {
    if ($val === null) {
      if (file_exists(WERE_LOOKING_AT)) {
	$v = file_get_contents(WERE_LOOKING_AT);
	file_put_contents(BREAK_LINK_FROM, $v);
	return $v;

      } else {
	return null;
      }

    } else {
      file_put_contents(BREAK_LINK_TO, $val);
      file_put_contents(WERE_LOOKING_AT, $val);
      return;
    }
  }

  function whatToShowNext($val) {
    file_put_contents(WAT_DO_NEXT, $val);
  }

  function err($file, $text) {
    print_r($file . ": " . $text . "\n");
    file_put_contents('/tmp/or.log', $file . ": " . $text . "\n", FILE_APPEND);
    sleep(5);
  }
}

?>
