<li><a href="../public/"><span class="glyphicon glyphicon-home"></span> home</a></li>
<li><a href="index.php"><span class="glyphicon glyphicon-star"></span> dashboard</a></li>
<li class="dropdown">
	<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
	<span class="glyphicon glyphicon-flag"></span> course <span class="caret"></span></a>
	<ul class="dropdown-menu" role="menu">
		<li><a href="points.php"> points overview </a></li>
		<?php if(have_problemtexts()) { ?>
			<li><a href="problems.php"> problems</a></li>
		<?php } ?>
		<?php if(have_printing()) { ?>
			<li><a href="print.php">print</a></li>
		<?php } ?>
	</ul>
</li>
<li><a href="scoreboard.php"><span class="glyphicon glyphicon-th-list"></span> scoreboard</a></li>
<li><a href="#"><span class="glyphicon glyphicon-globe"></span> news</a></li>

<?php
putClock();
?>
