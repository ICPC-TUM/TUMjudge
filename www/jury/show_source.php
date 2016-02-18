<?php

require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

$id = getRequestID();

$runRandomCase = true;
presentSourceCode($id);

?>