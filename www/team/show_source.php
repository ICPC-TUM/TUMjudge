<?php

require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$id = getRequestID();

presentSourcecode($id, $teamid);
	
?>