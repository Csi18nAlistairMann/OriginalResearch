<?php

/*
  developers_tend_to_have_repos.php

  Generally speaking, if $people is linked to $developer, then
  $people should also be linked to a thing that links to $repos

  $people.things = Scan things for anyone linked to people
  $devs.things = Scan $people for anyone linked to developers

  $repos = Scan $things for anything linked to repos

  $probs.things = Scan $devs for anyone not linked to one or more of $repos
 */

// argv[1] will contain a pathandfile that contains the arguments
// to look at, OR is "ALL".

require_once('classes/effort02_class.php');
require_once('classes/links_class.php');
require_once('classes/things_class.php');

$effort = new effort02;

// What tag will we show? 0 for 'first available'
$rv = 0;
if ($argc !== 3) {
  $effort->err(__FILE__, "expected two arguments");
  $rv = 1;
  exit;
}

$projname = $argv[1];
$paf = $argv[2];

if ($rv === 0) {
  $tag_arr = array();

  $things = new things($projname);
  $things->load();

  if ($paf === "ALL") {
    $tag_arr = $things->getUniqueTags();

  } elseif (file_exists($paf)) {
    $contents = file_get_contents($paf);
    $split_contents = explode("\n", $contents);
    foreach($split_contents as $a) {
      $a = trim($a);
      $a = trim($a, '\'"');
      if ($a !== '') {
	$tag_arr[] = $a;
      }
    }

  } else {
    $effort->err(__FILE__, "argument not a path-and-file or 'ALL'");
    $rv = 1;
  }
}

if ($rv === 0) {
  // Filter down to just People
  $people_tag = $things->getTagFor('People');
  if ($people_tag === false) {
    $effort->err(__FILE__, "Cant find tag for People");
    exit;
  }

  $links = new links($projname);
  $links->load();
  $tag_arr2 = array();
  foreach($links->db as $link) {
    $a = $link->from();
    $b = $link->to();
    $person = null;
    if ($a === $people_tag) {
      $person = $b;
    } elseif ($b === $people_tag) {
      $person = $a;
    }
    if ($person !== null) {
      foreach($tag_arr as $tag) {
	if ($tag === $person)
	  $tag_arr2[] = $tag;
      }
    }
  }

  // Filter down to just Developers
  $developers_tag = $things->getTagFor('Developers');
  if ($developers_tag === false) {
    $effort->err(__FILE__, "Cant find tag for Developers");
    exit;
  }

  $devs_arr = array();
  foreach($links->db as $link) {
    $a = $link->from();
    $b = $link->to();
    $person = null;
    if ($a === $developers_tag) {
      $person = $b;
    } elseif ($b === $developers_tag) {
      $person = $a;
    }
    if ($person !== null) {
      foreach($tag_arr2 as $tag) {
	if ($tag === $person)
	  $devs_arr[] = $tag;
      }
    }
  } // devs_arr contains tag of everyone marked developer

  // Filter down to the repos
  $repos_tag = $things->getTagFor('Repos');
  if ($repos_tag === false) {
    $effort->err(__FILE__, "Cant find tag for Repos");
    exit;
  }
  $toplevel_tag = $things->getTagFor('Top Level');
  if ($toplevel_tag === false) {
    $effort->err(__FILE__, "Cant find tag for Top level");
    exit;
  }

  $repos_arr = array();
  foreach($links->db as $link) {
    $a = $link->from();
    $b = $link->to();
    $repo = null;
    if ($a === $repos_tag && $a !== $toplevel_tag) {
      $repo = $b;
    } elseif ($b === $repos_tag && $a !== $toplevel_tag) {
      $repo = $a;
    }
    if ($repo !== null) {
      foreach($tag_arr as $tag) {
	if ($tag === $repo)
	  $repos_arr[] = $tag;
      }
    }
  } // repos_arr contains everything that links to Repo
  // so, github ...

  // Filter down to anything connected to one of the
  // repos above but not Repos itself
  $l2repos_arr = array();
  foreach($repos_arr as $repo) {
    $tag_arr = $links->filter($repo); // all things attached to [github]
    foreach($tag_arr as $url) {
      if ($url !== $repos_tag) { // except where github attaches to repo
	$l2repos_arr[] = $url;
      }
    }
  } // now have all things that are repo urls

  foreach($devs_arr as $dev) {
    $found = false;
    foreach($links->db as $link) {
      foreach($l2repos_arr as $url) {
	if (($link->from() === $dev && $link->to() === $url)
	    ||
	    ($link->from() === $url && $link->to() === $dev)) {
	  $found = true;
	}
      }
    }
    if ($found === false) {
      print_r("$dev not connected to a repo\n");
    }
  }
}

?>
