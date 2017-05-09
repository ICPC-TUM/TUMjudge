<?php
global $updates;
?>
<nav><div id="menutop">
<a href="index.php" accesskey="h"><span class="octicon octicon-home"></span> home</a>
<?php	if ( checkrole('balloon') ) { ?>
<a href="balloons.php" accesskey="b"><span class="octicon octicon-location"></span> balloons</a>
<?php	} ?>
<?php	if ( checkrole('jury') ) { ?>
<a href="problems.php" accesskey="p"><span class="octicon octicon-book"></span> problems</a>
<?php	} ?>
<?php	if ( IS_ADMIN ) {
	$ndown = count($updates['judgehosts']);
	if ( $ndown > 0 ) { ?>
<a class="new" href="judgehosts.php" accesskey="j" id="menu_judgehosts"><span class="octicon octicon-law"></span> judgehosts (<?php echo $ndown ?> down)</a>
<?php	} else { ?>
<a href="judgehosts.php" accesskey="j" id="menu_judgehosts"><span class="octicon octicon-law"></span> judgehosts</a>
<?php	} ?>
<a href="judgehosts_restrictions.php"><span class="octicon octicon-key"></span> judgehosts restrictions</a>
<?php
	$nerr = count($updates['internal_error']);
	if ( $nerr > 0 ) { ?>
<a class="new" href="internal_errors.php" accesskey="e" id="menu_internal_error"><span class="octicon octicon-zap"></span> internal error (<?php echo $nerr ?> new)</a>
<?php	}
	} ?>
<?php	if ( checkrole('jury') ) { ?>
<a href="teams.php" accesskey="t"><span class="octicon octicon-organization"></span> teams</a>
<a href="team_categories.php"><span class="octicon octicon-tag"></span> team categories</a>
<a href="team_affiliations.php"><span class="octicon octicon-mortar-board"></span> team affiliations</a>
<a href="users.php" accesskey="u"><span class="octicon octicon-person"></span> users</a>
<a href="contests.php"><span class="octicon octicon-rocket"></span> contests</a>
<a href="hof.php"><span class="octicon octicon-star"></span> hall of fame</a>
<?php
	$nunread = count($updates['clarifications']);
	if ( $nunread > 0 ) { ?>
<a class="new" href="clarifications.php" accesskey="c" id="menu_clarifications"><span class="octicon octicon-comment-discussion"></span> clarifications (<?php echo $nunread ?> new)</a>
<?php	} else { ?>
<a href="clarifications.php" accesskey="c" id="menu_clarifications"><span class="octicon octicon-comment-discussion"></span> clarifications</a>
<?php	} ?>
<a href="submissions.php" accesskey="s"><span class="octicon octicon-file-code"></span> submissions</a>
<?php
	$nrejudgings = count($updates['rejudgings']);
	if ( $nrejudgings > 0 ) { ?>
<a class="new" href="rejudgings.php" accesskey="r" id="menu_rejudgings"><span class="octicon octicon-sync"></span> rejudgings (<?php echo $nrejudgings ?> active)</a>
<?php	} else { ?>
<a href="rejudgings.php" accesskey="r" id="menu_rejudgings"><span class="octicon octicon-sync"></span> rejudgings</a>
<?php	} ?>
<?php   } /* checkrole('jury') */ ?>
<?php	if ( have_printing() ) { ?>
<a href="print.php" accesskey="p"><span class="octicon octicon-file-text"></span> print</a>
<?php	} ?>
<?php	if ( checkrole('jury') ) { ?>
<a href="scoreboard.php"><span class="octicon octicon-list-ordered"></span> scoreboard</a>
<a href="statistics.php"><span class="octicon octicon-graph"></span> statistics</a>
<?php	} ?>
<?php
/*
if ( checkrole('team') ) {
	echo "<a target=\"_top\" href=\"../team/\" accesskey=\"t\"><span class=\"octicon octicon-arrow-right\"></span> team</a>\n";
}
*/
?>
</div>

<div id="menutopright">
<?php

putClock();

$notify_flag  =  isset($_COOKIE["domjudge_notify"])  && (bool)$_COOKIE["domjudge_notify"];
$refresh_flag = !isset($_COOKIE["domjudge_refresh"]) || (bool)$_COOKIE["domjudge_refresh"];

echo 	addForm('toggle_notify.php', 'get', 'notify') .
	addHidden('enable', ($notify_flag ? 0 : 1)) .
	addEndForm();
if ( isset($refresh) ) {
	$text = $refresh_flag ? 'Disable' : 'Enable';
	echo '<input id="refresh-toggle" type="button" value="' . $text . ' refresh" />';
}
?>
<li id="notify_li" style="display: none">
<?php if($notify_flag) { ?>
    <a href="#" onclick="toggleNotifications(false); document.getElementById('notify').submit();">
    <span class="glyphicon glyphicon-volume-up"></span> notifications on</a>
<?php } else { ?>
    <a href="#" onclick="toggleNotifications(true); document.getElementById('notify').submit();">
    <span class="glyphicon glyphicon-volume-off"></span> notifications off</a>
<?php } ?>
</li>
<?php if(isset($refresh)) { ?>
<?php if($refresh_flag) { ?>
    <li><a href="#" onclick="document.getElementById('toggles').submit();"><span class="glyphicon glyphicon-refresh"></span> refresh on</a></li>
<?php } else { ?>
    <li><a href="#" onclick="document.getElementById('toggles').submit();"><span class="glyphicon glyphicon-lock"></span> refresh off</a></li>
<?php }} ?>
<?php

putClock();

?>
