<?php

define('OR_DIR', '/home/am/OR/');
define('ARCHIVE_DIR', '/home/am/OR-archives/');
define('SCRAPES_DIR', OR_DIR . 'scrapes/');
define('WBD_DIR', ARCHIVE_DIR . 'what-bitcoin-did/');
define('SLP_DIR', ARCHIVE_DIR . 'stephanliverapodcast/');
// The shortest transcript to Episode 219 was 33371 bytes, so assume here that
// a transcript one tenth that is probably bogus.
define('MIN_TRANSCRIPT_LEN', 3337);
define('INSTALL_DIR', '/home/am/development/originalresearch/effort02/');

define('DATA_DIR', './data/');
define("TEMP_DIR", '/tmp/effort02');
define("WERE_LOOKING_AT", TEMP_DIR . '/were_looking_at');
define("BREAK_LINK_FROM", TEMP_DIR . '/break_link_from');
define("BREAK_LINK_TO", TEMP_DIR . '/break_link_to');
define("WAT_DO_NEXT", TEMP_DIR . '/what_to_do_next');
define("MENU_SZ_SHORT", '999 999');
define("MENU_SZ_LONG", MENU_SZ_SHORT . ' 999');
define("TIMESTAMP_FORMAT", 'YmdHis');
define("STANDARD_USER", '120');
define("TYPE_TEST_THING", 'test-thing');
define("MAX_MENU_ITEMS", 100);
define("KEY_BACK_A_PAGE", '(');
define("KEY_FORWARD_A_PAGE", ')');

define("PREDICATE_LINKS", 0);
define("PREDICATE_AKA_OF", 1);

define("TWITTER_ROOT", 'https://twitter.com/');

define("DUPES_OK", 'dupes-ok');
define("DUPES_NOT_OK", 'dupes-not-ok');

define('ERR_BAD_ARGS_T', 'Bad arguments')
?>
