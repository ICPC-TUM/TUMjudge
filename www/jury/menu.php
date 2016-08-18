<li><a href="index.php"><span class="glyphicon glyphicon-home"></span> home</a></li>
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
<span class="glyphicon glyphicon-wrench"></span> settings <span class="caret"></span></a>
<ul class="dropdown-menu" role="menu">
<?php if ( checkrole('balloon') ) { ?>
    <li><a href="balloons.php">balloon status</a></li>
<?php } ?>
<?php if ( checkrole('jury') ) { ?>
<li><a href="clarifications.php">clarifications</a></li>
<li><a href="contests.php">contests</a></li>
<li><a href="executables.php">executables</a></li>
<li><a href="hof.php">hall of fame</a></li>
<li><a href="judgehosts.php" id="menu_judgehosts">judgehosts
    <?php $ndown = count($updates['judgehosts']); if ( $ndown > 0 ) { ?><span class="label label-info"><?php echo $ndown ?> down</span><?php } ?>
</a></li>
<li><a href="judgehost_restrictions.php">judgehost restrictions</a></li>
<li><a href="languages.php">languages</a></li>
<?php if ( have_printing() ) { ?>
    <li><a href="print.php">print</a></li>
<?php } ?>
<li><a href="problems.php">problems</a></li>
<li><a href="rejudgings.php">rejudgings
    <?php $nrejudgings = count($updates['rejudgings']); if ( $nrejudgings > 0 ) { ?><span class="label label-info"><?php echo $ndown ?> new</span><?php } ?>
</a></li>  
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
<?php if ( checkrole('jury') ) { ?>
<li><a href="clarifications.php"><span class="glyphicon glyphicon-envelope"></span> clarifications
    <?php $nunread = count($updates['clarifications']);if ( $nunread > 0 ) { ?><span class="label label-info"><?php echo $nunread ?> new<span><?php } ?>
</a></li>
<?php } ?>
<li><a href="submissions.php"><span class="glyphicon glyphicon-file"></span> submissions</a></li>
<li><a href="scoreboard.php"><span class="glyphicon glyphicon-th-list"></span> scoreboard</a></li>  
<?php /*if ( checkrole('team') ) { ?>
    <li><a href="../team/"><span class="glyphicon glyphicon-user"></span> team account</a></li>
<?php } ?>
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
<?php }}*/ ?>
<?php

putClock();

?>