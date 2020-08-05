<?php

/*
  mandatory-connect-urls-to-things

  Given a URL and a tag, any Thing which starts with that URL
  should mandatorily also link to that tag.

  This code will apply the rules to multiple URLs and tags,
  calling template-url-connects-to-thing.php to give effect
  to each in turn.
 */

mb_internal_encoding("UTF-8");

require_once('defines.php');
require_once('classes/effort02_class.php');

$effort = new effort02;
$cribs = array(TWITTER_ROOT => 'Twitter',
	       'https://www.swanbitcoin.com/' => 'Swan Bitcoin',
	       'https://www.reddit.com/' => 'Reddit',
	       'https://about.me/' => 'AboutMe',
	       'https://www.youtube.com/' => 'Youtube',
	       'https://youtu.be/' => 'Youtube',
	       'https://keybase.io/' => 'Keybase',
	       'https://github.com/' => 'Github'
	       );

if ($argc === 2) {
  $projname = escapeshellarg($argv[1]);
  $tag_arg = escapeshellarg('ALL');

} elseif ($argc === 3) {
  $projname = escapeshellarg($argv[1]);
  $taglist = escapeshellarg($argv[2]);
  $temp = tmpfile();
  $tag_arg = stream_get_meta_data($temp)['uri'];
  $esc_tag_arg = escapeshellarg($tag_arg);
  file_put_contents($tag_arg, $taglist);
} else {
  $effort->err(__FILE__, "expected one or two arguments");
}

foreach($cribs as $url => $thing) {
  $esc_url = escapeshellarg($url);
  $esc_thing = escapeshellarg($thing);
  $output = shell_exec("php automated_scripts/template-url-connects-to-thing.php " .
		       "$projname $esc_tag_arg $esc_thing $esc_url");
}

?>
