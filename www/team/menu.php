<ul class="nav navbar-nav">
<?php
echo '<li><a href="index.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="overview"><span class="glyphicon glyphicon-home"></span></a></li>'."\n";
echo '<li><a href="points.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="points overview"><span class="glyphicon glyphicon-tasks"></span></a></li>'."\n";

if ( have_problemtexts() ) {
	echo '<li><a href="problems.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="problems"><span class="glyphicon glyphicon-file"></span></a></li>'."\n";
}

if ( have_printing() ) {
	echo '<li><a href="print.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="print"><span class="glyphicon glyphicon-print"></span></a></li>'."\n";
}
echo '<li><a href="scoreboard.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="scoreboard"><span class="glyphicon glyphicon-th-list"></span></a></li>'."\n";

if ( checkrole('jury') || checkrole('balloon') ) {
	echo "<li><a target=\"_top\" href=\"../jury/\" accesskey=\"j\">→jury</a></li>\n";
}

?>
</ul>

<?php
putClock();
?>
