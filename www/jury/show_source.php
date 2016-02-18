<?php

$runRandomCase = true;
require('init.php');
require(LIBDIR . '/lib.sourcecode.php');

$id = getRequestID();

presentSourceCode($id);

?>