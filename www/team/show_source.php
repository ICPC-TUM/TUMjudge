<?php

error_reporting(-1);

require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

error_reporting(-1);

$id = getRequestID();

presentSourcecode($id, $teamid);
	
?>