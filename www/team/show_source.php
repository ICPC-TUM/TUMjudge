<?php

require('init.php');
require(LIBWWWDIR . '/lib.sourcecode.php');

$id = getRequestID();
echo "Test 1";
presentSourcecode($id, $teamid);
	
?>