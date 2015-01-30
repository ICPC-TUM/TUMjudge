<ul class="nav navbar-nav">
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><span class="glyphicon glyphicon-wrench"></span> <span class="caret"></span></a>
<ul class="dropdown-menu" role="menu">
<?php	if ( checkrole('balloon') ) { ?>
<li><a href="balloons.php">balloon status</a></li>
<?php	} ?>
<?php if ( checkrole('jury') ) { ?>
<li><a href="clarifications.php">clarifications</a></li>
<li><a href="contests.php">contests</a></li>
<li><a href="executables.php">executables</a></li>
<li><a href="judgehosts.php">judgehosts</a></li>
<li><a href="judgehost_restrictions.php">judgehost restrictions</a></li>
<li><a href="languages.php">languages</a></li>
<li><a href="problems.php">problems</a></li>
<li><a href="scoreboard.php">scoreboard</a></li>  
<li><a href="statistics.php">statistics</a></li>  
<li><a href="submissions.php">submissions</a></li>
<li><a href="users.php">users</a></li>
<li><a href="teams.php">teams</a></li>
<li><a href="team_categories.php">team categories</a></li>
<li><a href="team_affiliations.php">team affiliations</a></li>
<?php } ?>
</ul>
</li>
<?php	if ( IS_ADMIN ) {
	$ndown = count($updates['judgehosts']);
	if ( $ndown > 0 ) { ?>
<li><a class="new" href="judgehosts.php" accesskey="j" id="menu_judgehosts"><span class="glyphicon glyphicon-education"></span> <span class="label label-warning"><?php echo $ndown ?> down</span></a></li>
<?php	} else { ?>
<li><a href="judgehosts.php" accesskey="j" id="menu_judgehosts"><span class="glyphicon glyphicon-education"></span></a></li>
<?php	}
	} ?>
<?php	if ( checkrole('jury') ) {
	$nunread = count($updates['clarifications']);
	if ( $nunread > 0 ) { ?>
<li><a class="new" href="clarifications.php" accesskey="c" id="menu_clarifications"><span class="glyphicon glyphicon-envelope"></span> <span class="label label-info"><?php echo $nunread ?> new<span></a></li>
<?php	} else { ?>
<li><a href="clarifications.php" accesskey="c" id="menu_clarifications"><span class="glyphicon glyphicon-envelope"></span></a></li>
<?php	} ?>
<?php	} ?>
<li><a href="submissions.php"><span class="glyphicon glyphicon-file"></span></a></li>
<li><a href="scoreboard.php"><span class="glyphicon glyphicon-th-list"></span></a></li>  
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

echo 	addForm('toggle_notify.php', 'get', 'notify') .
	addHidden('enable', ($notify_flag ? 0 : 1)) .
	addEndForm();
if ( isset($refresh) ) {
	echo addForm('toggle_refresh.php', 'get', 'toggles') .
	    addHidden('enable', ($refresh_flag ? 0 : 1)) .
	    addEndForm();
}
?>

<ul class="nav navbar-nav navbar-right">
<?php
if ( isset($refresh) ) {
echo    '<li>' . ($refresh_flag ? '<a href="#" onclick="document.getElementById(\'toggles\').submit();"><span class="glyphicon glyphicon-refresh"></span></a>' : '<a href="#" onclick="document.getElementById(\'toggles\').submit();"><span class="glyphicon glyphicon-lock"></span></a>') . '</li>';
}

// Default hide this from view, only show when javascript and
// notifications are available:
echo	'<li id="notify_li" style="display: none">' .($notify_flag ? '<a href="#" onclick="toggleNotifications(false);"><span class="glyphicon glyphicon-volume-up"></span></a>' : '<a href="#" onclick="toggleNotifications(true);"><span class="glyphicon glyphicon-volume-off"></span></a>') . '</li>';

?>
</ul>

<script type="text/javascript">
<!--
    if ( 'Notification' in window ) {
		document.getElementById('notify_li').style.display = 'block';
	}
// -->
</script>
