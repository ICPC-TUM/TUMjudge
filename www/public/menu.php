<ul class="nav navbar-nav">
<li><a href="index.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="home"><span class="glyphicon glyphicon-home"></span></a></li>
<?php
if ( have_problemtexts() ) {
	echo '<li><a href="problems.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="problems"><span class="glyphicon glyphicon-file"></span></a></li>'."\n";
}
logged_in(); // fill userdata
if ( checkrole('team') ) {
	echo "<li><a target=\"_top\" href=\"../team/\" accesskey=\"t\">→team</a></li>\n";
}
if ( checkrole('jury') || checkrole('balloon') ) {
	echo "<li><a target=\"_top\" href=\"../jury/\" accesskey=\"j\">→jury</a></li>\n";
}
if ( !logged_in() && DOMSERVER_REPLICATION != 'master' ) {
	echo '<li><a href="login.php" class="dj-tooltip" data-toggle="tooltip" data-placement="bottom" title="login"><span class="glyphicon glyphicon-user"></span></a></li>'."\n";
}
?>
</ul>
