<?php

require('init.php');
$title = 'Points Overview';

require(LIBWWWDIR . '/header.php');
require(LIBWWWDIR . '/scoreboard.php');

echo "<h1>" . $title . "</h1>\n\n";
putPointsOverview($teamdata['teamid']);

require(LIBWWWDIR . '/footer.php');
?>
