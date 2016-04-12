<?php
/**
 * Common page header.
 * Before including this, one can set $title, $refresh,
 * $printercss, $jscolor and $menu.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */
if (!defined('DOMJUDGE_VERSION')) die("DOMJUDGE_VERSION not defined.");

header('Content-Type: text/html; charset=' . DJ_CHARACTER_SET);

/* Prevent clickjacking by forbidding framing in modern browsers.
 * Really want to frame DOMjudge? Then change DENY to SAMEORIGIN
 * or even comment out the header altogether. For the public
 * interface there's no risk, and embedding the scoreboard in a
 * frame may be useful.
 */
if ( ! IS_PUBLIC ) header('X-Frame-Options: DENY');

$refresh_cookie = (!isset($_COOKIE["domjudge_refresh"]) || (bool)$_COOKIE["domjudge_refresh"]);

if ( isset($refresh) && $refresh_cookie ) {
	header('Refresh: ' . $refresh);
}

if(!isset($menu)) {
	$menu = true;
}
?>
<!DOCTYPE html>
<html lang="en" xml:lang="en">
<head>
	<!-- DOMjudge version <?php echo DOMJUDGE_VERSION?> -->
<meta charset="<?php echo DJ_CHARACTER_SET?>"/>
<title><?php echo $title?></title>
<link rel="icon" href="../images/favicon.png" type="image/png" />
<link rel="stylesheet" href="../style.css" type="text/css" />
<link rel="stylesheet" href="../css/bootstrap.css" type="text/css" />
<link rel="stylesheet" href="../css/tumjudge.css" type="text/css" />
<link rel="stylesheet" href="../css/menu.css" type="text/css" />
<script type="text/javascript" src="../js/jquery.js"></script>
<script type="text/javascript" src="../js/bootstrap.js"></script>
<?php
if ( IS_JURY ) {
	echo "<link rel=\"stylesheet\" href=\"style_jury.css\" type=\"text/css\" />\n";
	if (isset($printercss)) {
		echo "<link rel=\"stylesheet\" href=\"style_printer.css\" type=\"text/css\" media=\"print\" />\n";
	}
	echo "<script type=\"text/javascript\" src=\"../js/jury.js\"></script>\n";
	if (isset($jscolor)) {
		echo "<script type=\"text/javascript\" src=\"" .
		"../js/jscolor.js\"></script>\n";
	}
	if (isset($jqtokeninput)) {
		echo "<link rel=\"stylesheet\" href=\"../token-input.css\" type=\"text/css\" />";
		echo "<script type=\"text/javascript\" src=\"../js/jquery.tokeninput.min.js\"></script>\n";
	}
	echo "<script type=\"text/javascript\" src=\"" .
		"../js/sorttable.js\"></script>\n";
}
echo "<script type=\"text/javascript\" src=\"../js/domjudge.js\"></script>\n";

if ( ! empty($extrahead) ) echo $extrahead;
?>
</head>
<?php

$cssclass = (!empty($cid) ? 'contest-'.$cid.' ' : '').'tumjudge-'.$_GET['tumjudge_instance'];
if ( IS_JURY ) {
	global $pagename;
	echo "<body class=\"".$cssclass."\" onload=\"setInterval('updateMenu(" .
		(int)($pagename=='clarifications.php' && $refresh_cookie) . ", " .
		(int)($pagename=='judgehosts.php'     && $refresh_cookie) . ", " .
		(int)($pagename=='rejudgings.php'     && $refresh_cookie) . ")', 20000); " .
		"updateMenu(0,0,0)\">\n";
} else {
	echo "<body class=\"".$cssclass."\">\n";
}

?>

<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
      <ul class="nav navbar-nav">
        <li class="dropdown brand">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
            <span class="tumjudge-instance tumjudge-info"><span class="pagetitle">ICPC@TUM</span> Info</span>
            <span class="tumjudge-instance tumjudge-main"><span class="pagetitle">TUMjudge</span> Main</span>
            <span class="tumjudge-instance tumjudge-test"><span class="pagetitle">TUMjudge</span> Test</span>
            <span class="tumjudge-instance tumjudge-conpra"><span class="pagetitle">TUMjudge</span> ConPra</span>
            <span class="tumjudge-instance tumjudge-contest"><span class="pagetitle">TUMjudge</span> Contest</span>
            <span class="tumjudge-instance tumjudge-gad"><span class="pagetitle">TUMjudge</span> GAD</span>
            <span class="tumjudge-instance tumjudge-isabelle"><span class="pagetitle">TUMjudge</span> Isabelle</span>
            <span class="tumjudge-instance tumjudge-gcpc"><span class="pagetitle">TUMjudge</span> GCPC</span>
            <span class="tumjudge-instance tumjudge-ioide"><span class="pagetitle">TUMjudge</span> IOI Germany</span>
            <span class="tumjudge-instance tumjudge-ioiat"><span class="pagetitle">TUMjudge</span> IOI Austria</span>
            <span class="tumjudge-instance tumjudge-challenge"><span class="pagetitle">TUMjudge</span> Challenge</span>
            <span class="tumjudge-instance tumjudge-mlr"><span class="pagetitle">TUMjudge</span> MLR</span>
            <span class="caret"></span>
          </a>
          <ul class="dropdown-menu" role="menu">
            <li><a class="pagetitle">ICPC@TUM</a></li>
            <li class="page tumjudge-info"><a href="https://icpc.tum.de">Website <span class="description">Information about the ICPC</span></a></li>
            <li><a class="pagetitle">TUMjudge</a></li>
            <?php if(IS_JURY): ?>
              <li class="page tumjudge-main"><a href="https://judge.in.tum.de/main/">Main <span class="description">Account Management</span></a></li>
              <li class="page tumjudge-test"><a href="https://judge.in.tum.de/test/">Test <span class="description">Testing for TUMjudge Admins</span></a></li>
            <?php endif; ?>
            <li class="page tumjudge-conpra"><a href="https://judge.in.tum.de/conpra/">ConPra <span class="description">Lecture &ldquo;Algorithms for Programming Contests&rdquo;</span></a></li>
            <li class="page tumjudge-contest"><a href="https://judge.in.tum.de/contest/">Contest <span class="description">Preparation for the ACM ICPC</span></a></li>
            <li class="page tumjudge-gad"><a href="https://judge.in.tum.de/gad/">GAD <span class="description">Lecture &ldquo;Foundations: Algorithms and Data Structures&rdquo;</span></a></li>
            <li class="page tumjudge-isabelle"><a href="https://judge.in.tum.de/isabelle/">Isabelle <span class="description">Proving Contests</span></a></li>
            <li class="page tumjudge-gcpc"><a href="https://judge.in.tum.de/gcpc/">GCPC <span class="description">German Collegiate Programming Contest</span></a></li>
            <li class="page tumjudge-ioide"><a href="https://judge.in.tum.de/ioide/">IOI Germany <span class="description">Preparation for the IOI</span></a></li>
            <li class="page tumjudge-ioiat"><a href="https://judge.in.tum.de/ioiat/">IOI Austria <span class="description">Preparation for the IOI</span></a></li>
            <li class="page tumjudge-challenge"><a href="https://judge.in.tum.de/challenge/">Challenge <span class="description">Yet Another Programming Contest</span></a></li>
            <li class="page tumjudge-mlr"><a href="https://judge.in.tum.de/mlr/">MLR <span class="description">Lecture &ldquo;Machine Learning in Robotics&rdquo;</span></a></li>
          </ul>
        </li>
      <?php
        /* NOTE: here a local menu.php is included
         *       both jury and team have their own menu.php
         */
        if ( $menu ) include("menu.php");
      ?>
      </ul>          
    </div>
  </div>
</nav>

<script type="text/javascript">
<!--
  if ( 'Notification' in window && document.getElementById('notify_li') != null) {
    document.getElementById('notify_li').style.display = 'block';
  }
// -->
</script>

<div class="container">
