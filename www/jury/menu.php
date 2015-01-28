<ul class="nav navbar-nav">
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">system <span class="caret"></span></a>
<ul class="dropdown-menu" role="menu">
<?php	if ( checkrole('balloon') ) { ?>
<li><a href="balloons.php">Balloon Status</a></li>
<?php	} ?>
<?php if ( checkrole('jury') ) { ?>
<li><a href="clarifications.php">Clarifications</a></li>
<li><a href="contests.php">Contests</a></li>
<li><a href="executables.php">Executables</a></li>
<li><a href="judgehosts.php">Judgehosts</a></li>
<li><a href="judgehost_restrictions.php">Judgehost Restrictions</a></li>
<li><a href="languages.php">Languages</a></li>
<li><a href="problems.php">Problems</a></li>
<li><a href="scoreboard.php">Scoreboard</a></li>  
<li><a href="statistics.php">Statistics</a></li>  
<li><a href="submissions.php">Submissions</a></li>
<li><a href="users.php">Users</a></li>
<li><a href="teams.php">Teams</a></li>
<li><a href="team_categories.php">Team Categories</a></li>
<li><a href="team_affiliations.php">Team Affiliations</a></li>
<?php } ?>
</ul>
</li>
<?php	if ( IS_ADMIN ) {
	$ndown = count($updates['judgehosts']);
	if ( $ndown > 0 ) { ?>
<li><a class="new" href="judgehosts.php" accesskey="j" id="menu_judgehosts">judgehosts <span class="label label-warning"><?php echo $ndown ?> down</span></a></li>
<?php	} else { ?>
<li><a href="judgehosts.php" accesskey="j" id="menu_judgehosts">judgehosts</a></li>
<?php	}
	} ?>
<?php	if ( checkrole('jury') ) {
	$nunread = count($updates['clarifications']);
	if ( $nunread > 0 ) { ?>
<li><a class="new" href="clarifications.php" accesskey="c" id="menu_clarifications">clarifications <span class="label label-info"><?php echo $nunread ?> new<span></a></li>
<?php	} else { ?>
<li><a href="clarifications.php" accesskey="c" id="menu_clarifications">clarifications</a></li>
<?php	} ?>
<?php	} ?>
<?php	if ( have_printing() ) { ?>
<li><a href="print.php" accesskey="p">print</a></li>
<?php	} ?>
<?php
if ( checkrole('team') ) {
	echo "<li><a target=\"_top\" href=\"../team/\" accesskey=\"t\">→team</a></li>\n";
}
?>
</ul>

<?php
putClock();
?>

<?php
$notify_flag  =  isset($_COOKIE["domjudge_notify"])  && (bool)$_COOKIE["domjudge_notify"];
$refresh_flag = !isset($_COOKIE["domjudge_refresh"]) || (bool)$_COOKIE["domjudge_refresh"];

if ( isset($refresh) ) {
	echo addForm('toggle_refresh.php', 'get', 'toggles', '', '', 'class="navbar-form navbar-right"') .
	    addHidden('enable', ($refresh_flag ? 0 : 1)) .
	    addSubmit(($refresh_flag ? 'Dis' : 'En' ) . 'able refresh', 'toggle_refresh') .
	    addEndForm();
}

// Default hide this from view, only show when javascript and
// notifications are available:
	addForm('toggle_notify.php', 'get', 'notify', '', '', 'class="navbar-form navbar-right" style="display: none"') .
	addHidden('enable', ($notify_flag ? 0 : 1)) .
	addSubmit(($notify_flag ? 'Dis' : 'En' ) . 'able notifications', 'toggle_notify',
	          'return toggleNotifications(' . ($notify_flag ? 'false' : 'true') . ')') .
	addEndForm();

?>
<script type="text/javascript">
<!--
    if ( 'Notification' in window ) {
		document.getElementById('notify').style.display = 'block';
	}
// -->
</script>
