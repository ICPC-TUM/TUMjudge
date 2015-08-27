<?php
/**
 * Produce a total score. Call with parameter 'static' for
 * output suitable for static HTML pages.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$title="Scoreboard";
// set auto refresh
$refresh="30;url=./scoreboard.php";

// This reads and sets a cookie, so must be called before headers are sent.
$filter = initScorefilter();

$menu = true;
require(LIBWWWDIR . '/header.php');

$isstatic = @$_SERVER['argv'][1] == 'static' || isset($_REQUEST['static']);

// call the general putScoreBoard function from scoreboard.php
putScoreBoard($cdata, null, $isstatic, $filter);

echo "<script type=\"text/javascript\">initFavouriteTeams();</script>";

require(LIBWWWDIR . '/footer.php');
