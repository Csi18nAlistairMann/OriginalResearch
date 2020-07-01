<?php

/*
  mandatory-connect-urls-to-things

  Given a URL and a tag, any Thing which starts with that URL
  should mandatorily also link to that tag.

  This code will apply the rules to multiple URLs and tags,
  calling template-url-connects-to-thing.php to give effect
  to each in turn.
 */

require_once('classes/effort02_class.php');

$effort = new effort02;
$cribs = array('Twitter' => 'https://twitter.com/',
	       'Swan Bitcoin' => 'https://www.swanbitcoin.com/',
	       'Reddit' => 'https://www.reddit.com/',
	       'AboutMe' => 'https://about.me/',
	       'Github' => 'https://github.com/'
	       );

if ($argc === 2) {
  $projname = $argv[1];
  $tag_arg = 'ALL';

} elseif ($argc === 3) {
  $projname = $argv[1];
  $taglist = $argv[2];
  $temp = tmpfile();
  $tag_arg = stream_get_meta_data($temp)['uri'];
  file_put_contents($tag_arg, $taglist);

} else {
  $effort->err(__FILE__, "expected one or two arguments");
}

foreach($cribs as $thing => $url) {
  $output = shell_exec('php automated_scripts/template-url-connects-to-thing.php "' . $projname . '" ' . $tag_arg .  ' "' . $thing . '" "' . $url . '"');
}

?>
