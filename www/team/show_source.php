<?php

require('init.php');
require(LIBWWWDIR . '/lib.sourcecode.php');

$id = getRequestID();

presentSourcecode($id, $teamid);
	
?>