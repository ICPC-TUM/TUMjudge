<ul class="nav navbar-nav">
<li><a href="index.php" accesskey="h">home</a></li>
<?php
if ( have_problemtexts() ) {
	echo "<li><a href=\"problems.php\" accesskey=\"p\">problems</a></li>\n";
}
logged_in(); // fill userdata
if ( checkrole('team') ) {
	echo "<li><a target=\"_top\" href=\"../team/\" accesskey=\"t\">→team</a></li>\n";
}
if ( checkrole('jury') || checkrole('balloon') ) {
	echo "<li><a target=\"_top\" href=\"../jury/\" accesskey=\"j\">→jury</a></li>\n";
}
if ( !logged_in() ) {
	echo "<li><a href=\"login.php\" accesskey=\"l\">login</a></li>\n";
}
?>
</ul>

<?php
putClock();
?>

