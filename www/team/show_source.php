<?php

require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

$id = getRequestID();

echo "Team ".$teamid;

presentSourcecode($id, $teamid);
	
?>