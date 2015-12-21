<?php

require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

$id = getRequestID();
echo "Test 1";
presentSourcecode($id, $teamid);
	
?>