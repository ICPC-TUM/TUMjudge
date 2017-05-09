<?php

echo "<nav><div id=\"menutop\">\n";

echo "<a target=\"_top\" href=\"index.php\" accesskey=\"o\"><span class=\"octicon octicon-home\"></span> overview</a>\n";

echo "<a target=\"_top\" href=\"problems.php\" accesskey=\"t\"><span class=\"octicon octicon-book\"></span> problems</a>\n";

if ( have_printing() ) {
	echo "<a target=\"_top\" href=\"print.php\" accesskey=\"p\"><span class=\"octicon octicon-file-text\"></span> print</a>\n";
}
echo "<a target=\"_top\" href=\"scoreboard.php\" accesskey=\"b\"><span class=\"octicon octicon-list-ordered\"></span> scoreboard</a>\n";
echo "<a target=\"_top\" href=\"news.php\" accesskey=\"n\" id=\"newsLinkMenu\"><span class=\"octicon octicon-mail\"></span> news</a>\n";
echo "<a target=\"_top\" href=\"help.php\" accesskey=\"h\"><span class=\"octicon octicon-question\"></span> help</a>\n";

if ( checkrole('jury') || checkrole('balloon') ) {
	echo "<a target=\"_top\" href=\"../jury/\" accesskey=\"j\"><span class=\"octicon octicon-arrow-right\"></span> jury</a>\n";
}

echo "</div>\n\n<div id=\"menutopright\">\n";

putClock();
?>
