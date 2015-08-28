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

<?php if(!logged_in()) { ?>
<div style="width: 200px; float: right; margin: 20px;">
<h2>Login</h2>
<form action="login.php" method="post">
<input type="hidden" name="cmd" value="login" />
<table>
<tr><td><label for="login">Login:</label></td><td><input type="text" id="login" name="login" value="" size="15" maxlength="15" accesskey="l" autofocus /><
<tr><td><label for="passwd">Password:</label></td><td><input type="password" id="passwd" name="passwd" value="" size="15" maxlength="255" accesskey="p" />
<tr><td></td><td><input type="submit" value="Login" /></td></tr>
</table>
</form>
</div>
<?php } ?>

<h1>Welcome to TUMjudge!</h1>

<p>Somebody will guide you through this website, hopefully...</p>

<?php
require(LIBWWWDIR . '/footer.php');
