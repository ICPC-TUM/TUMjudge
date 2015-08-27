<li><a href="index.php"><span class="glyphicon glyphicon-home"></span> home</a></li>
<?php if(!logged_in()) { ?>
	<li><a href="login.php"><span class="glyphicon glyphicon-user"></span> login</a></li>
<?php } ?>
<li><a href="scoreboard.php"><span class="glyphicon glyphicon-th-list"></span> scoreboard</a></li>

<?php
putClock();
?>

