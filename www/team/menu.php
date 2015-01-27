<ul class="nav navbar-nav">
<?php
echo "<li><a target=\"_top\" href=\"index.php\" accesskey=\"o\">overview</a></li>\n";

if ( have_problemtexts() ) {
	echo "<li><a target=\"_top\" href=\"problems.php\" accesskey=\"t\">problems</a></li>\n";
}

if ( have_printing() ) {
	echo "<li><a target=\"_top\" href=\"print.php\" accesskey=\"p\">print</a></li>\n";
}
echo "<li><a target=\"_top\" href=\"scoreboard.php\" accesskey=\"b\">scoreboard</a></li>\n";

if ( checkrole('jury') || checkrole('balloon') ) {
	echo "<li><a target=\"_top\" href=\"../jury/\" accesskey=\"j\">→jury</a></li>\n";
}

?>
</ul>

<?php
/*
echo "</div>\n\n<div id=\"menutopright\">\n";

putClock();

echo "</div></nav>\n\n";
*/