<?php
/**
 * Produce a total score. Call with parameter 'static' for
 * output suitable for static HTML pages.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');
$title="TUMjudge";

$menu = true;
require(LIBWWWDIR . '/header.php');

?>

<h1>Welcome to TUMjudge!</h1>

<p>Somebody will guide you through this website, hopefully...</p>

<?php
require(LIBWWWDIR . '/footer.php');
